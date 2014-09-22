<?php

namespace Wikibase\DataModel;

use Serializers\DispatchingSerializer;
use Serializers\Serializer;
use Wikibase\DataModel\Serializers\ClaimSerializer;
use Wikibase\DataModel\Serializers\ClaimsSerializer;
use Wikibase\DataModel\Serializers\FingerprintSerializer;
use Wikibase\DataModel\Serializers\ItemSerializer;
use Wikibase\DataModel\Serializers\PropertySerializer;
use Wikibase\DataModel\Serializers\ReferenceSerializer;
use Wikibase\DataModel\Serializers\ReferencesSerializer;
use Wikibase\DataModel\Serializers\SiteLinkSerializer;
use Wikibase\DataModel\Serializers\SnakSerializer;
use Wikibase\DataModel\Serializers\SnaksSerializer;
use InvalidArgumentException;

/**
 * Factory for constructing Serializer objects that can serialize WikibaseDataModel objects.
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
class SerializerFactory {

	const OPTION_DEFAULT = 0;
	const OPTION_OBJECTS_FOR_MAPS = 1;
	const OPTION_MAXIMUM_VALUE = 1;

	/**
	 * @var integer $options
	 */
	private $options = 0;

	/**
	 * @var Serializer
	 */
	private $dataValueSerializer;

	/**
	 * @param Serializer $dataValueSerializer serializer for DataValue objects
	 * @param integer $options set multiple with bitwise or
	 */
	public function __construct( Serializer $dataValueSerializer, $options = 0 ) {
		$this->dataValueSerializer = $dataValueSerializer;
		if ( $options > self::OPTION_MAXIMUM_VALUE ) {
			throw new InvalidArgumentException('The value of argument 2 $options must be less than 1.');
		}
		$this->options = $options;
	}

	/**
	 * @return bool
	 */
	private function shouldUseObjectsForMaps() {
		return (bool)( $this->options & self::OPTION_OBJECTS_FOR_MAPS );
	}

	/**
	 * Returns a Serializer that can serialize Entity objects.
	 *
	 * @return Serializer
	 */
	public function newEntitySerializer() {
		$fingerprintSerializer = new FingerprintSerializer( $this->shouldUseObjectsForMaps() );
		return new DispatchingSerializer( array(
			new ItemSerializer( $fingerprintSerializer, $this->newClaimsSerializer(), $this->newSiteLinkSerializer(), $this->shouldUseObjectsForMaps() ),
			new PropertySerializer( $fingerprintSerializer, $this->newClaimsSerializer() ),
		) );
	}

	/**
	 * Returns a Serializer that can serialize SiteLink objects.
	 *
	 * @return Serializer
	 */
	public function newSiteLinkSerializer() {
		return new SiteLinkSerializer();
	}

	/**
	 * Returns a Serializer that can serialize Claims objects.
	 *
	 * @return Serializer
	 */
	public function newClaimsSerializer() {
		return new ClaimsSerializer( $this->newClaimSerializer(), $this->shouldUseObjectsForMaps() );
	}

	/**
	 * Returns a Serializer that can serialize Claim objects.
	 *
	 * @return Serializer
	 */
	public function newClaimSerializer() {
		return new ClaimSerializer( $this->newSnakSerializer(), $this->newSnaksSerializer(), $this->newReferencesSerializer() );
	}

	/**
	 * Returns a Serializer that can serialize References objects.
	 *
	 * @return Serializer
	 */
	public function newReferencesSerializer() {
		return new ReferencesSerializer( $this->newReferenceSerializer() );
	}

	/**
	 * Returns a Serializer that can serialize Reference objects.
	 *
	 * @return Serializer
	 */
	public function newReferenceSerializer() {
		return new ReferenceSerializer( $this->newSnaksSerializer() );
	}

	/**
	 * Returns a Serializer that can serialize Snaks objects.
	 *
	 * @return Serializer
	 */
	public function newSnaksSerializer() {
		return new SnaksSerializer( $this->newSnakSerializer(), $this->shouldUseObjectsForMaps() );
	}

	/**
	 * Returns a Serializer that can serialize Snak objects.
	 *
	 * @return Serializer
	 */
	public function newSnakSerializer() {
		return new SnakSerializer( $this->dataValueSerializer );
	}

}
