<?php

namespace Wpe_Content_Engine\Helper\Acf_Support;

use Wpe_Content_Engine\Helper\Acf_Support\Acf_Factory;
use Wpe_Content_Engine\Helper\Constants\Json_Schema_Type;
use Wpe_Content_Engine\Helper\String_Transformation;

class Acf {

	/**
	 * @var array
	 */
	public const ACF_SUPPORTED_TYPES = array(
		Acf_Factory::EMAIL,
		Acf_Factory::NUMBER,
		Acf_Factory::TEXT,
		Acf_Factory::TEXTAREA,
	);

	/**
	 * @var array
	 */
	private $field_structure = array();

	/**
	 * @var array
	 */
	private $data = array();


	public function __construct( array $field_structure, array $data ) {
		$this->field_structure = $field_structure;
		$this->data            = $this->format_data_according_structure( $data );
	}

	/**
	 * @return array
	 */
	public function get_field_structure(): array {
		return $this->field_structure;
	}

	/**
	 * @return array
	 */
	public function get_data(): array {
		return $this->data;
	}

	/**
	 * @return bool
	 */
	public static function is_acf_loaded(): bool {
		return class_exists( 'ACF' );
	}

	/**
	 * @param string $post_type Post type.
	 * @return bool
	 */
	public static function acf_exists_for_post_type( string $post_type ): bool {
		return self::is_acf_loaded() && ! empty( acf_get_field_groups( array( 'post_type' => $post_type ) ) );
	}

	/**
	 * @param array $data Data.
	 * @return array
	 */
	protected function format_data_according_structure( array $data ): array {
		if ( empty( $this->field_structure ) || empty( $data ) ) {
			return array();
		}

		$field_data = array();

		foreach ( $this->field_structure as $field_group ) {
			if ( empty( $field_group['fields'] ) ) {
				continue;
			}

			$field_title                = String_Transformation::camel_case( $field_group['title'] );
			$field_data[ $field_title ] = array();

			foreach ( $field_group['fields'] as $field ) {
				if ( ! array_key_exists( $field['name'], $data ) ) {
					continue;
				}

				if ( ! in_array( $field['type'], $this::ACF_SUPPORTED_TYPES, true ) ) {
					continue;
				}

				$value = $data[ $field['name'] ];

				if ( Json_Schema_Type::NUMBER === $field['type'] || Json_Schema_Type::INTEGER === $field['type'] ) {
					// check with regex if value is an integer.
					$value = preg_match( '/^-?\d+$/', $value ) ? (int) $value : (float) $value;
				}

				if ( '' === $value ) {
					$value = null;
				}

				$field_data[ $field_title ][ String_Transformation::camel_case( $field['name'], array( '_' ) ) ] = $value;
			}
		}

		return $field_data;
	}
}
