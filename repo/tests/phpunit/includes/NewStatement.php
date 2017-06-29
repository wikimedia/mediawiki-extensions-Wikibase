<?php

namespace Wikibase\Repo\Tests;

use DataValues\DataValue;
use DataValues\StringValue;
use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\Statement;

class NewStatement {

	const TYPE_SOME_VALUE = 'some-value';
	const TYPE_NO_VALUE = 'no-value';
	const TYPE_PROPERTY_VALUE = 'property-value';
	/**
	 * @var PropertyId
	 */
	private $propertyId;

	/**
	 * @var string
	 */
	private $type;

	/**
	 * @var DataValue
	 */
	private $dataValue;

	/**
	 * @param PropertyId|string $propertyId
	 * @return self
	 */
	public static function forProperty( $propertyId ) {
		$result = new self();
		if ( is_string( $propertyId ) ) {
			$propertyId = new PropertyId( $propertyId );
		}
		$result->propertyId = $propertyId;

		return $result;
	}

	/**
	 * @param PropertyId|string $propertyId
	 * @return self
	 */
	public static function someValueFor( $propertyId ) {
		$result = self::forProperty( $propertyId );
		$result->type = self::TYPE_SOME_VALUE;

		return $result;
	}

	/**
	 * @param PropertyId|string $propertyId
	 * @return self
	 */
	public static function noValueFor( $propertyId ) {
		$result = self::forProperty( $propertyId );
		$result->type = self::TYPE_NO_VALUE;

		return $result;
	}

	/**
	 * @param DataValue|EntityId|string $dataValue If not a DataValue object, the builder tries to
	 *  guess the type and turns it into a DataValue object.
	 * @return self
	 */
	public function withValue( $dataValue ) {
		$result = clone $this;

		if ( $dataValue instanceof EntityId ) {
			$dataValue = new EntityIdValue( $dataValue );
		} elseif ( is_string( $dataValue ) ) {
			$dataValue = new StringValue( $dataValue );
		} elseif ( !( $dataValue instanceof DataValue ) ) {
			throw new InvalidArgumentException( 'Unsupported $dataValue type' );
		}

		$result->dataValue = $dataValue;
		$result->type = self::TYPE_PROPERTY_VALUE;

		return $result;
	}

	private function __construct() {
	}

	/**
	 * @return Statement
	 */
	public function build() {
		if ( !$this->type ) {
			$possibleTypes = [ self::TYPE_SOME_VALUE, self::TYPE_NO_VALUE ];
			$type = $possibleTypes[array_rand( $possibleTypes )];
		} else {
			$type = $this->type;
		}

		$snack = null;
		switch ( $type ) {
			case self::TYPE_SOME_VALUE:
				$snack = new PropertySomeValueSnak( $this->propertyId );
				break;
			case self::TYPE_NO_VALUE:
				$snack = new PropertyNoValueSnak( $this->propertyId );
				break;
			case self::TYPE_PROPERTY_VALUE:
				$snack = new PropertyValueSnak( $this->propertyId, $this->dataValue );
				break;
			default:
				throw new \LogicException( "Unknown statement type: '{$this->type}'" );
		}

		return new Statement( $snack );
	}

}
