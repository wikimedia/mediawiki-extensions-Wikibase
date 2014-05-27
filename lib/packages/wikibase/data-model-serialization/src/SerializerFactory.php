<?php

namespace Wikibase\DataModel;

use Serializers\DispatchingSerializer;
use Serializers\Serializer;
use Wikibase\DataModel\Serializers\ClaimSerializer;
use Wikibase\DataModel\Serializers\ClaimsSerializer;
use Wikibase\DataModel\Serializers\ItemSerializer;
use Wikibase\DataModel\Serializers\PropertySerializer;
use Wikibase\DataModel\Serializers\ReferenceSerializer;
use Wikibase\DataModel\Serializers\ReferencesSerializer;
use Wikibase\DataModel\Serializers\SiteLinkSerializer;
use Wikibase\DataModel\Serializers\SnakSerializer;
use Wikibase\DataModel\Serializers\SnaksSerializer;

/**
 * Factory for constructing Serializer objects that can serialize WikibaseDataModel objects.
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
class SerializerFactory {

	/**
	 * @var Serializer
	 */
	private $dataValueSerializer;

	/**
	 * @param Serializer $dataValueSerializer serializer for DataValue objects
	 */
	public function __construct( Serializer $dataValueSerializer ) {
		$this->dataValueSerializer = $dataValueSerializer;
	}

	/**
	 * Returns a Serializer that can serialize Entity objects.
	 *
	 * @return Serializer
	 */
	public function newEntitySerializer() {
		return new DispatchingSerializer( array(
			new ItemSerializer( $this->newClaimsSerializer(), $this->newSiteLinkSerializer() ),
			new PropertySerializer( $this->newClaimsSerializer() ),
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
		return new ClaimsSerializer( $this->newClaimSerializer() );
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
		return new SnaksSerializer( $this->newSnakSerializer() );
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