<?php

namespace Wpe_Content_Engine\Helper\Sync\Entity\Wordpress;

use ErrorException;
use WP_Post;
use Wpe_Content_Engine\Helper\Logging\Server_Log_Info;
use Wpe_Content_Engine\WPSettings;

class Asset extends WP_Entity {


	/**
	 * @param int          $asset_id Since assets (attachments) are stored in wp_posts table its actually post_id.
	 * @param WP_Post|null $post Post.
	 * @throws ErrorException Exception.
	 */
	public function upsert( int $asset_id, WP_Post $post = null ) {
		if ( empty( $post ) ) {
			$post = get_post( $asset_id );
		}

		$query = <<<'GRAPHQL'
				mutation syncAsset(
					$wpId: Int!
					$url: String!
					$mimeType: String
					$assetType: String
					$metadata: JSON
				) {
					syncAsset(
						wpId: $wpId
						data: {
							url: $url
							mimeType: $mimeType
							assetType: $assetType
							metadata: $metadata
						}
					) {
								status
								message
					}
				}
				GRAPHQL;

		$graphql_vars = array(
			'wpId'      => $asset_id,
			'url'       => $post->guid,
			'mimeType'  => $post->post_mime_type,
			'assetType' => null,
			'metadata'  => wp_get_attachment_metadata( $asset_id ) ?: null,
		);

		$wpe_content_engine_options = $this->settings->get( WPSettings::WPE_CONTENT_ENGINE_OPTION_NAME ); // Array of All Options.

		$this->client->query(
			$wpe_content_engine_options['url'],
			$query,
			$graphql_vars,
			$wpe_content_engine_options['access_token'],
			( new Server_Log_Info() )->get_data()
		);
	}

	/**
	 * TODO: This is disabled because of ORN-205. We can take a look post Q2
	 *  to see if this is really needed or needs to be removed
	 *
	 * @param int     $asset_id Asset ID.
	 * @param WP_Post $post Post.
	 * @throws ErrorException Exception.
	 */
	public function delete( int $asset_id, WP_Post $post ) {
		$query = <<<'GRAPHQL'
			mutation PostDelete(
			$wpId: Int!
			$url: String!
			) {
				postDelete(
				wpId: $wpId
				url: $url
				) {
					status
					message
				}
			}
			GRAPHQL;

		$wpe_content_engine_options = $this->settings->get( WPSettings::WPE_CONTENT_ENGINE_OPTION_NAME ); // Array of All Options.
		$url                        = $wpe_content_engine_options['url']; // Url.
		$access_token               = $wpe_content_engine_options['access_token']; // Access Token.

		$this->client->query(
			$url,
			$query,
			array(
				'wpId' => $asset_id,
				'url'  => wp_get_attachment_url( $asset_id ),
			),
			$access_token
		);
	}
}
