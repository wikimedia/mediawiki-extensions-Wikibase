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

	/**
	 * @var PropertyId
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
		$result->type = PropertySomeValueSnak::class;

		return $result;
	}

	/**
	 * @param PropertyId|string $propertyId
	 * @return self
	 */
	public static function noValueFor( $propertyId ) {
		$result = self::forProperty( $propertyId );
		$result->type = PropertyNoValueSnak::class;

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
		$result->type = PropertyValueSnak::class;

		return $result;
	}

	/**
	 * @param int $rank
	 * @return self
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
				throw new \LogicException( "Unknown statement type: '{$this->type}'" );
		}

		$result = new Statement( $snack );
		$result->setRank( $this->rank );

		return $result;
	}

}
