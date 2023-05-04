<?php

namespace Wpe_Content_Engine\Helper\Search\Config;

use Wpe_Content_Engine\Helper\String_Transformation;

abstract class Configurable {
	abstract public function get_config( array $existing_config ): array;

	/**
	 * @param string $model_name The name of the model i.e. post page rabbit zombie.
	 * @param string $field_name The name of the field i.e. title, content, acmField, acfField.
	 * @param array  $existing The existing search config for the selected model in the DB.
	 * @return array
	 */
	public function provide_config( string $model_name, string $field_name, array $existing ): array {
		if ( ! isset( $existing[ $model_name ][ $field_name ] ) ) {
			return $this->generate_field_config( true, 1 );
		}
		return $existing[ $model_name ][ $field_name ];
	}

	/**
	 * @param bool $searchable Set whether the field is searchable.
	 * @param int  $weight Set the weight of the search field.
	 * @return array
	 */
	public function generate_field_config( bool $searchable, int $weight ): array {
		return array(
			'searchable' => $searchable,
			'weight'     => $weight,
		);
	}

	public function get_acf_search_config( string $model_name, array $existing_config, array $current_config ): array {
		$acf_field_groups = \acf_get_field_groups( array( 'post_type' => $model_name ) );
		$result           = array();

		foreach ( $acf_field_groups as $key => $acf_field_group ) {
			if ( empty( $acf_field_group ) || ! $acf_field_group['active'] ) {
				continue;
			}
			$acf_fields = acf_get_fields( $acf_field_group );

			$title = String_Transformation::camel_case( $acf_field_group['title'] );
			foreach ( $acf_fields as $acf_field ) {
				$name                         = String_Transformation::camel_case( $acf_field['name'], array( '_' ) );
				$field_name_nested            = "{$title}.{$name}";
				$result[ $field_name_nested ] = $this->provide_config( $model_name, $field_name_nested, $existing_config );
			}
		}
		$current_config[ $model_name ] = array_merge( $current_config [ $model_name ], $result );
		return $current_config;
	}

}
