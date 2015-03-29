<?php

namespace Wikibase;

use BagOStuff;
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
	 * Hash to store seen references/values for deduplication
	 * @var BagOStuff
	 */
	private $dedupBag;

	/**
	 * @var TermsRdfBuilder
	 */
	private $termsBuilder;

	/**
	 * @var EntityRdfBuilder[]
	 */
	private $builders = array();

	/**
	 *
	 * @var DateTimeValueCleaner
	 */
	private $dateCleaner;

	/**
	 *
	 * @param SiteList $sites
	 * @param RdfVocabulary $vocabulary
	 * @param PropertyDataTypeLookup $propertyLookup
	 * @param integer $flavor
	 * @param RdfWriter $writer
	 * @param BagOStuff|null $dedupBag Container used for deduplication of refs/values
	 */
	public function __construct( SiteList $sites, RdfVocabulary $vocabulary,
			PropertyDataTypeLookup $propertyLookup, $flavor,
			RdfWriter $writer,
			BagOStuff $dedupBag = null
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

		if ( $this->shouldProduce( RdfProducer::PRODUCE_ALL_STATEMENTS ) || $this->shouldProduce( RdfProducer::PRODUCE_TRUTHY_STATEMENTS ) ) {
			$entityMentioned = array( $this, 'entityMentioned' );
			$self = $this; // PHP 5.3 compat

			$simpleValueBuilder = new SimpleValueRdfBuilder( $vocabulary, $propertyLookup );

			if( $this->shouldProduce( RdfProducer::PRODUCE_RESOLVED_ENTITIES ) ) {
				$simpleValueBuilder->setEntityMentionCallback( $entityMentioned );
			}

			if ( $this->shouldProduce( RdfProducer::PRODUCE_FULL_VALUES ) ) {
				$valueSeen = function( $hash ) use ( $self ) {
					return $self->alreadySeen( $hash, 'V' );
				};

				$valueWriter = $writer->sub();

				$statementValueBuilder = new ComplexValueRdfBuilder( $vocabulary, $valueWriter, $propertyLookup );
				$statementValueBuilder->setValueSeenCallback( $valueSeen );

				if( $this->shouldProduce( RdfProducer::PRODUCE_RESOLVED_ENTITIES ) ) {
					$statementValueBuilder->setEntityMentionCallback( $entityMentioned );
				}
			} else {
				$statementValueBuilder = $simpleValueBuilder;
			}

			if ( $this->shouldProduce( RdfProducer::PRODUCE_TRUTHY_STATEMENTS ) ) {
				//NOTE: currently, the only simple values are supported in truthy mode!
				$statementBuilder = new TruthyStatementRdfBuilder( $vocabulary, $writer, $simpleValueBuilder );
				$this->builders[] = $statementBuilder;
			}

			if ( $this->shouldProduce( RdfProducer::PRODUCE_ALL_STATEMENTS ) ) {
				$statementWriter = $writer->sub();
				$referenceWriter = $writer->sub();

				$referenceSeen = function( $hash ) use ( $self ) {
					return $self->alreadySeen( $hash, 'R' );
				};

				$statementBuilder = new FullStatementRdfBuilder( $vocabulary, $statementWriter, $referenceWriter, $statementValueBuilder );
				$statementBuilder->setReferenceSeenCallback( $referenceSeen );
				$statementBuilder->setEntityMentionCallback( $entityMentioned );
				$statementBuilder->setTrackProperties( $this->shouldProduce( RdfProducer::PRODUCE_PROPERTIES ) );
				$statementBuilder->setProduceQualifiers( $this->shouldProduce( RdfProducer::PRODUCE_QUALIFIERS ) );
				$statementBuilder->setProduceReferences( $this->shouldProduce( RdfProducer::PRODUCE_REFERENCES ) );

				$this->builders[] = $statementBuilder;
			}
		}
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
	 * Set date cleaner
	 * @param DateTimeValueCleaner $cleaner
	 */
	public function setDateCleaner( DateTimeValueCleaner $cleaner ) {
		$this->dateCleaner = $cleaner;
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
	 * Did we already see this value? If yes, we may need to skip it
	 *
	 * @todo Make callback private once we drop PHP 5.3 compat.
	 *
	 * @param string $hash hash value to check
	 * @param string $namespace
	 *
	 * @return bool
	 */
	public function alreadySeen( $hash, $namespace ) {
		if ( !$this->dedupBag ) {
			return false;
		}
		$key = $namespace . substr($hash, 0, 5);
		if ( $this->dedupBag->get( $key ) !== $hash ) {
			$this->dedupBag->set( $key, $hash );
			return false;
		}
		return true;
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
