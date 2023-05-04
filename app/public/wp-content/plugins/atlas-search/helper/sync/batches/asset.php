<?php

namespace Wpe_Content_Engine\Helper\Sync\Batches;

use ErrorException;
use WP_CLI;
use WP_Post;
use WP_Query;
use Wpe_Content_Engine;
use Wpe_Content_Engine\Helper\Constants\Post_Mime_Type;
use Wpe_Content_Engine\Helper\Constants\Post_Status;
use Wpe_Content_Engine\Helper\Constants\Post_Type;
use Wpe_Content_Engine\Helper\Progress_Bar_Info_Trait;
use Wpe_Content_Engine\Helper\Sync\Entity\Wordpress\Asset as Asset_Entity;

class Asset implements Batch_Sync_Interface {

	use Progress_Bar_Info_Trait;

	/**
	 * @var Asset_Entity
	 */
	private Asset_Entity $sync_asset;

	public function __construct( Asset_Entity $sync_asset ) {
		$this->sync_asset = $sync_asset;
	}

	/**
	 * @param int   $offset Offset.
	 * @param mixed $number Number.
	 * @return WP_Post[]
	 */
	public function get_items( $offset, $number ): array {
		$q   = array(
			'post_type'      => array( Post_Type::ATTACHMENT ),
			'post_status'    => Post_Status::INHERIT,
			'post_mime_type' => Post_Mime_Type::IMAGE,
			'posts_per_page' => $number,
			'paged'          => $offset,
		);
		$qry = new WP_Query( $q );

		return $qry->posts;
	}

	/**
	 * @param WP_Post[] $posts WordPress assets are stored as posts with different post_type and mime_type.
	 *
	 * @throws ErrorException Exception.
	 */
	public function sync( $posts ) {
		if ( count( $posts ) <= 0 ) {
			return;
		}

		foreach ( $posts as $post ) {
			$this->sync_asset->upsert( $post->ID, $post );
			$this->tick();
		}
		$this->finish();
	}

	/**
	 * @param mixed $items Items.
	 * @param mixed $page Page.
	 */
	public function format_items( $items, $page ) {
		$o = array_column( $items, 'ID' );
		WP_CLI::log( WP_CLI::colorize( "%RSyncing WordPress Assets - Page:{$page} Ids:" . implode( ',', $o ) . '%n ' ) );
	}

	/**
	 * @return int
	 */
	public function get_total_items(): int {
		$available_mime_types = get_available_post_mime_types( 'attachment' );
		$image_type_counters  = wp_count_attachments( Post_Mime_Type::IMAGE );
		$total_counter        = 0;
		foreach ( $available_mime_types as $available_mime_type ) {
			$total_counter += $image_type_counters->$available_mime_type ?? 0;
		}

		return $total_counter;
	}
}
