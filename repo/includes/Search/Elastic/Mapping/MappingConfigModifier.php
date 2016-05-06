<?php

namespace Wikibase\Repo\Search\Elastic\Mapping;

use Wikibase\Repo\Search\Elastic\FieldDefinitions\FieldDefinitions;

class MappingConfigModifier {

	/**
	 * @param FieldDefinitions[] $fieldDefinitions
	 * @param array &$properties
	 */
	public function addProperties( array $fieldDefinitions, array &$properties ) {
		foreach ( $fieldDefinitions as $fieldDefinition ) {
			$this->addFieldDefinitionsProperties( $fieldDefinition, $properties );
		}
	}

	/**
	 * @param FieldDefinitions $fieldDefinitions
	 * @param array &$properties
	 */
	private function addFieldDefinitionsProperties(
		FieldDefinitions $fieldDefinitions,
		array &$properties
	) {
		$propertiesToAdd = $this->getPropertiesToAdd( $fieldDefinitions, $properties );

		foreach ( $propertiesToAdd as $key => $property ) {
			$properties[$key] = $property;
		}
	}

	/**
	 * @param FieldDefinitions $fieldDefinitions
	 * @param array $properties
	 *
	 * @return array
	 */
	private function getPropertiesToAdd( FieldDefinitions $fieldDefinitions, array $properties ) {
		return array_diff_key(
			$fieldDefinitions->getMappingProperties(),
			$properties
		);
	}

}
