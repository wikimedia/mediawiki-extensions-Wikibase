<?php

namespace Wikibase\Repo\Search\Elastic\FieldDefinitions;

class PropertyFieldDefinitions implements FieldDefinitions {

	/**
	 * @var LabelsProviderFieldDefinitions
	 */
	private $labelsProviderFieldDefinitions;

	/**
	 * @var DescriptionsProviderFieldDefinitions
	 */
	private $descriptionsProviderFieldDefinitions;

	/**
	 * @var string[]
	 */
	private $languageCodes;

	/**
	 * @param string[] $languageCodes
	 */
	public function __construct( array $languageCodes ) {
		$this->languageCodes = $languageCodes;

		$this->labelsProviderFieldDefinitions = new LabelsProviderFieldDefinitions(
			$languageCodes
		);

		$this->descriptionsProviderFieldDefinitions = new DescriptionsProviderFieldDefinitions(
			$languageCodes
		);
	}

	/**
	 * @return array
	 */
	public function getMappingProperties() {
		$properties = array_merge(
			$this->labelsProviderFieldDefinitions->getMappingProperties(),
			$this->descriptionsProviderFieldDefinitions->getMappingProperties()
		);

		$properties['statement_count'] = [ 'type' => 'integer' ];

		return $properties;
	}

}
