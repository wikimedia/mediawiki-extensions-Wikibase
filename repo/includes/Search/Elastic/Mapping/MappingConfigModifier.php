<?php

namespace Wikibase\Repo\Search\Elastic\Mapping;

use Wikibase\Repo\Search\Elastic\FieldDefinitions\FieldDefinitions;

class MappingConfigModifier {

	/**
	 * @var FieldDefinitions[]
	 */
	private $fieldDefinitions;

	public function __construct( array $fieldDefinitions ) {
		$this->fieldDefinitions = $fieldDefinitions;
	}

	/**
	 * @param array &$fields
	 */
	public function addFields( array &$fields ) {
		foreach ( $this->fieldDefinitions as $fieldDefinitions ) {
			$this->addFieldsForFieldDefinitions( $fieldDefinitions, $fields );
		}
	}

	/**
	 * @param FieldDefinitions $fieldDefinitions
	 * @param array &$fields
	 */
	private function addFieldsForFieldDefinitions(
		FieldDefinitions $fieldDefinitions,
		array &$fields
	) {
		$fieldsToAdd = $this->getFieldsToAdd( $fieldDefinitions, $fields );

		foreach ( $fieldsToAdd as $key => $field ) {
			$fields[$key] = $field;
		}
	}

	/**
	 * @param FieldDefinitions $fieldDefinitions
	 * @param array $fields
	 *
	 * @return array
	 */
	private function getFieldsToAdd( FieldDefinitions $fieldDefinitions, array $fields ) {
		return array_diff_key(
			$fieldDefinitions->getFields(),
			$fields
		);
	}

}
