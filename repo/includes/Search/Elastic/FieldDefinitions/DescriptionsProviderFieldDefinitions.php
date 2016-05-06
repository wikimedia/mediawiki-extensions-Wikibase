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
		$properties = [];

		foreach ( $this->languageCodes as $languageCode ) {
			$key = 'description_' . $languageCode;
			$properties[$key] = [
				'type' => 'string',
				'copy_to' => [ 'all' ]
			];
		}

		return $properties;
	}

}
