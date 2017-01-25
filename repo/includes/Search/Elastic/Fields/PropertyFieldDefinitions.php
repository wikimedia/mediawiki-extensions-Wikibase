<?php
namespace Wikibase\Repo\Search\Elastic\Fields;

/**
 * Search fields that are used for properties.
 */
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
	 * @param LabelsProviderFieldDefinitions $labelsProviderFieldDefinitions
	 * @param DescriptionsProviderFieldDefinitions $descriptionsProviderFieldDefinitions
	 */
	public function __construct(
		LabelsProviderFieldDefinitions $labelsProviderFieldDefinitions,
		DescriptionsProviderFieldDefinitions $descriptionsProviderFieldDefinitions
	) {
		$this->labelsProviderFieldDefinitions = $labelsProviderFieldDefinitions;
		$this->descriptionsProviderFieldDefinitions = $descriptionsProviderFieldDefinitions;
	}

	/**
	 * @return SearchIndexField[]
	 */
	public function getFields() {
		/*
		 * Properties have:
		 * - labels
		 * - descriptions
		 * - statement count
		 */
		$fields = array_merge(
			$this->labelsProviderFieldDefinitions->getFields(),
			$this->descriptionsProviderFieldDefinitions->getFields()
		);

		$fields['statement_count'] = new StatementCountField();

		return $fields;
	}

}
