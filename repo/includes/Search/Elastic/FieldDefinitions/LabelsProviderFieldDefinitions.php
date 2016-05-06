<?php

namespace Wikibase\Repo\Search\Elastic\FieldDefinitions;

use Wikibase\Repo\Search\FieldDefinitions\FieldDefinitions;

class LabelsProviderFieldDefinitions implements FieldDefinitions {

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
		$fields = $this->getLabelFields();
		$fields['label_count'] = [ 'type' => 'integer' ];

		return $fields;
	}

	/**
	 * @return array
	 */
	private function getLabelFields() {
		$fields = [];

		// @todo add copy_to here for 'all' and 'all_near_match', in case of CirrusSearch,
		// though make these fields configurable.
		foreach ( $this->languageCodes as $languageCode ) {
			$key = 'label_' . $languageCode;
			$fields[$key] = $this->termSearchFieldDefinition->getMapping();
		}

		return $fields;
	}

}
