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
	public function getMappingProperties() {
		$properties = $this->getLabelProperties();
		$properties['label_count'] = [ 'type' => 'integer' ];

		return $properties;
	}

	/**
	 * @return array
	 */
	private function getLabelProperties() {
		$properties = [];

		foreach ( $this->languageCodes as $languageCode ) {
			$key = 'label_' . $languageCode;
			$properties[$key] = [
				'type' => 'string',
				'copy_to' => [ 'all', 'all_near_match' ]
			];
		}

		return $properties;
	}

}
