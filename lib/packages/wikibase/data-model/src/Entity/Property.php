<?php

namespace Wikibase\DataModel\Entity;

use InvalidArgumentException;
use Wikibase\DataModel\Statement\StatementList;
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
	 * @var StatementList
	 */
	private $statements;

	/**
	 * @since 1.0
	 *
	 * @param PropertyId|null $id
	 * @param Fingerprint $fingerprint
	 * @param string $dataTypeId
	 * @param StatementList|null $statements Since 1.1
	 */
	public function __construct( PropertyId $id = null, Fingerprint $fingerprint, $dataTypeId, StatementList $statements = null ) {
		$this->id = $id;
		$this->fingerprint = $fingerprint;
		$this->setDataTypeId( $dataTypeId );
		$this->statements = $statements === null ? new StatementList() : $statements;
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
	 * @since 0.1 return type changed in 0.3
	 *
	 * @return PropertyId|null
	 */
	public function getId() {
		return $this->id;
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
			$dataTypeId,
			new StatementList()
		);
	}

	/**
	 * @see Comparable::equals
	 *
	 * Two items are considered equal if they are of the same
	 * type and have the same value.
	 *
	 * @since 0.1
	 *
	 * @param mixed $that
	 *
	 * @return boolean
	 */
	public function equals( $that ) {
		if ( $this === $that ) {
			return true;
		}

		if ( !( $that instanceof self )
			|| is_null( $this->id ) !== is_null( $that->id )
			|| ( $this->id !== null && !$this->id->equals( $that->id ) )
		) {
			return false;
		}

		return $this->dataTypeId == $that->dataTypeId
			&& $this->fingerprint->equals( $that->fingerprint )
			&& $this->statements->equals( $that->statements );
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
		return $this->fingerprint->isEmpty() && $this->statements->isEmpty();
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

	/**
	 * @deprecated since 0.7.3. Use Property::newFromType
	 *
	 * @return Property
	 */
	public static function newEmpty() {
		return self::newFromType( '' );
	}

	/**
	 * @since 1.1
	 *
	 * @return StatementList
	 */
	public function getStatements() {
		return $this->statements;
	}

	/**
	 * @since 1.1
	 *
	 * @param StatementList $statements
	 */
	public function setStatements( StatementList $statements ) {
		$this->statements = $statements;
	}

}
