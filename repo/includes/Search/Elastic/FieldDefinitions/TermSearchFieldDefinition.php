<?php

namespace Wikibase\Repo\Search\Elastic\FieldDefinitions;

class TermSearchFieldDefinition {

	/**
	 * @return array
	 */
	public function getMapping() {
		return [
			'type' => 'string'
		];
	}

}
