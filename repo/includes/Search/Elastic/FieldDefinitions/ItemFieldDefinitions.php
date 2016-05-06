<?php

namespace Wikibase\Repo\Search\Elastic\FieldDefinitions;

class ItemFieldDefinitions implements FieldDefinitions {

	/**
	 * @var string[]
	 */
	private $languageCodes;

	/**
	 * @var LabelsProviderFieldDefinitions
	 */
	private $labelsProviderFieldDefinitions;

	/**
	 * @var DescriptionsProviderFieldDefinitions
	 */
	private $descriptionsProviderFieldDefinitions;

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
	public function getFields() {
		$properties = array_merge(
			$this->labelsProviderFieldDefinitions->getFields(),
			$this->descriptionsProviderFieldDefinitions->getFields()
		);

		$properties['sitelink_count'] = [ 'type' => 'integer' ];
		$properties['statement_count'] = [ 'type' => 'integer' ];

		return $properties;
	}

}
