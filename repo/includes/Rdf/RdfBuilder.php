<?php

namespace Wikibase\Rdf;

use SiteList;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Term\FingerprintProvider;
use Wikibase\Lib\Store\RevisionedUnresolvedRedirectException;
use Wikimedia\Purtle\RdfWriter;

/**
 * RDF mapping for wikibase data model.
 *
 * @since 0.4
 *
 * @license GPL-2.0+
 * @author Anja Jentzsch < anja.jentzsch@wikimedia.de >
 * @author Thomas Pellissier Tanon
 * @author Daniel Kinzler
 * @author Stas Malyshev
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
	private $entitiesResolved = array();

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
	 * Rdf builder for outputting labels for entity stubs.
	 * @var TermsRdfBuilder
	 */
	private $termsBuilder;

	/**
	 * RDF builders to apply when building RDF for an entity.
	 * @var EntityRdfBuilder[]
	 */
	private $builders = array();

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
	 * @param SiteList $sites
	 * @param RdfVocabulary $vocabulary
	 * @param ValueSnakRdfBuilderFactory $valueSnakRdfBuilderFactory
	 * @param PropertyDataTypeLookup $propertyLookup
	 * @param int $flavor
	 * @param RdfWriter $writer
	 * @param DedupeBag $dedupeBag
	 */
	public function __construct(
		SiteList $sites,
		RdfVocabulary $vocabulary,
		ValueSnakRdfBuilderFactory $valueSnakRdfBuilderFactory,
		PropertyDataTypeLookup $propertyLookup,
		$flavor,
		RdfWriter $writer,
		DedupeBag $dedupeBag
	) {
		$this->vocabulary = $vocabulary;
		$this->propertyLookup = $propertyLookup;
		$this->valueSnakRdfBuilderFactory = $valueSnakRdfBuilderFactory;
		$this->writer = $writer;
		$this->produceWhat = $flavor;
		$this->dedupeBag = $dedupeBag ?: new HashDedupeBag();

		// XXX: move construction of sub-builders to a factory class.
		$this->termsBuilder = new TermsRdfBuilder( $vocabulary, $writer );
		$this->builders[] = $this->termsBuilder;

		if ( $this->shouldProduce( RdfProducer::PRODUCE_TRUTHY_STATEMENTS ) ) {
			$this->builders[] = $this->newTruthyStatementRdfBuilder();
		}

		if ( $this->shouldProduce( RdfProducer::PRODUCE_ALL_STATEMENTS ) ) {
			$this->builders[] = $this->newFullStatementRdfBuilder();
		}

		// placing this last produces more readable output since all entity things are together
		if ( $this->shouldProduce( RdfProducer::PRODUCE_SITELINKS ) ) {
			$builder = new SiteLinksRdfBuilder( $vocabulary, $writer, $sites );
			// We can use the same bag since namespaces are different
			$builder->setDedupeBag( $this->dedupeBag );
			$this->builders[] = $builder;
		}
	}

	/**
	 * @param string $full
	 *
	 * @return SnakRdfBuilder
	 */
	private function newSnakBuilder( $full ) {
		if ( $full === 'full' ) {
			$statementValueBuilder = $this->valueSnakRdfBuilderFactory->getComplexValueSnakRdfBuilder(
				$this->vocabulary,
				$this->writer,
				$this,
				$this->dedupeBag
			);
		} else {
			$statementValueBuilder = $this->valueSnakRdfBuilderFactory->getSimpleValueSnakRdfBuilder(
				$this->vocabulary,
				$this->writer,
				$this,
				$this->dedupeBag
			);
		}

		$snakBuilder = new SnakRdfBuilder( $this->vocabulary, $statementValueBuilder, $this->propertyLookup );
		$snakBuilder->setEntityMentionListener( $this );

		return $snakBuilder;
	}

	/**
	 * @return EntityRdfBuilder
	 */
	private function newTruthyStatementRdfBuilder() {
		//NOTE: currently, the only simple values are supported in truthy mode!
		$simpleSnakBuilder = $this->newSnakBuilder( 'simple' );
		$statementBuilder = new TruthyStatementRdfBuilder( $this->vocabulary, $this->writer, $simpleSnakBuilder );

		return $statementBuilder;
	}

	/**
	 * @return EntityRdfBuilder
	 */
	private function newFullStatementRdfBuilder() {
		$snakBuilder = $this->newSnakBuilder(
			$this->shouldProduce( RdfProducer::PRODUCE_FULL_VALUES ) ? 'full' : 'simple'
		);

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

		$this->writer->about( RdfVocabulary::NS_DATA, $entityId )
			->a( RdfVocabulary::NS_SCHEMA_ORG, "Dataset" )
			->say( RdfVocabulary::NS_SCHEMA_ORG, 'about' )->is( RdfVocabulary::NS_ENTITY, $entityLName );

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
	 * Write definition for wdno:P123 class to use as novalue
	 * @param string $id
	 */
	private function writeNovalueClass( $id ) {
		$this->writer->about( RdfVocabulary::NSP_NOVALUE, $id )->say( 'a' )->is( 'owl', 'Class' );
		$internalClass = $this->writer->blank();
		$this->writer->say( 'owl', 'complementOf' )->is( '_', $internalClass );
		$this->writer->about( '_', $internalClass )->say( 'a' )->is( 'owl', 'Restriction' );
		$this->writer->say( 'owl', 'onProperty' )->is( RdfVocabulary::NSP_DIRECT_CLAIM, $id );
		$this->writer->say( 'owl', 'someValuesFrom' )->is( 'owl', 'Thing' );
	}

	/**
	 * Write predicates linking property entity to property predicates
	 * @param string $id
	 * @param boolean $isObjectProperty Is the property data or object property?
	 */
	private function writePropertyPredicates( $id, $isObjectProperty ) {
		$this->writer->say( RdfVocabulary::NS_ONTOLOGY, 'directClaim' )->is( RdfVocabulary::NSP_DIRECT_CLAIM, $id );
		$this->writer->say( RdfVocabulary::NS_ONTOLOGY, 'claim' )->is( RdfVocabulary::NSP_CLAIM, $id );
		$this->writer->say( RdfVocabulary::NS_ONTOLOGY, 'statementProperty' )->is( RdfVocabulary::NSP_CLAIM_STATEMENT, $id );
		$this->writer->say( RdfVocabulary::NS_ONTOLOGY, 'statementValue' )->is( RdfVocabulary::NSP_CLAIM_VALUE, $id );
		$this->writer->say( RdfVocabulary::NS_ONTOLOGY, 'qualifier' )->is( RdfVocabulary::NSP_QUALIFIER, $id );
		$this->writer->say( RdfVocabulary::NS_ONTOLOGY, 'qualifierValue' )->is( RdfVocabulary::NSP_QUALIFIER_VALUE, $id );
		$this->writer->say( RdfVocabulary::NS_ONTOLOGY, 'reference' )->is( RdfVocabulary::NSP_REFERENCE, $id );
		$this->writer->say( RdfVocabulary::NS_ONTOLOGY, 'referenceValue' )->is( RdfVocabulary::NSP_REFERENCE_VALUE, $id );
		$this->writer->say( RdfVocabulary::NS_ONTOLOGY, 'novalue' )->is( RdfVocabulary::NSP_NOVALUE, $id );
		// Always object properties
		$this->writer->about( RdfVocabulary::NSP_CLAIM, $id )->a( 'owl', 'ObjectProperty' );
		$this->writer->about( RdfVocabulary::NSP_CLAIM_VALUE, $id )->a( 'owl', 'ObjectProperty' );
		$this->writer->about( RdfVocabulary::NSP_QUALIFIER_VALUE, $id )->a( 'owl', 'ObjectProperty' );
		$this->writer->about( RdfVocabulary::NSP_REFERENCE_VALUE, $id )->a( 'owl', 'ObjectProperty' );
		// Depending on property type
		if ( $isObjectProperty ) {
			$datatype = 'ObjectProperty';
		} else {
			$datatype = 'DatatypeProperty';
		}
		$this->writer->about( RdfVocabulary::NSP_DIRECT_CLAIM, $id )->a( 'owl', $datatype );
		$this->writer->about( RdfVocabulary::NSP_CLAIM_STATEMENT, $id )->a( 'owl', $datatype );
		$this->writer->about( RdfVocabulary::NSP_QUALIFIER, $id )->a( 'owl', $datatype );
		$this->writer->about( RdfVocabulary::NSP_REFERENCE, $id )->a( 'owl', $datatype );
	}

	/**
	 * Check if the property describes link between objects
	 * or just data item.
	 *
	 * @param Property $property
	 * @return boolean
	 */
	private function propertyIsLink( Property $property ) {
		// For now, it's very simple but can be more complex later
		// FIXME: external-id properties may be literals or links. Check ExternalIdentifierRdfBuilder!
		return in_array( $property->getDataTypeId(), array( 'wikibase-item', 'wikibase-property', 'url', 'commonsMedia' ) );
	}

	/**
	 * Adds meta-information about an entity (such as the ID and type) to the RDF graph.
	 *
	 * @todo: extract into MetaDataRdfBuilder
	 *
	 * @param EntityDocument $entity
	 */
	private function addEntityMetaData( EntityDocument $entity ) {
		$entityLName = $this->vocabulary->getEntityLName( $entity->getId() );

		$this->writer->about( RdfVocabulary::NS_ENTITY, $entityLName )
			->a( RdfVocabulary::NS_ONTOLOGY, $this->vocabulary->getEntityTypeName( $entity->getType() ) );

		if ( $entity instanceof Property ) {
			$this->writer->say( RdfVocabulary::NS_ONTOLOGY, 'propertyType' )
				->is( $this->vocabulary->getDataTypeURI( $entity ) );

			$id = $entity->getId()->getSerialization();
			$this->writePropertyPredicates( $id, $this->propertyIsLink( $entity ) );
			$this->writeNovalueClass( $id );
		}
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
	 * @todo: extract into EntityStubRdfBuilder?
	 *
	 * @param EntityDocument $entity
	 */
	private function addEntityStub( EntityDocument $entity ) {
		$this->addEntityMetaData( $entity );

		if ( $entity instanceof FingerprintProvider ) {
			$fingerprint = $entity->getFingerprint();

			/** @var EntityDocument $entity */
			$entityLName = $this->vocabulary->getEntityLName( $entity->getId() );

			$this->termsBuilder->addLabels( $entityLName, $fingerprint->getLabels() );
			$this->termsBuilder->addDescriptions( $entityLName, $fingerprint->getDescriptions() );
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
		$toLName = $this->vocabulary->getEntityLName( $to );

		$this->writer->about( RdfVocabulary::NS_ENTITY, $fromLName )
			->say( 'owl', 'sameAs' )
			->is( RdfVocabulary::NS_ENTITY, $toLName );

		$this->entityResolved( $from );

		if ( $this->shouldProduce( RdfProducer::PRODUCE_RESOLVED_ENTITIES ) ) {
			$this->entityToResolve( $to );
		}
	}

	/**
	 * Create header structure for the dump
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
