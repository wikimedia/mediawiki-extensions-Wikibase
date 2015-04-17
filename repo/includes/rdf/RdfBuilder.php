<?php

namespace Wikibase;

use SiteList;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyDataTypeLookup;
use Wikibase\Lib\Store\EntityLookup;
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
class RdfBuilder {

	/**
	 * A list of entities mentioned/touched to or by this builder.
	 * The prefixed entity IDs are used as keys in the array, the values 'true'
	 * is used to indicate that the entity has been resolved, 'false' indicates
	 * that the entity was mentioned but not resolved (defined).
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
	 *
	 * @param SiteList $sites
	 * @param RdfVocabulary $vocabulary
	 * @param PropertyDataTypeLookup $propertyLookup
	 * @param integer $flavor
	 * @param RdfWriter $writer
	 * @param DedupeBag $dedupBag
	 */
	public function __construct( SiteList $sites, RdfVocabulary $vocabulary,
			PropertyDataTypeLookup $propertyLookup, $flavor,
			RdfWriter $writer,
			DedupeBag $dedupBag
	) {
		$this->vocabulary = $vocabulary;
		$this->dedupBag = $dedupBag;
		$this->writer = $writer;
		$this->produceWhat = $flavor;
		
		// XXX: move construction of sub-builders to the caller.
		$this->termsBuilder = new TermsRdfBuilder( $vocabulary, $writer );
		$this->builders[] = $this->termsBuilder;

		if ( $this->shouldProduce( RdfProducer::PRODUCE_SITELINKS ) ) {
			$this->builders[] = new SiteLinksRdfBuilder( $vocabulary, $writer, $sites );
		}

		if ( $this->shouldProduce( RdfProducer::PRODUCE_TRUTHY_STATEMENTS ) ) {
			$this->builders[] = $this->newTruthyStatementRdfBuilder( $vocabulary, $propertyLookup, $writer );
		}

		if ( $this->shouldProduce( RdfProducer::PRODUCE_ALL_STATEMENTS ) ) {
			$this->builders[] = $this->newFullStatementRdfBuilder( $vocabulary, $propertyLookup, $writer );
		}
	}

	/**
	 * @param RdfVocabulary $vocabulary
	 * @param PropertyDataTypeLookup $propertyLookup
	 *
	 * @return SnakValueRdfBuilder
	 */
	private function newSimpleValueRdfBuilder( RdfVocabulary $vocabulary, PropertyDataTypeLookup $propertyLookup ) {
		$simpleValueBuilder = new SimpleValueRdfBuilder( $vocabulary, $propertyLookup );

		if ( $this->shouldProduce( RdfProducer::PRODUCE_RESOLVED_ENTITIES ) ) {
			$simpleValueBuilder->setEntityMentionCallback( array( $this, 'entityMentioned' ) );
		}

		return $simpleValueBuilder;
	}

	/**
	 * @param RdfVocabulary $vocabulary
	 * @param PropertyDataTypeLookup $propertyLookup
	 * @param RdfWriter $writer
	 *
	 * @return SnakValueRdfBuilder
	 */
	private function newSnakValueBuilder( RdfVocabulary $vocabulary, PropertyDataTypeLookup $propertyLookup, RdfWriter $writer ) {
		$simpleValueBuilder = $this->newSimpleValueRdfBuilder( $vocabulary, $propertyLookup );

		if ( $this->shouldProduce( RdfProducer::PRODUCE_FULL_VALUES ) ) {
			$self = $this; // PHP 5.3 compat

			// NOTE: us sub-writers for nested structures
			$valueWriter = $writer->sub();

			$statementValueBuilder = new ComplexValueRdfBuilder( $vocabulary, $valueWriter, $propertyLookup );
			$statementValueBuilder->setDedupeBag( $this->dedupBag );

			if ( $this->shouldProduce( RdfProducer::PRODUCE_RESOLVED_ENTITIES ) ) {
				$statementValueBuilder->setEntityMentionCallback( array( $this, 'entityMentioned' ) );
			}
		} else {
			$statementValueBuilder = $simpleValueBuilder;
		}

		return $statementValueBuilder;
	}

	/**
	 * @param RdfVocabulary $vocabulary
	 * @param PropertyDataTypeLookup $propertyLookup
	 * @param RdfWriter $writer
	 * @param callable|null $entityMentioned
	 *
	 * @return EntityRdfBuilder
	 */
	public function newTruthyStatementRdfBuilder( RdfVocabulary $vocabulary, PropertyDataTypeLookup $propertyLookup, RdfWriter $writer ) {
		//NOTE: currently, the only simple values are supported in truthy mode!
		$simpleValueBuilder = $this->newSimpleValueRdfBuilder( $vocabulary, $propertyLookup );
		$statementBuilder = new TruthyStatementRdfBuilder( $vocabulary, $writer, $simpleValueBuilder );

		return $statementBuilder;
	}
	/**
	 * @param RdfVocabulary $vocabulary
	 * @param PropertyDataTypeLookup $propertyLookup
	 * @param RdfWriter $writer
	 * @param callable|null $entityMentioned
	 *
	 * @return EntityRdfBuilder
	 */
	public function newFullStatementRdfBuilder( RdfVocabulary $vocabulary, PropertyDataTypeLookup $propertyLookup, RdfWriter $writer ) {
		$statementValueBuilder = $this->newSnakValueBuilder( $vocabulary, $propertyLookup, $writer );

		$self = $this; // PHP 5.3 compat

		$statementBuilder = new FullStatementRdfBuilder( $vocabulary, $writer, $statementValueBuilder );
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
	 * Returns the RDF generated by the builder.
	 *
	 * @note After a call to getRDF(), the RdfBuilder should not be used any further.
	 * @todo: Allow periodic flushing via getRDF() calls on a single getRDF() instance.
	 *        That would require RdfWriter::drain() to keep sub-writers intect.
	 *
	 * @return string RDF
	 */
	public function getRDF() {
		//NOTE: drain() kills all the sub-writers, any output they may collect in the future will be lost!
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
			$this->entitiesResolved[$prefixedId] = false;
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
	 * Adds meta-information about an entity (such as the ID and type) to the RDF graph.
	 *
	 * @todo: extract into MetaDataRdfBuilder
	 *
	 * @param Entity $entity
	 * @param bool $produceData Should we also produce Dataset node?
	 */
	private function addEntityMetaData( Entity $entity, $produceData = true ) {
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
		}
	}

	/**
	 * Add an entity to the RDF graph, including all supported structural components
	 * of the entity.
	 *
	 * @param Entity $entity the entity to output.
	 */
	public function addEntity( Entity $entity ) {
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
	public function resolveMentionedEntities( EntityLookup $entityLookup ) { //FIXME: needs test
		// @todo FIXME inject a DispatchingEntityIdParser
		$idParser = new BasicEntityIdParser();

		foreach ( $this->entitiesResolved as $entityId => $resolved ) {
			if ( !$resolved ) {
				$entityId = $idParser->parse( $entityId );
				$entity = $entityLookup->getEntity( $entityId );
				if ( !$entity ) {
					continue;
				}
				$this->addEntityStub( $entity );
			}
		}
	}

	/**
	 * Adds stub information for the given Entity to the RDF graph.
	 * Stub information means meta information and labels.
	 *
	 * @todo: extract into EntityStubRdfBuilder?
	 *
	 * @param Entity $entity
	 */
	private function addEntityStub( Entity $entity ) {
		$this->addEntityMetaData( $entity, false );
		$this->termsBuilder->addLabels( $entity );
		$this->termsBuilder->addDescriptions( $entity );
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
