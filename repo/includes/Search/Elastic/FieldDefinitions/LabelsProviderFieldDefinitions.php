<?php

namespace Wikibase\Repo\Search\Elastic\FieldDefinitions;

class LabelsProviderFieldDefinitions implements FieldDefinitions {

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
			$fields[$key] = [
				'type' => 'string'
			];
		}

		return $fields;
	}

}
