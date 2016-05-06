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
		$fields = $this->getLabelProperties();
		$fields['label_count'] = [ 'type' => 'integer' ];

		return $fields;
	}

	/**
	 * @return array
	 */
	private function getLabelProperties() {
		$fields = [];

		foreach ( $this->languageCodes as $languageCode ) {
			$key = 'label_' . $languageCode;
			$fields[$key] = [
				'type' => 'string',
				'copy_to' => [ 'all', 'all_near_match' ]
			];
		}

		return $fields;
	}

}
