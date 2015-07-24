<?php

namespace Wikibase\Lib\Serializers;

use InvalidArgumentException;
use SiteStore;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyDataTypeLookup;
use Wikibase\EntityFactory;

/**
 * Factory for constructing Serializer and Unserializer objects.
 *
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 *
 * @todo: allow this to be obtained from WikibaseRepo resp. WikibaseClient
 */
class LibSerializerFactory {

	/**
	 * @var EntityFactory|null
	 */
	private $entityFactory = null;

	/**
	 * @var SiteStore|null
	 */
	private $siteStore = null;

	/**
	 * @var PropertyDataTypeLookup|null
	 */
	private $dataTypeLookup = null;

	/**
	 * @param SerializationOptions $defaultOptions
	 * @param PropertyDataTypeLookup $dataTypeLookup
	 * @param EntityFactory $entityFactory
	 * @param SiteStore $siteStore
	 *
	 * @todo: injecting the services should be required
	 */
	public function __construct(
		SerializationOptions $defaultOptions = null,
		PropertyDataTypeLookup $dataTypeLookup = null,
		EntityFactory $entityFactory = null,
		SiteStore $siteStore = null
	) {
		if ( $siteStore === null ) {
			$siteStore = \SiteSQLStore::newInstance();
		}

		$this->defaultOptions = $defaultOptions;
		$this->dataTypeLookup = $dataTypeLookup;
		$this->entityFactory = $entityFactory;
		$this->siteStore = $siteStore;
	}

	/**
	 * @param string $entityType
	 * @param SerializationOptions $options
	 *
	 * @throws InvalidArgumentException
	 * @return Serializer
	 */
	public function newSerializerForEntity( $entityType, $options ) {
		switch( $entityType ) {
			case Item::ENTITY_TYPE:
				return $this->newItemSerializer( $options );
			case Property::ENTITY_TYPE:
				return $this->newPropertySerializer( $options );
			//TODO: support extra entity types!
			default:
				throw new InvalidArgumentException( '$entityType is invalid' );
		}
	}

	/**
	 * @param string $entityType
	 * @param SerializationOptions $options
	 *
	 * @throws InvalidArgumentException
	 * @return Unserializer
	 */
	public function newUnserializerForEntity( $entityType, $options ) {
		switch( $entityType ) {
			case Item::ENTITY_TYPE:
				return $this->newItemUnserializer( $options );
			case Property::ENTITY_TYPE:
				return $this->newPropertyUnserializer( $options );
			//TODO: support extra entity types!
			default:
				throw new InvalidArgumentException( '$entityType is invalid' );
		}
	}

	/**
	 * @param SerializationOptions $options
	 *
	 * @return Serializer
	 */
	public function newSnakSerializer( SerializationOptions $options ) {
		return new SnakSerializer( $this->dataTypeLookup, $options );
	}

	/**
	 * @param SerializationOptions $options
	 *
	 * @return Serializer
	 */
	public function newClaimSerializer( SerializationOptions $options ) {
		return new ClaimSerializer( $this->newSnakSerializer( $options ), $options );
	}

	/**
	 * @param SerializationOptions $options
	 *
	 * @return Serializer
	 */
	public function newClaimsSerializer( SerializationOptions $options ) {
		return new ClaimsSerializer( $this->newClaimSerializer( $options ), $options );
	}

	/**
	 * @param SerializationOptions $options
	 *
	 * @return Serializer
	 */
	private function newItemSerializer( SerializationOptions $options ) {
		return new ItemSerializer( $this->newClaimSerializer( $options ), $this->siteStore, $options, $this->entityFactory );
	}

	/**
	 * @param SerializationOptions $options
	 *
	 * @return Serializer
	 */
	private function newPropertySerializer( SerializationOptions $options ) {
		return new PropertySerializer( $this->newClaimSerializer( $options ), $options, $this->entityFactory );
	}

	/**
	 * @param SerializationOptions $options
	 *
	 * @return Unserializer
	 */
	public function newClaimUnserializer( SerializationOptions $options ) {
		return $this->newClaimSerializer( $this->makeOptions( $options ) );
	}

	/**
	 * @param SerializationOptions $options
	 *
	 * @return Unserializer
	 */
	private function newItemUnserializer( SerializationOptions $options ) {
		return $this->newItemSerializer( $this->makeOptions( $options ) );
	}

	/**
	 * @param SerializationOptions $options
	 *
	 * @return Unserializer
	 */
	private function newPropertyUnserializer( SerializationOptions $options ) {
		return $this->newPropertySerializer( $this->makeOptions( $options ) );
	}

	/**
	 * Returns an options object that combines the options in $options
	 * and the $defaultOptions provided to the constructor.
	 *
	 * @param SerializationOptions $options
	 *
	 * @return null|SerializationOptions
	 */
	private function makeOptions( SerializationOptions $options = null ) {
		if ( $options === null && $this->defaultOptions === null ) {
			return new SerializationOptions();
		}

		if ( $this->defaultOptions === null ) {
			return $options;
		}

		if ( $options === null ) {
			return clone $this->defaultOptions;
		}

		$mergedOptions = new SerializationOptions();
		$mergedOptions->merge( $this->defaultOptions );
		$mergedOptions->merge( $options );

		return $mergedOptions;
	}

}
