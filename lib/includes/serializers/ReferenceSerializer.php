<?php

namespace Wikibase\Lib\Serializers;

use InvalidArgumentException;
use OutOfBoundsException;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Snak\SnakList;

/**
 * Serializer for Reference objects.
 *
 * See docs/json.wiki for details of the format.
 *
 * @since 0.3
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
class ReferenceSerializer extends SerializerObject implements Unserializer {

	/**
	 * @var SnakSerializer
	 */
	protected $snakSerializer;

	/**
	 * @param SnakSerializer $snakSerializer
	 * @param SerializationOptions $options
	 */
	public function __construct( SnakSerializer $snakSerializer, SerializationOptions $options = null ) {
		parent::__construct( $options );

		$this->snakSerializer = $snakSerializer;
	}

	/**
	 * @see ApiSerializer::getSerialized
	 *
	 * @since 0.3
	 *
	 * @param mixed $reference
	 *
	 * @return array
	 * @throws InvalidArgumentException
	 */
	public function getSerialized( $reference ) {
		if ( !( $reference instanceof Reference ) ) {
			throw new InvalidArgumentException( 'ReferenceSerializer can only serialize Reference objects' );
		}

		//NOTE: when changing the serialization structure, update docs/json.wiki too!

		$serialization = array();

		$clonedReference = clone $reference;
		$serialization['hash'] = $reference->getHash();

		if( in_array( 'references', $this->options->getOption( SerializationOptions::OPT_GROUP_BY_PROPERTIES ) ) ){
			$listSerializer = new ByPropertyListSerializer( 'snak', $this->snakSerializer, $this->options );
		} else {
			$listSerializer = new ListSerializer( 'snak', $this->snakSerializer, $this->options );
		}

		$snaks = $clonedReference->getSnaks();
		$serialization['snaks'] = $listSerializer->getSerialized( $snaks );

		$serialization['snaks-order'] = array();
		/** @var Snak $snak */
		foreach( $snaks as $snak ) {
			$id = $snak->getPropertyId()->getPrefixedId();
			if( !in_array( $id, $serialization['snaks-order'] ) ) {
				$serialization['snaks-order'][] = $id;
			}
		}
		$this->setIndexedTagName( $serialization['snaks-order'], 'property' );

		return $serialization;
	}

	/**
	 * @see Unserializer::newFromSerialization
	 *
	 * @since 0.4
	 *
	 * @param array $serialization
	 *
	 * @return Reference
	 * @throws InvalidArgumentException
	 * @throws OutOfBoundsException
	 */
	public function newFromSerialization( array $serialization ) {

		if ( !array_key_exists( 'snaks', $serialization ) || !is_array( $serialization['snaks'] ) ) {
			throw new InvalidArgumentException( 'A reference serialization needs to have a list of snaks' );
		}

		$snakUnserializer = new SnakSerializer( null, $this->options );

		if( $this->isAssociative( $serialization['snaks'] ) ){
			$unserializer = new ByPropertyListUnserializer( $snakUnserializer );
		} else {
			$unserializer = new ListUnserializer( $snakUnserializer );
		}

		$snakList = new SnakList( $unserializer->newFromSerialization( $serialization['snaks'] ) );

		if( array_key_exists( 'snaks-order', $serialization ) ) {
			$snakList->orderByProperty( $serialization['snaks-order'] );
		}

		$reference = new Reference( new SnakList( $snakList ) );

		return $reference;
	}

}
