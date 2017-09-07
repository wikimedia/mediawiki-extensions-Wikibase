<?php
namespace Wikibase\Repo\Search\Elastic\Fields;

/**
 * Search fields that are used for items.
 */
class ItemFieldDefinitions implements FieldDefinitions {

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
		 * Items have:
		 * - labels
		 * - descriptions
		 * - link count
		 * - statement count
		 */
		$fields = array_merge(
			$this->labelsProviderFieldDefinitions->getFields(),
			$this->descriptionsProviderFieldDefinitions->getFields(),
			$this->statementProviderFieldDefinitions->getFields()
		);

		$fields['sitelink_count'] = new SiteLinkCountField();

		return $fields;
	}

}
