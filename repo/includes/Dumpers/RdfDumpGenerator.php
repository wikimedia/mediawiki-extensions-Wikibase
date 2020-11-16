<?php

namespace Wikibase\Repo\Dumpers;

use InvalidArgumentException;
use MWContentSerializationException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Entity\EntityPrefetcher;
use Wikibase\DataModel\Services\Lookup\EntityLookupException;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\RedirectResolvingEntityLookup;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\RevisionedUnresolvedRedirectException;
use Wikibase\Lib\Store\StorageException;
use Wikibase\Repo\Content\EntityContentFactory;
use Wikibase\Repo\Rdf\EntityRdfBuilderFactory;
use Wikibase\Repo\Rdf\HashDedupeBag;
use Wikibase\Repo\Rdf\RdfBuilder;
use Wikibase\Repo\Rdf\RdfProducer;
use Wikibase\Repo\Rdf\RdfVocabulary;
use Wikibase\Repo\Rdf\ValueSnakRdfBuilderFactory;
use Wikimedia\Purtle\BNodeLabeler;
use Wikimedia\Purtle\RdfWriterFactory;

/**
 * RdfDumpGenerator generates an RDF dump of a given set of entities, excluding
 * redirects.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Stas Malyshev
 */
class RdfDumpGenerator extends DumpGenerator {

	/**
	 * @var RdfBuilder
	 */
	private $rdfBuilder;

	/**
	 * @var EntityRevisionLookup
	 */
	private $entityRevisionLookup;

	/**
	 * @var int Fixed timestamp for tests.
	 */
	private $timestamp;

	/**
	 * @param resource             $out
	 * @param EntityRevisionLookup $lookup Must not resolve redirects
	 * @param RdfBuilder           $rdfBuilder
	 * @param EntityPrefetcher     $entityPrefetcher
	 */
	public function __construct(
		$out,
		EntityRevisionLookup $lookup,
		RdfBuilder $rdfBuilder,
		EntityPrefetcher $entityPrefetcher
	) {
		parent::__construct( $out, $entityPrefetcher );

		if ( $lookup instanceof RedirectResolvingEntityLookup ) {
			throw new InvalidArgumentException( '$lookup must not resolve redirects!' );
		}

		$this->rdfBuilder = $rdfBuilder;
		$this->entityRevisionLookup = $lookup;
	}

	/**
	 * Do something before dumping data
	 */
	protected function preDump() {
		$this->rdfBuilder->startDocument();
		$this->rdfBuilder->addDumpHeader( $this->timestamp );

		$header = $this->rdfBuilder->getRDF();
		$this->writeToDump( $header );
	}

	/**
	 * Do something after dumping data
	 */
	protected function postDump() {
		$this->rdfBuilder->finishDocument();

		$footer = $this->rdfBuilder->getRDF();
		$this->writeToDump( $footer );
	}

	/**
	 * Produces RDF dump of the entity
	 *
	 * @param EntityId $entityId
	 *
	 * @throws EntityLookupException
	 * @throws StorageException
	 * @return string|null RDF
	 */
	protected function generateDumpForEntityId( EntityId $entityId ) {
		try {
			$entityRevision = $this->entityRevisionLookup->getEntityRevision( $entityId );

			if ( !$entityRevision ) {
				throw new EntityLookupException( $entityId, 'Entity not found: ' . $entityId->getSerialization() );
			}

			$this->rdfBuilder->addEntityRevisionInfo(
				$entityRevision->getEntity()->getId(),
				$entityRevision->getRevisionId(),
				$entityRevision->getTimestamp()
			);

			$this->rdfBuilder->addEntityPageProps( $entityRevision->getEntity() );

			$this->rdfBuilder->addEntity(
				$entityRevision->getEntity()
			);

		} catch ( MWContentSerializationException $ex ) {
			throw new StorageException( 'Deserialization error for ' . $entityId->getSerialization() );
		} catch ( RevisionedUnresolvedRedirectException $e ) {
			if ( $e->getRevisionId() > 0 ) {
				$this->rdfBuilder->addEntityRevisionInfo(
					$entityId,
					$e->getRevisionId(),
					$e->getRevisionTimestamp()
				);
			}

			$this->rdfBuilder->addEntityRedirect(
				$entityId,
				$e->getRedirectTargetId()
			);
		}

		$rdf = $this->rdfBuilder->getRDF();
		return $rdf;
	}

	/**
	 * @param int $timestamp
	 */
	public function setTimestamp( $timestamp ) {
		$this->timestamp = (int)$timestamp;
	}

	private static function getRdfWriter( $name, BNodeLabeler $labeler = null ) {
		$factory = new RdfWriterFactory();
		$format = $factory->getFormatName( $name );

		if ( !$format ) {
			return null;
		}

		return $factory->getWriter( $format, $labeler );
	}

	/**
	 * Get the producer setting for the given flavor.
	 *
	 * @param string|null $flavorName
	 *
	 * @return int
	 * @throws InvalidArgumentException
	 */
	private static function getFlavorFlags( $flavorName ) {
		//Note: RdfProducer::PRODUCE_VERSION_INFO is not needed here as dumps
		// include that per default.

		switch ( $flavorName ) {
			case 'full-dump':
				return RdfProducer::PRODUCE_ALL_STATEMENTS
					| RdfProducer::PRODUCE_TRUTHY_STATEMENTS
					| RdfProducer::PRODUCE_QUALIFIERS
					| RdfProducer::PRODUCE_REFERENCES
					| RdfProducer::PRODUCE_SITELINKS
					| RdfProducer::PRODUCE_FULL_VALUES
					| RdfProducer::PRODUCE_PAGE_PROPS
					| RdfProducer::PRODUCE_NORMALIZED_VALUES;
			case 'truthy-dump':
				// XXX: For partial dumps we might want this to also include entity stubs.
				return RdfProducer::PRODUCE_TRUTHY_STATEMENTS;
		}

		throw new InvalidArgumentException( "Unsupported flavor: $flavorName" );
	}

	/**
	 * @param string                     $format
	 * @param resource                   $output
	 * @param string                     $flavor Either "full" or "truthy"
	 * @param EntityRevisionLookup       $entityRevisionLookup
	 * @param PropertyDataTypeLookup     $propertyLookup
	 * @param ValueSnakRdfBuilderFactory $valueSnakRdfBuilderFactory
	 * @param EntityRdfBuilderFactory    $entityRdfBuilderFactory
	 * @param EntityPrefetcher           $entityPrefetcher
	 * @param RdfVocabulary              $vocabulary
	 * @param EntityContentFactory       $entityContentFactory
	 * @param BNodeLabeler|null          $labeler
	 *
	 * @return static
	 * @throws InvalidArgumentException
	 */
	public static function createDumpGenerator(
		$format,
		$output,
		$flavor,
		EntityRevisionLookup $entityRevisionLookup,
		PropertyDataTypeLookup $propertyLookup,
		ValueSnakRdfBuilderFactory $valueSnakRdfBuilderFactory,
		EntityRdfBuilderFactory $entityRdfBuilderFactory,
		EntityPrefetcher $entityPrefetcher,
		RdfVocabulary $vocabulary,
		EntityContentFactory $entityContentFactory,
		BNodeLabeler $labeler = null
	) {
		$rdfWriter = self::getRdfWriter( $format, $labeler );
		if ( !$rdfWriter ) {
			throw new InvalidArgumentException( "Unknown format: $format" );
		}

		$rdfBuilder = new RdfBuilder(
			$vocabulary,
			$valueSnakRdfBuilderFactory,
			$propertyLookup,
			$entityRdfBuilderFactory,
			self::getFlavorFlags( $flavor ),
			$rdfWriter,
			new HashDedupeBag(),
			$entityContentFactory
		);

		return new self( $output, $entityRevisionLookup, $rdfBuilder, $entityPrefetcher );
	}

}
