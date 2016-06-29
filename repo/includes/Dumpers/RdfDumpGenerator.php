<?php

namespace Wikibase\Dumpers;

use InvalidArgumentException;
use MWContentSerializationException;
use MWException;
use SiteList;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Lookup\EntityLookupException;
use Wikibase\DataModel\Services\Entity\EntityPrefetcher;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\StorageException;
use Wikibase\DataModel\Services\Lookup\RedirectResolvingEntityLookup;
use Wikibase\Lib\Store\RevisionedUnresolvedRedirectException;
use Wikibase\Rdf\ValueSnakRdfBuilderFactory;
use Wikibase\Rdf\HashDedupeBag;
use Wikibase\Rdf\RdfBuilder;
use Wikibase\Rdf\RdfProducer;
use Wikibase\Rdf\RdfVocabulary;
use Wikimedia\Purtle\RdfWriterFactory;

/**
 * RdfDumpGenerator generates an RDF dump of a given set of entities, excluding
 * redirects.
 *
 * @since 0.5
 *
 * @license GPL-2.0+
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
	 * @param resource $out
	 * @param EntityRevisionLookup $lookup Must not resolve redirects
	 * @param RdfBuilder $rdfBuilder
	 * @param EntityPrefetcher $entityPrefetcher
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $out, EntityRevisionLookup $lookup, RdfBuilder $rdfBuilder, EntityPrefetcher $entityPrefetcher ) {
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

	private static function getRdfWriter( $name ) {
		$factory = new RdfWriterFactory();
		$format = $factory->getFormatName( $name );

		if ( !$format ) {
			return null;
		}

		return $factory->getWriter( $format );
	}

	/**
	 * @param string $format
	 * @param resource $output
	 * @param SiteList $sites
	 * @param EntityRevisionLookup $entityRevisionLookup
	 * @param PropertyDataTypeLookup $propertyLookup
	 * @param ValueSnakRdfBuilderFactory $valueSnakRdfBuilderFactory
	 * @param EntityPrefetcher $entityPrefetcher
	 * @param RdfVocabulary $vocabulary
	 *
	 * @return self
	 * @throws MWException
	 */
	public static function createDumpGenerator(
		$format,
		$output,
		SiteList $sites,
		EntityRevisionLookup $entityRevisionLookup,
		PropertyDataTypeLookup $propertyLookup,
		ValueSnakRdfBuilderFactory $valueSnakRdfBuilderFactory,
		EntityPrefetcher $entityPrefetcher,
		RdfVocabulary $vocabulary
	) {
		$rdfWriter = self::getRdfWriter( $format );
		if ( !$rdfWriter ) {
			throw new MWException( "Unknown format: $format" );
		}

		$flavor =
			RdfProducer::PRODUCE_ALL_STATEMENTS | RdfProducer::PRODUCE_TRUTHY_STATEMENTS |
			RdfProducer::PRODUCE_QUALIFIERS | RdfProducer::PRODUCE_REFERENCES |
			RdfProducer::PRODUCE_SITELINKS | RdfProducer::PRODUCE_FULL_VALUES |
			RdfProducer::PRODUCE_NORMALIZED_VALUES;

		$rdfBuilder = new RdfBuilder(
			$sites,
			$vocabulary,
			$valueSnakRdfBuilderFactory,
			$propertyLookup,
			$flavor,
			$rdfWriter,
			new HashDedupeBag()
		);

		return new self( $output, $entityRevisionLookup, $rdfBuilder, $entityPrefetcher );
	}

}
