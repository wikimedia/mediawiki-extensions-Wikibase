<?php

namespace Wikibase\Repo\Search\Elastic\FieldDefinitions;

class DescriptionsProviderFieldDefinitions implements FieldDefinitions {

	/**
	 * @var string[]
	 */
	private $languageCodes;

	/**
	 * @param string[] $languageCodes
	 */
	public function __construct( array $languageCodes ) {
		$this->languageCodes = $languageCodes;
	}

	/**
	 * @return array
	 */
	public function getFields() {
		$fields = [];

		// @todo add copy_to fields ([ 'all' ] in the case of CirrusSearch)
		foreach ( $this->languageCodes as $languageCode ) {
			$key = 'description_' . $languageCode;
			$fields[$key] = [
				'type' => 'string'
			];
		}

		return $fields;
	}

}
