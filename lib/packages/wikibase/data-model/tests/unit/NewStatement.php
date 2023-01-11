<?php

namespace Wikibase\DataModel\Tests;

use DataValues\DataValue;
use DataValues\StringValue;
use InvalidArgumentException;
use LogicException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\ReferenceList;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Statement\Statement;

/**
 * Immutable Wikibase statement builder.
 *
 * @license GPL-2.0-or-later
 */
class NewStatement {

	private const GENERATE_GUID = true;

	/**
	 * @var NumericPropertyId
	 */
	private $propertyId;

	/**
	 * @var string|null
	 */
	private $type;

	/**
	 * @var DataValue|null
	 */
	private $dataValue;

	/**
	 * @var int
	 */
	private $rank = Statement::RANK_NORMAL;

	/** @var string|bool|null */
	private $guid;

	/**
	 * @var Snak[]
	 */
	private $qualifiers = [];

	/**
	 * @var Reference[]
	 */
	private $references = [];

	/**
	 * @param NumericPropertyId|string $propertyId
	 * @return static
	 */
	public static function forProperty( $propertyId ) {
		$result = new static();
		if ( is_string( $propertyId ) ) {
			$propertyId = new NumericPropertyId( $propertyId );
		}
		$result->propertyId = $propertyId;

		return $result;
	}

	/**
	 * @param NumericPropertyId|string $propertyId
	 * @return static
	 */
	public static function someValueFor( $propertyId ) {
		$result = static::forProperty( $propertyId );
		$result->type = PropertySomeValueSnak::class;

		return $result;
	}

	/**
	 * @param NumericPropertyId|string $propertyId
	 * @return static
	 */
	public static function noValueFor( $propertyId ) {
		$result = static::forProperty( $propertyId );
		$result->type = PropertyNoValueSnak::class;

		return $result;
	}

	/**
	 * @param DataValue|EntityId|string $dataValue If not a DataValue object, the builder tries to
	 *  guess the type and turns it into a DataValue object.
	 * @return static
	 */
	public function withValue( $dataValue ) {
		if ( $this->type !== null && $this->type !== PropertyValueSnak::class ) {
			throw new LogicException( "Statements of type {$this->type} must not have a value" );
		}

		$result = clone $this;

		$result->dataValue = $this->createDataValueObject( $dataValue );
		$result->type = PropertyValueSnak::class;

		return $result;
	}

	/**
	 * @param int $rank
	 * @return static
	 */
	public function withRank( $rank ) {
		$result = clone $this;

		$result->rank = $rank;

		return $result;
	}

	public function withDeprecatedRank() {
		return $this->withRank( Statement::RANK_DEPRECATED );
	}

	public function withNormalRank() {
		return $this->withRank( Statement::RANK_NORMAL );
	}

	public function withPreferredRank() {
		return $this->withRank( Statement::RANK_PREFERRED );
	}

	/**
	 * @param string $guid
	 * @return static
	 */
	public function withGuid( $guid ) {
		$result = clone $this;
		if ( $result->guid !== null ) {
			throw new LogicException( 'Cannot redefine GUID' );
		}

		$result->guid = (string)$guid;

		return $result;
	}

	/**
	 * @return static
	 */
	public function withSomeGuid() {
		$result = clone $this;
		if ( $result->guid !== null ) {
			throw new LogicException( 'Cannot redefine GUID' );
		}

		$result->guid = self::GENERATE_GUID;

		return $result;
	}

	/**
	 * @param string|NumericPropertyId $propertyId
	 * @param DataValue|EntityId|string $value If not a DataValue object, the builder tries to
	 *  guess the type and turns it into a DataValue object.
	 *
	 * @return static
	 */
	public function withQualifier( $propertyId, $value ) {
		$result = clone $this;
		if ( is_string( $propertyId ) ) {
			$propertyId = new NumericPropertyId( $propertyId );
		}

		$value = $this->createDataValueObject( $value );

		$result->qualifiers[] = new PropertyValueSnak( $propertyId, $value );

		return $result;
	}

	/**
	 * @param Reference $reference
	 * @return static
	 */
	public function withReference( Reference $reference ) {
		$result = clone $this;

		$result->references[] = $reference;

		return $result;
	}

	private function __construct() {
	}

	/**
	 * @return Statement
	 */
	public function build() {
		if ( !$this->type ) {
			$possibleTypes = [ PropertySomeValueSnak::class, PropertyNoValueSnak::class ];
			$type = $possibleTypes[array_rand( $possibleTypes )];
		} else {
			$type = $this->type;
		}

		switch ( $type ) {
			case PropertySomeValueSnak::class:
				$snack = new PropertySomeValueSnak( $this->propertyId );
				break;
			case PropertyNoValueSnak::class:
				$snack = new PropertyNoValueSnak( $this->propertyId );
				break;
			case PropertyValueSnak::class:
				$snack = new PropertyValueSnak( $this->propertyId, $this->dataValue );
				break;
			default:
				throw new LogicException( "Unknown statement type: '{$this->type}'" );
		}

		$result = new Statement( $snack );
		$result->setRank( $this->rank );

		if ( $this->guid === self::GENERATE_GUID ) {
			$result->setGuid(
				$this->propertyId->getSerialization() . '$' . $this->generateUuidV4()
			);
		} elseif ( $this->guid ) {
			$result->setGuid( $this->guid );
		}

		foreach ( $this->qualifiers as $qualifier ) {
			$result->getQualifiers()->addSnak( $qualifier );
		}

		$result->setReferences( new ReferenceList( $this->references ) );

		return $result;
	}

	private function generateUuidV4() {
		return sprintf(
			'%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
			mt_rand( 0, 0xffff ),
			mt_rand( 0, 0xffff ),
			mt_rand( 0, 0xffff ),
			mt_rand( 0, 0x0fff ) | 0x4000,
			mt_rand( 0, 0x3fff ) | 0x8000,
			mt_rand( 0, 0xffff ),
			mt_rand( 0, 0xffff ),
			mt_rand( 0, 0xffff )
		);
	}

	/**
	 * @param DataValue|EntityId|string $dataValue If not a DataValue object, the builder tries to
	 *  guess the type and turns it into a DataValue object.
	 * @return DataValue
	 */
	private function createDataValueObject( $dataValue ) {
		if ( $dataValue instanceof EntityId ) {
			$dataValue = new EntityIdValue( $dataValue );
		} elseif ( is_string( $dataValue ) ) {
			$dataValue = new StringValue( $dataValue );
		} elseif ( !( $dataValue instanceof DataValue ) ) {
			throw new InvalidArgumentException( 'Unsupported $dataValue type' );
		}

		return $dataValue;
	}

}
