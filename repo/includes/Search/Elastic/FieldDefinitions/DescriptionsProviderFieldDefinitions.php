<?php

namespace Wikibase\Repo\Search\Elastic\FieldDefinitions;

class DescriptionsProviderFieldDefinitions implements FieldDefinitions {

	/**
	 * @var TermSearchFieldDefinition
	 */
	private $termSearchFieldDefinition;

	/**
	 * @var string[]
	 */
	private $languageCodes;

	/**
	 * @param TermSearchFieldDefinition $termSearchFieldDefinition
	 * @param string[] $languageCodes
	 */
	public function __construct(
		TermSearchFieldDefinition $termSearchFieldDefinition,
		array $languageCodes
	) {
		$this->termSearchFieldDefinition = $termSearchFieldDefinition;
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
			$fields[$key] = $this->termSearchFieldDefinition->getMapping();
		}

		return $fields;
	}

}
