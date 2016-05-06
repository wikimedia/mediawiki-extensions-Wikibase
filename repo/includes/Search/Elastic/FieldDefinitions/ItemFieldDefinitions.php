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
		$fields = array_merge(
			$this->labelsProviderFieldDefinitions->getFields(),
			$this->descriptionsProviderFieldDefinitions->getFields()
		);

		$fields['sitelink_count'] = [ 'type' => 'integer' ];
		$fields['statement_count'] = [ 'type' => 'integer' ];

		return $fields;
	}

}
