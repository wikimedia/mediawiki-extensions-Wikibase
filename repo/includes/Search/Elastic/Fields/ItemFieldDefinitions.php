<?php

namespace Wikibase\Repo\Search\Elastic\Fields;

/**
 * Search fields that are used for items.
 *
 * @license GPL-2.0-or-later
 * @author Stas Malyshev
 */
class ItemFieldDefinitions implements FieldDefinitions {

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

		$fields[SiteLinkCountField::NAME] = new SiteLinkCountField();

		return $fields;
	}

}
