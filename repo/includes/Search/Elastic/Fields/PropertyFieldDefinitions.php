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
	 * @var StatementProviderFieldDefinitions
	 */
	private $statementProviderFieldDefinitions;

	public function __construct(
		LabelsProviderFieldDefinitions $labelsProviderFieldDefinitions,
		DescriptionsProviderFieldDefinitions $descriptionsProviderFieldDefinitions,
		StatementProviderFieldDefinitions $statementProviderFieldDefinitions
	) {
		$this->labelsProviderFieldDefinitions = $labelsProviderFieldDefinitions;
		$this->descriptionsProviderFieldDefinitions = $descriptionsProviderFieldDefinitions;
		$this->statementProviderFieldDefinitions = $statementProviderFieldDefinitions;
	}

	/**
	 * @return WikibaseIndexField[]
	 */
	public function getFields() {
		/*
		 * Properties have:
		 * - labels
		 * - descriptions
		 * - statements
		 * - statement count
		 */
		$fields = array_merge(
			$this->labelsProviderFieldDefinitions->getFields(),
			$this->descriptionsProviderFieldDefinitions->getFields(),
			$this->statementProviderFieldDefinitions->getFields()
		);

		return $fields;
	}

}
