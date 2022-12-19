<?php

namespace Wikibase\DataModel\Deserializers;

use Deserializers\Deserializer;
use Deserializers\DispatchingDeserializer;
use Wikibase\DataModel\Entity\EntityIdParser;

/**
 * Factory for constructing Deserializer objects that can deserialize WikibaseDataModel objects.
 *
 * @since 0.1
 *
 * @license GPL-2.0-or-later
 * @author Thomas Pellissier Tanon
 * @author Bene* < benestar.wikimedia@gmail.com >
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
	public function __construct(
		Deserializer $dataValueDeserializer,
		EntityIdParser $entityIdParser
	) {
		$this->dataValueDeserializer = $dataValueDeserializer;
		$this->entityIdParser = $entityIdParser;
	}

	/**
	 * @return DispatchingDeserializer A deserializer that can only deserialize Item and Property
	 *  objects, but no other entity types. In contexts with custom entity types other than items
	 *  and properties this is not what you want. If in doubt, favor a custom
	 *  `DispatchingDeserializer` containing the exact entity deserializers you need.
	 */
	public function newEntityDeserializer(): DispatchingDeserializer {
		return new DispatchingDeserializer( [
			$this->newItemDeserializer(),
			$this->newPropertyDeserializer(),
		] );
	}

	/**
	 * Returns a Deserializer that can deserialize Item objects.
	 *
	 * @since 2.1
	 */
	public function newItemDeserializer(): ItemDeserializer {
		return new ItemDeserializer(
			$this->newEntityIdDeserializer(),
			$this->newTermListDeserializer(),
			$this->newAliasGroupListDeserializer(),
			$this->newStatementListDeserializer(),
			$this->newSiteLinkDeserializer()
		);
	}

	/**
	 * Returns a Deserializer that can deserialize Property objects.
	 *
	 * @since 2.1
	 */
	public function newPropertyDeserializer(): PropertyDeserializer {
		return new PropertyDeserializer(
			$this->newEntityIdDeserializer(),
			$this->newTermListDeserializer(),
			$this->newAliasGroupListDeserializer(),
			$this->newStatementListDeserializer()
		);
	}

	/**
	 * Returns a Deserializer that can deserialize SiteLink objects.
	 */
	public function newSiteLinkDeserializer(): SiteLinkDeserializer {
		return new SiteLinkDeserializer( $this->newEntityIdDeserializer() );
	}

	/**
	 * Returns a Deserializer that can deserialize StatementList objects.
	 *
	 * @since 1.4
	 */
	public function newStatementListDeserializer(): StatementListDeserializer {
		return new StatementListDeserializer( $this->newStatementDeserializer() );
	}

	/**
	 * Returns a Deserializer that can deserialize Statement objects.
	 *
	 * @since 1.4
	 */
	public function newStatementDeserializer(): StatementDeserializer {
		return new StatementDeserializer(
			$this->newSnakDeserializer(),
			$this->newSnakListDeserializer(),
			$this->newReferencesDeserializer()
		);
	}

	/**
	 * Returns a Deserializer that can deserialize ReferenceList objects.
	 */
	public function newReferencesDeserializer(): ReferenceListDeserializer {
		return new ReferenceListDeserializer( $this->newReferenceDeserializer() );
	}

	/**
	 * Returns a Deserializer that can deserialize Reference objects.
	 */
	public function newReferenceDeserializer(): ReferenceDeserializer {
		return new ReferenceDeserializer( $this->newSnakListDeserializer() );
	}

	/**
	 * Returns a Deserializer that can deserialize SnakList objects.
	 *
	 * @since 1.4
	 */
	public function newSnakListDeserializer(): SnakListDeserializer {
		return new SnakListDeserializer( $this->newSnakDeserializer() );
	}

	/**
	 * Returns a Deserializer that can deserialize Snak objects.
	 */
	public function newSnakDeserializer(): SnakDeserializer {
		return new SnakDeserializer( $this->entityIdParser, $this->dataValueDeserializer );
	}

	/**
	 * Returns a Deserializer that can deserialize EntityId objects.
	 */
	public function newEntityIdDeserializer(): EntityIdDeserializer {
		return new EntityIdDeserializer( $this->entityIdParser );
	}

	/**
	 * Returns a Deserializer that can deserialize Term objects.
	 *
	 * @since 1.5
	 */
	public function newTermDeserializer(): TermDeserializer {
		return new TermDeserializer();
	}

	/**
	 * Returns a Deserializer that can deserialize TermList objects.
	 *
	 * @since 1.5
	 */
	public function newTermListDeserializer(): TermListDeserializer {
		return new TermListDeserializer( $this->newTermDeserializer() );
	}

	/**
	 * Returns a Deserializer that can deserialize AliasGroupList objects.
	 *
	 * @since 1.5
	 */
	public function newAliasGroupListDeserializer(): AliasGroupListDeserializer {
		return new AliasGroupListDeserializer();
	}

}
