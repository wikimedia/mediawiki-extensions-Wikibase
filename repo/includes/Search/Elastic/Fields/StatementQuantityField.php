<?php

namespace Wikibase\Repo\Search\Elastic\Fields;

use CirrusSearch;
use DataValues\UnboundedQuantityValue;
use MWException;
use SearchEngine;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementListProvider;

/**
 * Additional field to index statements with their qualifiers and the qualifier value where the
 * qualifier is of type 'quantity'
 *
 * @license GPL-2.0-or-later
 */
class StatementQuantityField extends StatementsField implements WikibaseIndexField {

	/**
	 * Field name
	 */
	const NAME = 'statement_quantity';

	/**
	 * @var array
	 */
	private $allowedQualifierPropertyIds;

	/**
	 * @param PropertyDataTypeLookup $propertyDataTypeLookup
	 * @param string[] $propertyIds List of property IDs to index
	 * @param string[] $indexedTypes List of property types to index. Property of this type will be
	 *      indexed regardless of $propertyIds.
	 * @param string[] $excludedIds List of property IDs to exclude.
	 * @param callable[] $searchIndexDataFormatters Search formatters, indexed by data type name
	 * @param string[] $allowedQualifierPropertyIds Only index if the property id of the statement's
	 * 	qualifier is in this list
	 */
	public function __construct(
		PropertyDataTypeLookup $propertyDataTypeLookup,
		array $propertyIds,
		array $indexedTypes,
		array $excludedIds,
		array $searchIndexDataFormatters,
		array $allowedQualifierPropertyIds
	) {
		parent::__construct(
			$propertyDataTypeLookup,
			$propertyIds,
			$indexedTypes,
			$excludedIds,
			$searchIndexDataFormatters
		);
		$this->allowedQualifierPropertyIds = $allowedQualifierPropertyIds;
	}

	/**
	 * @param EntityDocument $entity
	 *
	 * @throws MWException
	 * @return mixed Get the value of the field to be indexed when a page/document
	 *               is indexed. This might be an array with nested data, if the field
	 *               is defined with nested type or an int or string for simple field types.
	 */
	public function getFieldData( EntityDocument $entity ) {
		if ( !( $entity instanceof StatementListProvider ) ) {
			return [];
		}

		$data = [];

		/** @var Statement $statement */
		foreach ( $entity->getStatements() as $statement ) {
			$snak = $statement->getMainSnak();
			$mainSnakString = $this->getWhitelistedSnakAsString( $snak, $statement->getGuid() );
			if ( is_null( $mainSnakString ) ) {
				continue;
			}
			foreach ( $statement->getQualifiers() as $qualifier ) {
				$propertyIdAndValue = $this->getSnakAsPropertyIdAndValue( $qualifier );
				if (
					!is_null( $propertyIdAndValue )
					&&
					$qualifier->getDataValue() instanceof UnboundedQuantityValue
					&&
					in_array( $propertyIdAndValue[ 'propertyId' ], $this->allowedQualifierPropertyIds )
				) {
						$data[] = $mainSnakString . '|' . $propertyIdAndValue[ 'value' ];
				}
			}
		}

		return $data;
	}

	/**
	 * @param SearchEngine $engine
	 *
	 * @return array
	 */
	public function getMapping( SearchEngine $engine ) {
		// Since we need a specially tuned field, we can not use
		// standard search engine types.
		if ( !( $engine instanceof CirrusSearch ) ) {
			// For now only Cirrus/Elastic is supported
			return [];
		}

		//NOTE: needs the cirrus search wikimedia-extra plugin to be installed to work
		$config = [
			'type' => 'text',
			'index_options' => 'freqs',
			'analyzer' => 'extract_wb_quantity',
			'search_analyzer' => 'keyword',
		];

		return $config;
	}

}
