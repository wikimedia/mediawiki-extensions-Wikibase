<?php

namespace Wikibase\Rdf;

use PageProps;
use SiteList;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Store\RevisionedUnresolvedRedirectException;
use Wikimedia\Purtle\RdfWriter;

/**
 * RDF mapping for wikibase data model.
 *
 * @license GPL-2.0+
 */
class RdfBuilder implements EntityRdfBuilder, EntityMentionListener {

	/**
	 * A list of entities mentioned/touched to or by this builder.
	 * The prefixed entity IDs are used as keys in the array, the value 'true'
	 * is used to indicate that the entity has been resolved. If the value
	 * is an EntityId, this indicates that the entity has not yet been resolved
	 * (defined).
	 *
	 * @var bool[]
	 */
	private $entitiesResolved = [];

	/**
	 * What the serializer would produce?
	 *
	 * @var int
	 */
	private $produceWhat;

	/**
	 * @var RdfWriter
	 */
	private $writer;

	/**
	 * @var DedupeBag
	 */
	private $dedupeBag;

	/**
	 * RDF builders to apply when building RDF for an entity.
	 * @var EntityRdfBuilder[]
	 */
	private $builders = [];

	/**
	 * @var RdfVocabulary
	 */
	private $vocabulary;

	/**
	 * @var PropertyDataTypeLookup
	 */
	private $propertyLookup;

	/**
	 * @var ValueSnakRdfBuilderFactory
	 */
	private $valueSnakRdfBuilderFactory;

	/**
	 * @var EntityTitleLookup
	 */
	private $titleLookup;

	/**
	 * Page properties handler, can be null if we don't need them.
	 * @var PageProps|null
	 */
	private $pageProps;

	/**
	 * @param SiteList $sites
	 * @param RdfVocabulary $vocabulary
	 * @param ValueSnakRdfBuilderFactory $valueSnakRdfBuilderFactory
	 * @param PropertyDataTypeLookup $propertyLookup
	 * @param EntityRdfBuilderFactory $entityRdfBuilderFactory
	 * @param int $flavor
	 * @param RdfWriter $writer
	 * @param DedupeBag $dedupeBag
	 * @param EntityTitleLookup $titleLookup
	 */
	public function __construct(
		SiteList $sites,
		RdfVocabulary $vocabulary,
		ValueSnakRdfBuilderFactory $valueSnakRdfBuilderFactory,
		PropertyDataTypeLookup $propertyLookup,
		EntityRdfBuilderFactory $entityRdfBuilderFactory,
		$flavor,
		RdfWriter $writer,
		DedupeBag $dedupeBag,
		EntityTitleLookup $titleLookup
	) {
		$this->vocabulary = $vocabulary;
		$this->propertyLookup = $propertyLookup;
		$this->valueSnakRdfBuilderFactory = $valueSnakRdfBuilderFactory;
		$this->writer = $writer;
		$this->produceWhat = $flavor;
		$this->dedupeBag = $dedupeBag ?: new HashDedupeBag();
		$this->titleLookup = $titleLookup;

		// XXX: move construction of sub-builders to a factory class.
		$this->builders[] = new TermsRdfBuilder( $vocabulary, $writer );

		if ( $this->shouldProduce( RdfProducer::PRODUCE_TRUTHY_STATEMENTS ) ) {
			$this->builders[] = $this->newTruthyStatementRdfBuilder();
		}

		if ( $this->shouldProduce( RdfProducer::PRODUCE_ALL_STATEMENTS ) ) {
			$this->builders[] = $this->newFullStatementRdfBuilder();
		}

		$entityRdfBuilders = $entityRdfBuilderFactory->getEntityRdfBuilders(
			$flavor,
			$vocabulary,
			$writer,
			$this,
			$dedupeBag
		);

		$this->builders = array_merge( $this->builders, $entityRdfBuilders );
	}

	/**
	 * @param int $flavorFlags Flavor flags to use for this builder
	 * @return SnakRdfBuilder
	 */
	private function newSnakBuilder( $flavorFlags ) {
		$statementValueBuilder = $this->valueSnakRdfBuilderFactory->getValueSnakRdfBuilder(
			$flavorFlags,
			$this->vocabulary,
			$this->writer,
			$this,
			$this->dedupeBag
		);
		$snakBuilder = new SnakRdfBuilder( $this->vocabulary, $statementValueBuilder, $this->propertyLookup );
		$snakBuilder->setEntityMentionListener( $this );

		return $snakBuilder;
	}

	/**
	 * @return EntityRdfBuilder
	 */
	private function newTruthyStatementRdfBuilder() {
		//NOTE: currently, the only simple values are supported in truthy mode!
		$simpleSnakBuilder = $this->newSnakBuilder( $this->produceWhat & ~RdfProducer::PRODUCE_FULL_VALUES );
		$statementBuilder = new TruthyStatementRdfBuilder( $this->vocabulary, $this->writer, $simpleSnakBuilder );

		return $statementBuilder;
	}

	/**
	 * @return EntityRdfBuilder
	 */
	private function newFullStatementRdfBuilder() {
		$snakBuilder = $this->newSnakBuilder( $this->produceWhat );

		$builder = new FullStatementRdfBuilder( $this->vocabulary, $this->writer, $snakBuilder );
		$builder->setDedupeBag( $this->dedupeBag );
		$builder->setProduceQualifiers( $this->shouldProduce( RdfProducer::PRODUCE_QUALIFIERS ) );
		$builder->setProduceReferences( $this->shouldProduce( RdfProducer::PRODUCE_REFERENCES ) );

		return $builder;
	}

	/**
	 * Start writing RDF document
	 * Note that this builder does not have to finish it, it may be finished later.
	 */
	public function startDocument() {
		foreach ( $this->getNamespaces() as $gname => $uri ) {
			$this->writer->prefix( $gname, $uri );
		}

		$this->writer->start();
	}

	/**
	 * Finish writing the document
	 * After that, nothing should ever be written into the document.
	 */
	public function finishDocument() {
		$this->writer->finish();
	}

	/**
	 * Returns the RDF generated by the builder
	 *
	 * @return string RDF
	 */
	public function getRDF() {
		return $this->writer->drain();
	}

	/**
	 * Returns a map of namespace names to URIs
	 *
	 * @return array
	 */
	public function getNamespaces() {
		return $this->vocabulary->getNamespaces();
	}

	/**
	 * Get map of page properties used by this builder
	 *
	 * @return string[]
	 */
	public function getPageProperties() {
		return $this->vocabulary->getPageProperties();
	}

	/**
	 * Should we produce this aspect?
	 *
	 * @param int $what
	 *
	 * @return bool
	 */
	private function shouldProduce( $what ) {
		return ( $this->produceWhat & $what ) !== 0;
	}

	/**
	 * @see EntityMentionListener::entityReferenceMentioned
	 *
	 * @param EntityId $id
	 */
	public function entityReferenceMentioned( EntityId $id ) {
		if ( $this->shouldProduce( RdfProducer::PRODUCE_RESOLVED_ENTITIES ) ) {
			$this->entityToResolve( $id );
		}
	}

	/**
	 * @see EntityMentionListener::propertyMentioned
	 *
	 * @param PropertyId $id
	 */
	public function propertyMentioned( PropertyId $id ) {
		if ( $this->shouldProduce( RdfProducer::PRODUCE_PROPERTIES ) ) {
			$this->entityToResolve( $id );
		}
	}

	/**
	 * Registers an entity as mentioned.
	 * Will be recorded as unresolved
	 * if it wasn't already marked as resolved.
	 *
	 * @param EntityId $entityId
	 */
	private function entityToResolve( EntityId $entityId ) {
		$prefixedId = $entityId->getSerialization();

		if ( !isset( $this->entitiesResolved[$prefixedId] ) ) {
			$this->entitiesResolved[$prefixedId] = $entityId;
		}
	}

	/**
	 * Registers an entity as resolved.
	 *
	 * @param EntityId $entityId
	 */
	private function entityResolved( EntityId $entityId ) {
		$prefixedId = $entityId->getSerialization();
		$this->entitiesResolved[$prefixedId] = true;
	}

	/**
	 * Adds revision information about an entity's revision to the RDF graph.
	 *
	 * @todo: extract into MetaDataRdfBuilder
	 *
	 * @param EntityId $entityId
	 * @param int $revision
	 * @param string $timestamp in TS_MW format
	 */
	public function addEntityRevisionInfo( EntityId $entityId, $revision, $timestamp ) {
		$timestamp = wfTimestamp( TS_ISO_8601, $timestamp );
		$entityLName = $this->vocabulary->getEntityLName( $entityId );
		$entityRepositoryName = $this->vocabulary->getEntityRepositoryName( $entityId );

		$this->writer->about( RdfVocabulary::NS_DATA, $entityLName )
			->a( RdfVocabulary::NS_SCHEMA_ORG, "Dataset" )
			->say( RdfVocabulary::NS_SCHEMA_ORG, 'about' )
			->is( $this->vocabulary->entityNamespaceNames[$entityRepositoryName], $entityLName );

		if ( $this->shouldProduce( RdfProducer::PRODUCE_VERSION_INFO ) ) {
			// Dumps don't need version/license info for each entity, since it is included in the dump header
			$this->writer
				->say( RdfVocabulary::NS_CC, 'license' )->is( RdfVocabulary::LICENSE )
				->say( RdfVocabulary::NS_SCHEMA_ORG, 'softwareVersion' )->value( RdfVocabulary::FORMAT_VERSION );
		}

		$this->writer->say( RdfVocabulary::NS_SCHEMA_ORG, 'version' )->value( $revision, 'xsd', 'integer' )
			->say( RdfVocabulary::NS_SCHEMA_ORG, 'dateModified' )->value( $timestamp, 'xsd', 'dateTime' );
	}

	/**
	 * Set page props handler
	 * @param PageProps $pageProps
	 * @return self
	 */
	public function setPageProps( PageProps $pageProps ) {
		$this->pageProps = $pageProps;
		return $this;
	}

	/**
	 * Add page props information
	 * @param EntityId $entityId
	 */
	public function addEntityPageProps( EntityId $entityId ) {
		if ( !$this->pageProps || !$this->shouldProduce( RdfProducer::PRODUCE_PAGE_PROPS ) ) {
			return;
		}
		$title = $this->titleLookup->getTitleForId( $entityId );
		$props = $this->getPageProperties();
		if ( !$title || !$props ) {
			return;
		}
		$propValues = $this->pageProps->getProperties( $title, array_keys( $props ) );
		if ( !$propValues ) {
			return;
		}
		$entityProps = reset( $propValues );
		if ( !$entityProps ) {
			return;
		}

		$entityLName = $this->vocabulary->getEntityLName( $entityId );

		foreach ( $entityProps as $name => $value ) {
			if ( !isset( $props[$name]['name'] ) ) {
				continue;
			}

			if ( isset( $props[$name]['type'] ) ) {
				settype( $value, $props[$name]['type'] );
			}

			$this->writer->about( RdfVocabulary::NS_DATA, $entityLName )
				->say( RdfVocabulary::NS_ONTOLOGY, $props[$name]['name'] )
				->value( $value );
		}
	}

	/**
	 * Adds meta-information about an entity (such as the ID and type) to the RDF graph.
	 *
	 * @todo: extract into MetaDataRdfBuilder
	 *
	 * @param EntityDocument $entity
	 */
	private function addEntityMetaData( EntityDocument $entity ) {
		$entityId = $entity->getId();
		$entityLName = $this->vocabulary->getEntityLName( $entityId );
		$entityRepoName = $this->vocabulary->getEntityRepositoryName( $entityId );

		$this->writer->about(
			$this->vocabulary->entityNamespaceNames[$entityRepoName],
			$entityLName
		)
			->a( RdfVocabulary::NS_ONTOLOGY, $this->vocabulary->getEntityTypeName( $entity->getType() ) );
	}

	/**
	 * Add an entity to the RDF graph, including all supported structural components
	 * of the entity.
	 *
	 * @param EntityDocument $entity the entity to output.
	 */
	public function addEntity( EntityDocument $entity ) {
		$this->addEntityMetaData( $entity );

		foreach ( $this->builders as $builder ) {
			$builder->addEntity( $entity );
		}

		$this->entityResolved( $entity->getId() );
	}

	/**
	 * Add stubs for any entities that were previously mentioned (e.g. as properties
	 * or data values).
	 *
	 * @param EntityLookup $entityLookup
	 */
	public function resolveMentionedEntities( EntityLookup $entityLookup ) {
		$hasRedirect = false;

		foreach ( $this->entitiesResolved as $id ) {
			// $value is true if the entity has already been resolved,
			// or an EntityId to resolve.
			if ( !( $id instanceof EntityId ) ) {
				continue;
			}

			try {
				$entity = $entityLookup->getEntity( $id );

				if ( !$entity ) {
					continue;
				}

				$this->addEntityStub( $entity );
			} catch ( RevisionedUnresolvedRedirectException $ex ) {
				// NOTE: this may add more entries to the end of entitiesResolved
				$target = $ex->getRedirectTargetId();
				$this->addEntityRedirect( $id, $target );
				$hasRedirect = true;
			}
		}

		// If we encountered redirects, the redirect targets may now need resolving.
		// They actually got added to $this->entitiesResolved, but may not have been
		// processed by the loop above, because they got added while the loop was in progress.
		if ( $hasRedirect ) {
			// Call resolveMentionedEntities() recursively to resolve any yet unresolved
			// redirect targets. The regress will eventually terminate even for circular
			// redirect chains, because the second time an entity ID is encountered, it
			// will be marked as already resolved.
			$this->resolveMentionedEntities( $entityLookup );
		}
	}

	/**
	 * Adds stub information for the given Entity to the RDF graph.
	 * Stub information means meta information and labels.
	 *
	 * @param EntityDocument $entity
	 */
	public function addEntityStub( EntityDocument $entity ) {
		$this->addEntityMetaData( $entity );

		foreach ( $this->builders as $builder ) {
			$builder->addEntityStub( $entity );
		}
	}

	/**
	 * Declares $from to be an alias for $to, using the owl:sameAs relationship.
	 *
	 * @param EntityId $from
	 * @param EntityId $to
	 */
	public function addEntityRedirect( EntityId $from, EntityId $to ) {
		$fromLName = $this->vocabulary->getEntityLName( $from );
		$fromRepoName = $this->vocabulary->getEntityRepositoryName( $from );
		$toLName = $this->vocabulary->getEntityLName( $to );
		$toRepoName = $this->vocabulary->getEntityRepositoryName( $to );

		$this->writer->about( $this->vocabulary->entityNamespaceNames[$fromRepoName], $fromLName )
			->say( 'owl', 'sameAs' )
			->is( $this->vocabulary->entityNamespaceNames[$toRepoName], $toLName );

		$this->entityResolved( $from );

		if ( $this->shouldProduce( RdfProducer::PRODUCE_RESOLVED_ENTITIES ) ) {
			$this->entityToResolve( $to );
		}
	}

	/**
	 * Create header structure for the dump (this makes RdfProducer::PRODUCE_VERSION_INFO redundant)
	 *
	 * @param int $timestamp Timestamp (for testing)
	 */
	public function addDumpHeader( $timestamp = 0 ) {
		// TODO: this should point to "this document"
		$this->writer->about( RdfVocabulary::NS_ONTOLOGY, 'Dump' )
			->a( RdfVocabulary::NS_SCHEMA_ORG, "Dataset" )
			->a( 'owl', 'Ontology' )
			->say( RdfVocabulary::NS_CC, 'license' )->is( RdfVocabulary::LICENSE )
			->say( RdfVocabulary::NS_SCHEMA_ORG, 'softwareVersion' )->value( RdfVocabulary::FORMAT_VERSION )
			->say( RdfVocabulary::NS_SCHEMA_ORG, 'dateModified' )->value( wfTimestamp( TS_ISO_8601, $timestamp ), 'xsd', 'dateTime' )
			->say( 'owl', 'imports' )->is( RdfVocabulary::getOntologyURI() );
	}

}
