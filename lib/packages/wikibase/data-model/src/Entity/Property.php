<?php

namespace Wikibase\DataModel\Entity;

use InvalidArgumentException;
use Wikibase\DataModel\Term\Fingerprint;

/**
 * Represents a single Wikibase property.
 * See https://meta.wikimedia.org/wiki/Wikidata/Data_model#Properties
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class Property extends Entity {

	const ENTITY_TYPE = 'property';

	/**
	 * @var string
	 */
	private $dataTypeId;

	/**
	 * @since 1.0
	 *
	 * @param PropertyId|null $id
	 * @param Fingerprint $fingerprint
	 * @param string $dataTypeId
	 */
	public function __construct( PropertyId $id = null, Fingerprint $fingerprint, $dataTypeId ) {
		$this->id = $id;
		$this->fingerprint = $fingerprint;
		$this->setDataTypeId( $dataTypeId );
	}

	/**
	 * Can be integer since 0.1.
	 * Can be PropertyId since 0.5.
	 * Can be null since 1.0.
	 *
	 * @param PropertyId|int|null $id
	 *
	 * @throws InvalidArgumentException
	 */
	public function setId( $id ) {
		if ( $id === null || $id instanceof PropertyId ) {
			$this->id = $id;
		}
		else if ( is_integer( $id ) ) {
			$this->id = PropertyId::newFromNumber( $id );
		}
		else if ( $id instanceof EntityId ) {
			$this->id = new PropertyId( $id->getSerialization() );
		}
		else {
			throw new InvalidArgumentException( __METHOD__ . ' only accepts PropertyId, integer and null' );
		}
	}

	/**
	 * @since 0.4
	 *
	 * @param string $dataTypeId
	 *
	 * @throws InvalidArgumentException
	 */
	public function setDataTypeId( $dataTypeId ) {
		if ( !is_string( $dataTypeId ) ) {
			throw new InvalidArgumentException( '$dataTypeId needs to be a string' );
		}

		$this->dataTypeId = $dataTypeId;
	}

	/**
	 * @since 0.4
	 *
	 * @return string
	 */
	public function getDataTypeId() {
		return $this->dataTypeId;
	}

	/**
	 * @see Entity::getType
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getType() {
		return self::ENTITY_TYPE;
	}

	/**
	 * @since 0.3
	 *
	 * @param string $dataTypeId
	 *
	 * @return Property
	 */
	public static function newFromType( $dataTypeId ) {
		return new self(
			null,
			Fingerprint::newEmpty(),
			$dataTypeId
		);
	}

	/**
	 * @see Comparable::equals
	 *
	 * Two items are considered equal if they are of the same
	 * type and have the same value. The value does not include
	 * the id, so entities with the same value but different id
	 * are considered equal.
	 *
	 * @since 0.1
	 *
	 * @param mixed $that
	 *
	 * @return boolean
	 */
	public function equals( $that ) {
		if ( $that === $this ) {
			return true;
		}

		if ( !( $that instanceof self ) ) {
			return false;
		}

		return $this->fieldsEqual( $that );
	}

	private function fieldsEqual( Property $that ) {
		return ( $this->id === null && $that->id === null || $this->id->equals( $that->id ) )
			&& $this->fingerprint->equals( $that->fingerprint )
			&& $this->dataTypeId == $that->dataTypeId;
	}

	/**
	 * Returns if the Property has no content.
	 * Having an id and type set does not count as having content.
	 *
	 * @since 0.1
	 *
	 * @return boolean
	 */
	public function isEmpty() {
		return $this->fingerprint->isEmpty();
	}

	/**
	 * Removes all content from the Property.
	 * The id and the type are not part of the content.
	 *
	 * @since 0.1
	 */
	public function clear() {
		$this->fingerprint = Fingerprint::newEmpty();
	}

}
