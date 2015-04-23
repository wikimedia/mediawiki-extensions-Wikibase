<?php

namespace Wikibase\Rdf;

use SiteList;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyDataTypeLookup;
use Wikibase\DataModel\Term\FingerprintProvider;
use Wikibase\Lib\Store\EntityLookup;
use Wikibase\Lib\Store\UnresolvedRedirectException;
use Wikibase\RdfProducer;
use Wikimedia\Purtle\RdfWriter;

/**
 * RDF mapping for wikibase data model.
 *
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author Anja Jentzsch < anja.jentzsch@wikimedia.de >
 * @author Thomas Pellissier Tanon
 * @author Daniel Kinzler
 * @author Stas Malyshev
 */
class RdfBuilder implements EntityRdfBuilder {

	/**
	 * A list of entities mentioned/touched to or by this builder.
	 * The prefixed entity IDs are used as keys in the array, the value 'true'
	 * is used to indicate that the entity has been resolved. If the value
	 * is an EntityId, this indicates that the entity has not yet been resolved
	 * (defined).
	 *
	 * @var array
	 */
	private $entitiesResolved = array ();

	/**
	 * What the serializer would produce?
	 * @var integer
	 */
	private $produceWhat;

	/**
	 * @var RdfWriter
	 */
	private $writer;

	/**
	 * @var DedupeBag
	 */
	private $dedupBag;

	/**
	 * Rdf builder for outputting labels for entity stubs.
	 * @var TermsRdfBuilder
	 */
	private $termsBuilder;

	/**
	 * Rdf builders to appyl when building rdf for an entity.
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
	 *
	 * @param SiteList $sites
	 * @param RdfVocabulary $vocabulary
	 * @param PropertyDataTypeLookup $propertyLookup
	 * @param integer $flavor
	 * @param RdfWriter $writer
	 * @param DedupeBag $dedupBag
	 */
	public function __construct(
		SiteList $sites,
		RdfVocabulary $vocabulary,
		PropertyDataTypeLookup $propertyLookup,
		$flavor,
		RdfWriter $writer,
			DedupeBag $dedupBag
	) {
		$this->vocabulary = $vocabulary;
		$this->propertyLookup = $propertyLookup;
		$this->writer = $writer;
		$this->produceWhat = $flavor;
		$this->dedupBag = $dedupBag ?: new \HashBagOStuff();

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
			$this->builders[] = new SiteLinksRdfBuilder( $vocabulary, $writer, $sites );
		}

	}

	/**
	 * @return SnakValueRdfBuilder
	 */
	private function newSimpleValueRdfBuilder() {
		$simpleValueBuilder = new SimpleValueRdfBuilder( $this->vocabulary, $this->propertyLookup );

		if ( $this->shouldProduce( RdfProducer::PRODUCE_RESOLVED_ENTITIES ) ) {
			$simpleValueBuilder->setEntityMentionCallback( array( $this, 'entityMentioned' ) );
		}

		return $simpleValueBuilder;
	}

	/**
	 * @return SnakValueRdfBuilder
	 */
	private function newSnakValueBuilder() {
		if ( $this->shouldProduce( RdfProducer::PRODUCE_FULL_VALUES ) ) {
			// NOTE: use sub-writers for nested structures
			$valueWriter = $this->writer->sub();

			$statementValueBuilder = new ComplexValueRdfBuilder( $this->vocabulary, $valueWriter, $this->propertyLookup );
			$statementValueBuilder->setDedupeBag( $this->dedupBag );

			if ( $this->shouldProduce( RdfProducer::PRODUCE_RESOLVED_ENTITIES ) ) {
				$statementValueBuilder->setEntityMentionCallback( array( $this, 'entityMentioned' ) );
			}
		} else {
			$statementValueBuilder = $this->newSimpleValueRdfBuilder();

		}

		return $statementValueBuilder;
	}

	/**
	 * @return EntityRdfBuilder
	 */
	private function newTruthyStatementRdfBuilder() {
		//NOTE: currently, the only simple values are supported in truthy mode!
		$simpleValueBuilder = $this->newSimpleValueRdfBuilder( $this->vocabulary, $this->propertyLookup );
		$statementBuilder = new TruthyStatementRdfBuilder( $this->vocabulary, $this->writer, $simpleValueBuilder );

		return $statementBuilder;
	}
	/**
	 * @return EntityRdfBuilder
	 */
	private function newFullStatementRdfBuilder() {
		$statementValueBuilder = $this->newSnakValueBuilder();

		$statementBuilder = new FullStatementRdfBuilder( $this->vocabulary, $this->writer, $statementValueBuilder );
		$statementBuilder->setDedupeBag( $this->dedupBag );

		if ( $this->shouldProduce( RdfProducer::PRODUCE_PROPERTIES ) ) {
			$statementBuilder->setPropertyMentionCallback( array( $this, 'entityMentioned' ) );
		}

		$statementBuilder->setProduceQualifiers( $this->shouldProduce( RdfProducer::PRODUCE_QUALIFIERS ) );
		$statementBuilder->setProduceReferences( $this->shouldProduce( RdfProducer::PRODUCE_REFERENCES ) );

		return $statementBuilder;
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
	 * Registers an entity as mentioned.
	 * Will be recorded as unresolved
	 * if it wasn't already marked as resolved.
	 *
	 * @todo Make callback private once we drop PHP 5.3 compat.
	 *
	 * @param EntityId $entityId
	 */
	public function entityMentioned( EntityId $entityId ) {
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

		$this->writer->about( RdfVocabulary::NS_DATA, $entityId )
			->say( RdfVocabulary::NS_SCHEMA_ORG, 'version' )->value( $revision, 'xsd', 'integer' )
			->say( RdfVocabulary::NS_SCHEMA_ORG, 'dateModified' )->value( $timestamp, 'xsd', 'dateTime' );
	}

	/**
	 * Write predicates linking property entity to property predicates
	 * @param string $id
	 */
	private function writePropertyPredicates( $id ) {
		$this->writer->say( RdfVocabulary::NS_ONTOLOGY, 'directClaim')->is( RdfVocabulary::NSP_DIRECT_CLAIM, $id );
		$this->writer->say( RdfVocabulary::NS_ONTOLOGY, 'claim')->is( RdfVocabulary::NSP_CLAIM, $id );
		$this->writer->say( RdfVocabulary::NS_ONTOLOGY, 'statementProperty' )->is( RdfVocabulary::NSP_CLAIM_STATEMENT, $id );
		$this->writer->say( RdfVocabulary::NS_ONTOLOGY, 'statementValue' )->is( RdfVocabulary::NSP_CLAIM_VALUE, $id );
		$this->writer->say( RdfVocabulary::NS_ONTOLOGY, 'qualifier' )->is( RdfVocabulary::NSP_QUALIFIER, $id );
		$this->writer->say( RdfVocabulary::NS_ONTOLOGY, 'qualifierValue' )->is( RdfVocabulary::NSP_QUALIFIER_VALUE, $id );
		$this->writer->say( RdfVocabulary::NS_ONTOLOGY, 'reference' )->is( RdfVocabulary::NSP_REFERENCE, $id );
		$this->writer->say( RdfVocabulary::NS_ONTOLOGY, 'referenceValue' )->is( RdfVocabulary::NSP_REFERENCE_VALUE, $id );
	}

	/**
	 * Adds meta-information about an entity (such as the ID and type) to the RDF graph.
	 *
	 * @todo: extract into MetaDataRdfBuilder
			$writer->say( $propertyValueNamespace, $propertyValueLName )->is( trim( $value ) );
	 *
	 * @param EntityDocument $entity
	 * @param bool $produceData Should we also produce Dataset node?
	 */
	private function addEntityMetaData( EntityDocument $entity, $produceData = true ) {
		$entityLName = $this->vocabulary->getEntityLName( $entity->getId() );

		if ( $produceData ) {
			$this->writer->about( RdfVocabulary::NS_DATA, $entity->getId() )
				->a( RdfVocabulary::NS_SCHEMA_ORG, "Dataset" )
				->say( RdfVocabulary::NS_SCHEMA_ORG, 'about' )->is( RdfVocabulary::NS_ENTITY, $entityLName );

			if ( $this->shouldProduce( RdfProducer::PRODUCE_VERSION_INFO ) ) {
				// Dumps don't need version/license info for each entity, since it is included in the dump header
				$this->writer
					->say( RdfVocabulary::NS_CC, 'license' )->is( RdfVocabulary::LICENSE )
					->say( RdfVocabulary::NS_SCHEMA_ORG, 'softwareVersion' )->value( RdfVocabulary::FORMAT_VERSION );
			}
		}

		$this->writer->about( RdfVocabulary::NS_ENTITY, $entityLName )
			->a( RdfVocabulary::NS_ONTOLOGY, $this->vocabulary->getEntityTypeName( $entity->getType() ) );

		if( $entity instanceof Property ) {
			$this->writer->say( RdfVocabulary::NS_ONTOLOGY, 'propertyType' )
				->is( RdfVocabulary::NS_ONTOLOGY, $this->vocabulary->getDataTypeName( $entity ) );
			$this->writePropertyPredicates( $entity->getId()->getSerialization() );
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
	 * Add stubs for any entities that were previously mentioned (e.g.
	 * as properties
	 * or data values).
	 *
	 * @param EntityLookup $entityLookup
	 */
	public function resolveMentionedEntities( EntityLookup $entityLookup ) {
		$hasRedirect = false;

		foreach ( $this->entitiesResolved as $key => $value ) {
			// $value is true if the entity has already been resolved,
			// or an EntityId to resolve.
			if ( !( $value instanceof EntityId ) ) {
				continue;
			}

			try {
				$entity = $entityLookup->getEntity( $value );

				if ( !$entity ) {
					continue;
				}

				$this->addEntityStub( $entity );
			} catch ( UnresolvedRedirectException $ex ) {
				// NOTE: this may add more entries to the end of entitiesResolved
				$target = $ex->getRedirectTargetId();
				$this->addEntityRedirect( $value, $target );
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
		$this->addEntityMetaData( $entity, false );

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
			$this->entityMentioned( $to );
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
			->say( RdfVocabulary::NS_CC, 'license' )->is( RdfVocabulary::LICENSE )
			->say( RdfVocabulary::NS_SCHEMA_ORG, 'softwareVersion' )->value( RdfVocabulary::FORMAT_VERSION )
			->say( RdfVocabulary::NS_SCHEMA_ORG, 'dateModified' )->value( wfTimestamp( TS_ISO_8601, $timestamp ), 'xsd', 'dateTime'  );
	}

}
