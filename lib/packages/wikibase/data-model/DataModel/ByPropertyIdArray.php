<?php

namespace Wikibase;

use OutOfBoundsException;
use RuntimeException;
use Wikibase\DataModel\Entity\PropertyId;

/**
 * Helper for doing indexed look-ups of objects by property id.
 *
 * This is a light weight alternative approach to using something
 * like GenericArrayObject with the advantages that no extra interface
 * is needed and that indexing does not happen automatically.
 *
 * Lack of automatic indexing means that you will need to call the
 * buildIndex method before doing any look-ups.
 *
 * Since no extra interface is used, the user is responsible for only
 * adding objects that have a getPropertyId method that returns either
 * a string or integer when called with no arguments.
 *
 * @since 0.2
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ByPropertyIdArray extends \ArrayObject {

	/**
	 * @since 0.2
	 *
	 * @var null|object[][]
	 */
	protected $byId = null;

	/**
	 * Builds the index for doing look-ups by property id.
	 *
	 * @since 0.2
	 */
	public function buildIndex() {
		$this->byId = array();

		foreach ( $this as $object ) {
			$propertyId = $object->getPropertyId()->getSerialization();

			if ( !array_key_exists( $propertyId, $this->byId ) ) {
				$this->byId[$propertyId] = array();
			}

			$this->byId[$propertyId][] = $object;
		}
	}

	/**
	 * Returns the property ids used for indexing.
	 *
	 * @since 0.2
	 *
	 * @return PropertyId[]
	 * @throws RuntimeException
	 */
	public function getPropertyIds() {
		if ( $this->byId === null ) {
			throw new RuntimeException( 'Index not build, call buildIndex first' );
		}

		return array_map(
			function( $serializedPropertyId ) {
				return new PropertyId( $serializedPropertyId );
			},
			array_keys( $this->byId )
		);
	}

	/**
	 * Returns the objects featuring the provided property id in the index.
	 *
	 * @since 0.2
	 *
	 * @param PropertyId $propertyId
	 *
	 * @return object[]
	 * @throws RuntimeException|OutOfBoundsException
	 */
	public function getByPropertyId( PropertyId $propertyId ) {
		if ( $this->byId === null ) {
			throw new RuntimeException( 'Index not build, call buildIndex first' );
		}

		if ( !( array_key_exists( $propertyId->getSerialization(), $this->byId ) ) ) {
			throw new OutOfBoundsException( 'Property id array key does not exist.' );
		}

		return $this->byId[$propertyId->getSerialization()];
	}

}
