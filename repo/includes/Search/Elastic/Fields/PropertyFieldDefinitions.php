<?php

namespace Wikibase\Repo\Search\Elastic\Fields;

/**
 * Search fields that are used for properties.
 *
 * @license GPL-2.0-or-later
 * @author Stas Malyshev
 */
class PropertyFieldDefinitions implements FieldDefinitions {

	/**
	 * @var FieldDefinitions[]
	 */
	private $fieldDefinitions;

	/**
	 * @param FieldDefinitions[] $fieldDefinitions
	 */
	public function __construct( array $fieldDefinitions ) {
		$this->fieldDefinitions = $fieldDefinitions;
	}

	/**
	 * @return WikibaseIndexField[]
	 */
	public function getFields() {
		$fields = [];

		foreach ( $this->fieldDefinitions as $definitions ) {
			$fields = array_merge( $fields, $definitions->getFields() );
		}

		return $fields;
	}

}
