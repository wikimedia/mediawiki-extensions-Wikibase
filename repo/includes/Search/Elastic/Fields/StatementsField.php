<?php
namespace Wikibase\Repo\Search\Elastic\Fields;

use CirrusSearch;
use DataValues\BooleanValue;
use DataValues\DataValue;
use DataValues\DecimalValue;
use DataValues\MonolingualTextValue;
use DataValues\NumberValue;
use DataValues\QuantityValue;
use DataValues\StringValue;
use DataValues\UnboundedQuantityValue;
use SearchEngine;
use SearchIndexFieldDefinition;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\StatementListProvider;

/**
 * Field indexing statements for particular item.
 */
class StatementsField extends SearchIndexFieldDefinition implements WikibaseIndexField {

	/**
	 * String which separates property from value in statement representation.
	 * Should be the string that is:
	 * - Not part of property ID serialization
	 * - Regex-safe
	 */
	const STATEMENT_SEPARATOR = '=';

	/**
	 * List of properties to index
	 * @var string[]
	 */
	private $properties;

	public function __construct( $properties ) {
		$this->properties = $properties;
		parent::__construct( "", \SearchIndexField::INDEX_TYPE_KEYWORD );
	}

	/**
	 * Produce specific field mapping
	 *
	 * @param SearchEngine $engine
	 * @param string $name
	 *
	 * @return \SearchIndexField|null Null if mapping is not supported
	 */
	public function getMappingField( SearchEngine $engine, $name ) {
		if ( !( $engine instanceof CirrusSearch ) ) {
			// For now only Cirrus/Elastic is supported
			return null;
		}
		return $this;
	}

	/**
	 * Produce string representation for a value in order to index it.
	 * @param DataValue $value
	 * @return string|null String representation or null if we can't represent it
	 */
	private function getDataForIndex( DataValue $value ) {
		switch ( true ) {
			case ($value instanceof EntityIdValue):
				return $value->getEntityId()->getSerialization();
			case ($value instanceof StringValue):
				return $value->getValue();
			case ($value instanceof MonolingualTextValue):
				return $value->getText();
			case ($value instanceof UnboundedQuantityValue):
				// Strip leading +
				return ltrim( $value->getAmount()->getValue(), '+' );
			case ($value instanceof QuantityValue):
				// For now QuantityValue extends UnboundedQuantityValue, but this feels a bit
				// unnatural, so just in case it is changed, include both.
				// Strip leading +
				return ltrim( $value->getAmount()->getValue(), '+' );
			case ($value instanceof DecimalValue):
				// For decimal, getValue() returns string representation of value - what we need!
				return ltrim( $value->getValue(), '+' );
			case ($value instanceof NumberValue):
				return $value->getValue();
			case ($value instanceof BooleanValue):
				return $value->getValue() ? '1' : '0';
			default:
				// the rest is null
				return null;
		}
	}

	/**
	 * @param EntityDocument $entity
	 *
	 * @return mixed Get the value of the field to be indexed when a page/document
	 *               is indexed. This might be an array with nested data, if the field
	 *               is defined with nested type or an int or string for simple field types.
	 */
	public function getFieldData( EntityDocument $entity ) {
		if ( !( $entity instanceof StatementListProvider ) ) {
			return [];
		}
		$data = [];

		foreach ( $this->properties as $property ) {
			try {
				$id = new PropertyId( $property );
			} catch ( \Exception $e ) {
				// If we couldn't index this property, skip it
				continue;
			}
			foreach ( $entity->getStatements()->getByPropertyId( $id )->getMainSnaks() as $snak ) {
				if ( !( $snak instanceof PropertyValueSnak ) ) {
					// Won't index novalue/somevalue for now
					continue;
				}
				$value = $this->getDataForIndex( $snak->getDataValue() );
				if ( !$value ) {
					continue;
				}
				$data[] = $snak->getPropertyId()->getSerialization() . self::STATEMENT_SEPARATOR
				          . $value;
			}
		}

		return $data;
	}

	/**
	 * @param SearchEngine $engine
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
		];

		$config['fields']['property'] = [
			'type' => 'text',
			'analyzer' => "extract_property",
			'search_analyzer' => 'keyword',
		];

		return $config;
	}
}
