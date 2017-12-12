<?php

namespace Wikibase\Repo\Search\Elastic\Fields;

use CirrusSearch;
use MWException;
use SearchEngine;
use SearchIndexField;
use SearchIndexFieldDefinition;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementListProvider;

/**
 * Field indexing statements for particular item.
 *
 * @license GPL-2.0+
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
	private $propertyIds;

	/**
	 * @var callable[]
	 */
	private $searchIndexDataFormatters;

	/**
	 * @param string[] $propertyIds
	 * @param callable[] $searchIndexDataFormatters
	 */
	public function __construct( array $propertyIds, array $searchIndexDataFormatters ) {
		parent::__construct( static::NAME, SearchIndexField::INDEX_TYPE_KEYWORD );

		$this->propertyIds = array_flip( $propertyIds );
		$this->searchIndexDataFormatters = $searchIndexDataFormatters;
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
			if ( !( $snak instanceof PropertyValueSnak ) ) {
				// Won't index novalue/somevalue for now
				continue;
			}

			$propertyId = $snak->getPropertyId()->getSerialization();
			if ( !array_key_exists( $propertyId, $this->propertyIds ) ) {
				continue;
			}

			$dataValue = $snak->getDataValue();
			$definitionKey = 'VT:' . $dataValue->getType();

			if ( !isset( $this->searchIndexDataFormatters[$definitionKey] ) ) {
				// We do not know how to format these values
				continue;
			}

			$formatter = $this->searchIndexDataFormatters[$definitionKey];
			$value = $formatter( $dataValue );

			if ( !is_string( $value ) ) {
				throw new MWException( 'Search index data formatter callback for "' . $definitionKey
					. '" didn\'t return a string' );
			} elseif ( $value === '' ) {
				continue;
			}

			$data[] = $propertyId . self::STATEMENT_SEPARATOR . $value;
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
