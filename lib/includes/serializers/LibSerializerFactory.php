<?php

namespace Wikibase\Lib\Serializers;

use InvalidArgumentException;
use OutOfBoundsException;
use SiteStore;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Claim\Claims;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyDataTypeLookup;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\Snak\Snak;
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
	public $entityFactory = null;

	/**
	 * @var SiteStore|null
	 */
	public $siteStore = null;

	/**
	 * @var PropertyDataTypeLookup|null
	 */
	protected $dataTypeLookup = null;

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
	 * @param mixed $object
	 * @param SerializationOptions $options
	 *
	 * @return Serializer
	 * @throws OutOfBoundsException
	 * @throws InvalidArgumentException
	 */
	public function newSerializerForObject( $object, $options = null ) {
		if ( !is_object( $object ) ) {
			throw new InvalidArgumentException( 'newSerializerForObject only accepts objects and got ' . gettype( $object ) );
		}

		//TODO: The factory should take options in the constructor.
		//TODO: The factory should offer clones of the options via newSerializationOptions().
		//TODO: This method should merge to options given with the options from the constructor.

		if ( $options == null ) {
			$options = new SerializationOptions();
		}

		switch ( true ) {
			case ( $object instanceof Snak ):
				return $this->newSnakSerializer( $options );
			case ( $object instanceof Reference ):
				return $this->newReferenceSerializer( $options );
			case ( $object instanceof Item ):
				return $this->newItemSerializer( $options );
			case ( $object instanceof Property ):
				return $this->newPropertySerializer( $options );
			//TODO: support extra entity types!
			case ( $object instanceof Claim ):
				return $this->newClaimSerializer( $options );
			case ( $object instanceof Claims ):
				return $this->newClaimsSerializer( $options );
		}

		throw new OutOfBoundsException( 'There is no serializer for the provided type of object "' . get_class( $object ) . '"' );
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
	public function newReferenceSerializer( SerializationOptions $options ) {
		return new ReferenceSerializer( $this->newSnakSerializer( $options ), $options );
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
	public function newItemSerializer( SerializationOptions $options ) {
		return new ItemSerializer( $this->newClaimSerializer( $options ), $this->siteStore, $options, $this->entityFactory );
	}

	/**
	 * @param SerializationOptions $options
	 *
	 * @return Serializer
	 */
	public function newPropertySerializer( SerializationOptions $options ) {
		return new PropertySerializer( $this->newClaimSerializer( $options ), $options, $this->entityFactory );
	}

	/**
	 * @param SerializationOptions $options
	 *
	 * @return Serializer
	 */
	public function newAliasSerializer( SerializationOptions $options ) {
		return new AliasSerializer( $this->makeOptions( $options ) );
	}

	/**
	 * @param SerializationOptions $options
	 *
	 * @return Unserializer
	 */
	public function newReferenceUnserializer( SerializationOptions $options ) {
		return $this->newReferenceSerializer( $this->makeOptions( $options ) );
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
	public function newItemUnserializer( SerializationOptions $options ) {
		return $this->newItemSerializer( $this->makeOptions( $options ) );
	}

	/**
	 * @param SerializationOptions $options
	 *
	 * @return Unserializer
	 */
	public function newPropertyUnserializer( SerializationOptions $options ) {
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
	protected function makeOptions( SerializationOptions $options = null ) {
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
