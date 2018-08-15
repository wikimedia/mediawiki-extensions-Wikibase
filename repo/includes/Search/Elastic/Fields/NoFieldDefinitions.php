<?php
namespace Wikibase\Repo\Search\Elastic\Fields;

/**
 * Class for empty field definitions
 */
class NoFieldDefinitions implements FieldDefinitions {

	/**
	 * Get the list of definitions
	 */
	public function getFields() {
		return [];
	}

}
