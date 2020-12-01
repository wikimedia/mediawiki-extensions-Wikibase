<?php

namespace Wikibase\Repo\Search\Fields;

/**
 * Class for empty field definitions
 * @license GPL-2.0-or-later
 */
class NoFieldDefinitions implements FieldDefinitions {

	/**
	 * Get the list of definitions
	 */
	public function getFields() {
		return [];
	}

}
