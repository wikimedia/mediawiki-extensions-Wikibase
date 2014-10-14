<?php

namespace Wikibase\DataModel;

use Deserializers\Deserializer;
use Deserializers\DispatchableDeserializer;
use Deserializers\DispatchingDeserializer;
use Wikibase\DataModel\Deserializers\ClaimDeserializer;
use Wikibase\DataModel\Deserializers\ClaimsDeserializer;
use Wikibase\DataModel\Deserializers\EntityIdDeserializer;
use Wikibase\DataModel\Deserializers\ItemDeserializer;
use Wikibase\DataModel\Deserializers\PropertyDeserializer;
use Wikibase\DataModel\Deserializers\ReferenceDeserializer;
use Wikibase\DataModel\Deserializers\ReferenceListDeserializer;
use Wikibase\DataModel\Deserializers\SiteLinkDeserializer;
use Wikibase\DataModel\Deserializers\SnakDeserializer;
use Wikibase\DataModel\Deserializers\SnakListDeserializer;
use Wikibase\DataModel\Entity\EntityIdParser;

/**
 * Factory for constructing Deserializer objects that can deserialize WikibaseDataModel objects.
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
class DeserializerFactory {

	/**
	 * @var Deserializer
	 */
	private $dataValueDeserializer;

	/**
	 * @var EntityIdParser
	 */
	private $entityIdParser;

	/**
	 * @param Deserializer $dataValueDeserializer deserializer for DataValue objects
	 * @param EntityIdParser $entityIdParser
	 */
	public function __construct( Deserializer $dataValueDeserializer, EntityIdParser $entityIdParser ) {
		$this->dataValueDeserializer = $dataValueDeserializer;
		$this->entityIdParser = $entityIdParser;
	}

	/**
	 * Returns a Deserializer that can deserialize Entity objects.
	 *
	 * @return DispatchableDeserializer
	 */
	public function newEntityDeserializer() {
		return new DispatchingDeserializer( array(
			new ItemDeserializer( $this->newEntityIdDeserializer(), $this->newClaimsDeserializer(), $this->newSiteLinkDeserializer() ),
			new PropertyDeserializer( $this->newEntityIdDeserializer(), $this->newClaimsDeserializer() )
		) );
	}

	/**
	 * Returns a Deserializer that can deserialize SiteLink objects.
	 *
	 * @return Deserializer
	 */
	public function newSiteLinkDeserializer() {
		return new SiteLinkDeserializer( $this->newEntityIdDeserializer() );
	}

	/*
	 * Returns a Deserializer that can deserialize Claims objects.
	 *
	 * @return Deserializer
	 */
	public function newClaimsDeserializer() {
		return new ClaimsDeserializer( $this->newClaimDeserializer() );
	}

	/*
	 * Returns a Deserializer that can deserialize Claim objects.
	 *
	 * @return DispatchableDeserializer
	 */
	public function newClaimDeserializer() {
		return new ClaimDeserializer(
			$this->newSnakDeserializer(),
			$this->newSnaksDeserializer(),
			$this->newReferencesDeserializer()
		);
	}

	/**
	 * Returns a Deserializer that can deserialize ReferenceList objects.
	 *
	 * @return Deserializer
	 */
	public function newReferencesDeserializer() {
		return new ReferenceListDeserializer( $this->newReferenceDeserializer() );
	}

	/**
	 * Returns a Deserializer that can deserialize Reference objects.
	 *
	 * @return Deserializer
	 */
	public function newReferenceDeserializer() {
		return new ReferenceDeserializer( $this->newSnaksDeserializer() );
	}

	/**
	 * Returns a Deserializer that can deserialize Snaks objects.
	 *
	 * @return Deserializer
	 */
	public function newSnaksDeserializer() {
		return new SnakListDeserializer( $this->newSnakDeserializer() );
	}

	/**
	 * Returns a Deserializer that can deserialize Snak objects.
	 *
	 * @return Deserializer
	 */
	public function newSnakDeserializer() {
		return new SnakDeserializer( $this->dataValueDeserializer, $this->newEntityIdDeserializer() );
	}

	/**
	 * Returns a Deserializer that can deserialize EntityId objects.
	 *
	 * @return Deserializer
	 */
	public function newEntityIdDeserializer() {
		return new EntityIdDeserializer( $this->entityIdParser );
	}

}
