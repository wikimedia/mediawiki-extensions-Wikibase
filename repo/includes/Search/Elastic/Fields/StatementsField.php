<?php

namespace Wikibase\Repo\Search\Elastic\Fields;

use CirrusSearch;
use MWException;
use SearchEngine;
use SearchIndexField;
use SearchIndexFieldDefinition;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookupException;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementListProvider;

/**
 * Field indexing statements for particular item.
 *
 * @license GPL-2.0-or-later
 * @author Stas Malyshev
 */
class StatementsField extends SearchIndexFieldDefinition implements WikibaseIndexField {

	/**
	 * Field name
	 */
	const NAME = 'statement_keywords';

	/**
	 * String which separates property from value in statement representation.
	 * Should be the string that is:
	 * - Not part of property ID serialization
	 * - Regex-safe
	 */
	const STATEMENT_SEPARATOR = '=';

	/**
	 * @var array List of properties to index, as a flipped array with the property IDs as keys.
	 */
	protected $propertyIds;

	/**
	 * @var string[]
	 */
	private $indexedTypes;

	/**
	 * @var callable[]
	 */
	protected $searchIndexDataFormatters;

	/**
	 * @var PropertyDataTypeLookup
	 */
	private $propertyDataTypeLookup;
	/**
	 * @var array
	 */
	private $excludedIds;

	/**
	 * @param PropertyDataTypeLookup $propertyDataTypeLookup
	 * @param string[] $propertyIds List of property IDs to index
	 * @param string[] $indexedTypes List of property types to index. Property of this type will be
	 *      indexed regardless of $propertyIds.
	 * @param string[] $excludedIds List of property IDs to exclude.
	 * @param callable[] $searchIndexDataFormatters Search formatters, indexed by data type name
	 */
	public function __construct(
		PropertyDataTypeLookup $propertyDataTypeLookup,
		array $propertyIds,
		array $indexedTypes,
		array $excludedIds,
		array $searchIndexDataFormatters
	) {
		parent::__construct( static::NAME, SearchIndexField::INDEX_TYPE_KEYWORD );

		$this->propertyIds = array_flip( $propertyIds );
		$this->indexedTypes = array_flip( $indexedTypes );
		$this->searchIndexDataFormatters = $searchIndexDataFormatters;
		$this->propertyDataTypeLookup = $propertyDataTypeLookup;
		$this->excludedIds = array_flip( $excludedIds );
	}

	/**
	 * Produce specific field mapping
	 *
	 * @param SearchEngine $engine
	 * @param string $name
	 *
	 * @return SearchIndexField|null Null if mapping is not supported
	 */
	public function getMappingField( SearchEngine $engine, $name ) {
		if ( !( $engine instanceof CirrusSearch ) ) {
			// For now only Cirrus/Elastic is supported
			return null;
		}

		return $this;
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
			if ( !is_null( $mainSnakString ) ) {
				$data[] = $mainSnakString;
				foreach ( $statement->getQualifiers() as $qualifier ) {
					$qualifierString = $this->getSnakAsString( $qualifier );
					if ( !is_null( $qualifierString ) ) {
						$data[] = $mainSnakString . '[' . $qualifierString . ']';
					}
				}
			}
		}

		return $data;
	}

	/**
	 * Return the snak as an array with keys propertyId and value
	 *
	 * e.g. [ 'propertyId' => 'P180', 'value' => 'Q999' ]
	 *
	 * @param Snak $snak
	 * @return array
	 * @throws MWException
	 */
	protected function getSnakAsPropertyIdAndValue( Snak $snak ) {
		if ( !( $this->snakHasKnownValue( $snak ) ) ) {
			return null;
		}

		$dataValue = $snak->getDataValue();
		$definitionKey = 'VT:' . $dataValue->getType();

		if ( !isset( $this->searchIndexDataFormatters[$definitionKey] ) ) {
			// We do not know how to format these values
			return null;
		}

		$formatter = $this->searchIndexDataFormatters[$definitionKey];
		$value = $formatter( $dataValue );

		if ( !is_string( $value ) ) {
			throw new MWException( 'Search index data formatter callback for "' . $definitionKey
								   . '" didn\'t return a string' );
		}
		if ( $value === '' ) {
			return null;
		}

		return [
			'propertyId' => $snak->getPropertyId()->getSerialization(),
			'value' => $value,
		];
	}

	protected function getSnakAsString( Snak $snak ) {
		$snakAsPropertyIdAndValue = $this->getSnakAsPropertyIdAndValue( $snak );
		if ( is_null( $snakAsPropertyIdAndValue ) ) {
			return null;
		}
		return $snakAsPropertyIdAndValue[ 'propertyId' ] . self::STATEMENT_SEPARATOR .
			   $snakAsPropertyIdAndValue[ 'value' ];
	}

	/**
	 * Return the snak in the format '<property id>=<value>' IF AND ONLY IF the property has been
	 * whitelisted or its type has been whitelisted, and it has not been specifically excluded
	 *
	 * e.g. P180=Q537, P240=1234567
	 *
	 * @param Snak $snak
	 * @param string $guid Statement GUID to which this snak belongs
	 * @return null|string
	 * @throws MWException
	 */
	protected function getWhitelistedSnakAsString( Snak $snak, $guid ) {
		if ( !( $this->snakHasKnownValue( $snak ) ) ) {
			return null;
		}

		$propertyId = $snak->getPropertyId()->getSerialization();
		if ( array_key_exists( $propertyId, $this->excludedIds ) ) {
			return null;
		}

		try {
			$propType = $this->propertyDataTypeLookup->getDataTypeIdForProperty( $snak->getPropertyId() );
		} catch ( PropertyDataTypeLookupException $e ) {
			// T198091: looks like occasionally we get weird fails on indexing
			// Log them but do not break indexing other data
			wfLogWarning( __METHOD__ . ': Failed to look up property ' . $e->getPropertyId() .
				' for ' . $guid );
			return null;
		}
		if ( !array_key_exists( $propType, $this->indexedTypes ) &&
			 !array_key_exists( $propertyId, $this->propertyIds ) ) {
			return null;
		}

		return $this->getSnakAsString( $snak );
	}

	/**
	 * Returns true if the snak has a known value - i.e. it is NOT a PropertyNoValueSnak or a
	 * 	PropertySomeValueSnak
	 *
	 * @param Snak $snak
	 * @return bool
	 */
	protected function snakHasKnownValue( Snak $snak ) {
		return ( $snak instanceof PropertyValueSnak );
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

		$config = [
			'type' => 'keyword',
			'ignore_above' => 255,
		];
		// Subfield indexing only property names, so we could do matches
		// like "property exists" without specifying the value.
		$config['fields']['property'] = [
			'type' => 'text',
			'analyzer' => 'extract_wb_property',
			'search_analyzer' => 'keyword',
		];

		return $config;
	}

}
