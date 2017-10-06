<?php

namespace Wikibase\Repo\Search\Elastic\Fields;

/**
 * Search fields that are used for properties.
 */
class PropertyFieldDefinitions implements FieldDefinitions {

	/**
	 * @var FieldDefinitions
	 */
	private $labelsProviderFieldDefinitions;

	/**
	 * @var FieldDefinitions
	 */
	private $descriptionsProviderFieldDefinitions;

	/**
	 * @var FieldDefinitions
	 */
	private $statementProviderFieldDefinitions;

	public function __construct(
		FieldDefinitions $labelsProviderFieldDefinitions,
		FieldDefinitions $descriptionsProviderFieldDefinitions,
		FieldDefinitions $statementProviderFieldDefinitions
	) {
		$this->labelsProviderFieldDefinitions = $labelsProviderFieldDefinitions;
		$this->descriptionsProviderFieldDefinitions = $descriptionsProviderFieldDefinitions;
		$this->statementProviderFieldDefinitions = $statementProviderFieldDefinitions;
	}

	/**
	 * @return WikibaseIndexField[]
	 */
	public function getFields() {
		return array_merge(
			$this->labelsProviderFieldDefinitions->getFields(),
			$this->descriptionsProviderFieldDefinitions->getFields(),
			$this->statementProviderFieldDefinitions->getFields()
		);
	}

}
