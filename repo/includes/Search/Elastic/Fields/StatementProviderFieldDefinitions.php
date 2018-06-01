<?php

namespace Wikibase\Repo\Search\Elastic\Fields;

use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;

/**
 * Fields for an object that has statements.
 *
 * @license GPL-2.0-or-later
 * @author Stas Malyshev
 */
class StatementProviderFieldDefinitions implements FieldDefinitions {

	/**
	 * List of properties to index.
	 * @var string[]
	 */
	private $propertyIds;

	/**
	 * @var callable[]
	 */
	private $searchIndexDataFormatters;
	/**
	 * @var PropertyDataTypeLookup
	 */
	private $propertyDataTypeLookup;
	/**
	 * @var array
	 */
	private $indexedTypes;
	/**
	 * @var array
	 */
	private $excludedIds;

	public function __construct(
		PropertyDataTypeLookup $propertyDataTypeLookup,
		array $propertyIds,
		array $indexedTypes,
		array $excludedIds,
		array $searchIndexDataFormatters
	) {
		$this->propertyIds = $propertyIds;
		$this->searchIndexDataFormatters = $searchIndexDataFormatters;
		$this->propertyDataTypeLookup = $propertyDataTypeLookup;
		$this->indexedTypes = $indexedTypes;
		$this->excludedIds = $excludedIds;
	}

	/**
	 * Get the list of definitions
	 * @return WikibaseIndexField[] key is field name, value is WikibaseIndexField
	 */
	public function getFields() {
		return [
			StatementsField::NAME => new StatementsField(
				$this->propertyDataTypeLookup,
				$this->propertyIds,
				$this->indexedTypes,
				$this->excludedIds,
				$this->searchIndexDataFormatters
			),
			StatementCountField::NAME => new StatementCountField(),
			StatementQuantityField::NAME => new StatementQuantityField(
				$this->propertyDataTypeLookup,
				$this->propertyIds,
				$this->indexedTypes,
				$this->excludedIds,
				$this->searchIndexDataFormatters
			),
		];
	}

}
