<?php

namespace Wikibase\Dumpers;

use HashBagOStuff;
use InvalidArgumentException;
use MWContentSerializationException;
use MWException;
use SiteList;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\PropertyDataTypeLookup;
use Wikibase\Lib\Store\EntityPrefetcher;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\RedirectResolvingEntityLookup;
use Wikibase\Lib\Store\StorageException;
use Wikibase\Lib\Store\UnresolvedRedirectException;
use Wikibase\Rdf\HashDedupeBag;
use Wikibase\Rdf\RdfBuilder;
use Wikibase\Rdf\RdfProducer;
use Wikibase\Rdf\RdfVocabulary;
use Wikimedia\Purtle\RdfWriterFactory;
use Wikibase\Lib\Store\UnresolvedRedirectRevisionException;

/**
 * RdfDumpGenerator generates an RDF dump of a given set of entities, excluding
 * redirects.
 *
 * @since 0.5
 *
 * @license GPL 2+
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
	 * @throws StorageException
	 * @return string|null RDF
	 */
	protected function generateDumpForEntityId( EntityId $entityId ) {
		try {
			$entityRevision = $this->entityRevisionLookup->getEntityRevision( $entityId );

			if ( !$entityRevision ) {
				throw new StorageException( 'Entity not found: ' . $entityId->getSerialization() );
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
		} catch ( UnresolvedRedirectException $e ) {
			if( $e instanceof UnresolvedRedirectRevisionException ) {
				$this->rdfBuilder->addEntityRevisionInfo(
						$entityId,
						$e->getRevisionId(),
						$e->getTimestamp()
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
	 * @param string $baseUri
	 * @param string $dataUri
	 * @param SiteList $sites
	 * @param EntityRevisionLookup $entityRevisionLookup
	 * @param PropertyDataTypeLookup $propertyLookup
	 * @param EntityPrefetcher $entityPrefetcher
	 *
	 * @return RdfDumpGenerator
	 * @throws MWException
	 */
	public static function createDumpGenerator(
			$format,
			$output,
			$baseUri,
			$dataUri,
			SiteList $sites,
			EntityRevisionLookup $entityRevisionLookup,
			PropertyDataTypeLookup $propertyLookup,
			EntityPrefetcher $entityPrefetcher
	) {
		$rdfWriter = self::getRdfWriter( $format );
		if( !$rdfWriter ) {
			throw new MWException( "Unknown format: $format" );
		}

		$flavor = RdfProducer::PRODUCE_ALL_STATEMENTS | RdfProducer::PRODUCE_TRUTHY_STATEMENTS |
			RdfProducer::PRODUCE_QUALIFIERS | RdfProducer::PRODUCE_REFERENCES |
			RdfProducer::PRODUCE_SITELINKS | RdfProducer::PRODUCE_FULL_VALUES;

		$rdfBuilder = new RdfBuilder(
			$sites,
			new RdfVocabulary( $baseUri, $dataUri ),
			$propertyLookup,
			$flavor,
			$rdfWriter,
			new HashDedupeBag()
		);

		return new RdfDumpGenerator( $output, $entityRevisionLookup, $rdfBuilder, $entityPrefetcher );
	}

}
