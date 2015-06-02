<?php

namespace Wikibase\DataModel;

use Deserializers\Deserializer;
use Deserializers\DispatchableDeserializer;
use Deserializers\DispatchingDeserializer;
use Wikibase\DataModel\Deserializers\StatementDeserializer;
use Wikibase\DataModel\Deserializers\ClaimsDeserializer;
use Wikibase\DataModel\Deserializers\EntityIdDeserializer;
use Wikibase\DataModel\Deserializers\FingerprintDeserializer;
use Wikibase\DataModel\Deserializers\ItemDeserializer;
use Wikibase\DataModel\Deserializers\PropertyDeserializer;
use Wikibase\DataModel\Deserializers\ReferenceDeserializer;
use Wikibase\DataModel\Deserializers\ReferenceListDeserializer;
use Wikibase\DataModel\Deserializers\SiteLinkDeserializer;
use Wikibase\DataModel\Deserializers\SnakDeserializer;
use Wikibase\DataModel\Deserializers\SnakListDeserializer;
use Wikibase\DataModel\Deserializers\StatementListDeserializer;
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
			new ItemDeserializer( $this->newEntityIdDeserializer(), $this->newFingerprintDeserializer(), $this->newStatementListDeserializer(), $this->newSiteLinkDeserializer() ),
			new PropertyDeserializer( $this->newEntityIdDeserializer(), $this->newFingerprintDeserializer(), $this->newStatementListDeserializer() )
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

	/**
	 * Returns a Deserializer that can deserialize Claims objects.
	 *
	 * @return Deserializer
	 */
	public function newClaimsDeserializer() {
		return new ClaimsDeserializer( $this->newStatementDeserializer() );
	}

	/**
	 * Returns a Deserializer that can deserialize StatementList objects.
	 *
	 * @return Deserializer
	 */
	public function newStatementListDeserializer() {
		return new StatementListDeserializer( $this->newStatementDeserializer() );
	}

	/**
	 * Returns a Deserializer that can deserialize Statement objects.
	 *
	 * @return DispatchableDeserializer
	 */
	public function newStatementDeserializer() {
		return new StatementDeserializer(
			$this->newSnakDeserializer(),
			$this->newSnaksDeserializer(),
			$this->newReferencesDeserializer()
		);
	}

	/**
	 * b/c alias for newStatementDeserializer
	 *
	 * @deprecated since 1.4 - use newStatementDeserializer instead
	 * @return DispatchableDeserializer
	 */
	public function newClaimDeserializer() {
		return $this->newStatementDeserializer();
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
	 * Returns a Deserializer that can deserialize SnakList objects.
	 *
	 * @return Deserializer
	 */
	public function newSnakListDeserializer() {
		return new SnakListDeserializer( $this->newSnakDeserializer() );
	}

	/**
	 * b/c alias for newSnakListDeserializer
	 *
	 * @deprecated since 1.4 - use newSnakListDeserializer instead
	 * @return Deserializer
	 */
	public function newSnaksDeserializer() {
		return $this->newSnakListDeserializer();
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

	/**
	 * Returns a Deserializer that can deserialize Fingerprint objects.
	 *
	 * @return Deserializer
	 */
	public function newFingerprintDeserializer() {
		return new FingerprintDeserializer();
	}

}
