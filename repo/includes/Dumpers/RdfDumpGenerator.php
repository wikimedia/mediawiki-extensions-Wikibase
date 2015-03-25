<?php

namespace Wikibase\Dumpers;

use HashBagOStuff;
use InvalidArgumentException;
use MWContentSerializationException;
use MWException;
use SiteList;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\PropertyDataTypeLookup;
use Wikibase\Lib\Store\EntityLookup;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\RedirectResolvingEntityLookup;
use Wikibase\Lib\Store\StorageException;
use Wikibase\Lib\Store\UnresolvedRedirectException;
use Wikibase\RdfProducer;
use Wikibase\RdfSerializer;

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
	 * @var RdfSerializer
	 */
	private $entitySerializer;

	/**
	 * @var EntityRevisionLookup
	 */
	private $entityRevisionLookup;

	/**
	 * @var bool[] List of the prefixes we've seen in the dump.
	 */
	private $prefixes;

	/**
	 * @var int Fixed timestamp for tests.
	 */
	private $timestamp;

	/**
	 * @param resource $out
	 * @param EntityRevisionLookup $lookup Must not resolve redirects
	 * @param RdfSerializer $entitySerializer
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $out, EntityRevisionLookup $lookup, RdfSerializer $entitySerializer ) {
		parent::__construct( $out );
		if ( $lookup instanceof RedirectResolvingEntityLookup ) {
			throw new InvalidArgumentException( '$lookup must not resolve redirects!' );
		}

		$this->entitySerializer = $entitySerializer;
		$this->entityRevisionLookup = $lookup;
	}

	/**
	 * Do something before dumping data
	 */
	protected function preDump() {
		$header = $this->entitySerializer->startDump( $this->timestamp );

		$this->writeToDump( $header );
	}

	/**
	 * Do something after dumping data
	 */
	protected function postDump() {
		$footer = $this->entitySerializer->finishDocument();

		$this->writeToDump( $footer );
	}

	/**
	 * Produces RDF dump of the entity
	 *
	 * @param EntityId $entityId
	 *
	 * @throws StorageException
	 * @return string|null
	 */
	protected function generateDumpForEntityId( EntityId $entityId ) {
		try {
			$entityRevision = $this->entityRevisionLookup->getEntityRevision( $entityId );

			if ( !$entityRevision ) {
				throw new StorageException( 'Entity not found: ' . $entityId->getSerialization() );
			}
		} catch ( MWContentSerializationException $ex ) {
			throw new StorageException( 'Deserialization error for ' . $entityId->getSerialization() );
		} catch ( UnresolvedRedirectException $e ) {
			return null;
		}

		return $this->entitySerializer->serializeEntityRevision( $entityRevision );
	}

	/**
	 * @param int $timestamp
	 */
	public function setTimestamp( $timestamp ) {
		$this->timestamp = (int)$timestamp;
	}

	/**
	 * @param string $format
	 * @param resource $output
	 * @param string $baseUri
	 * @param string $dataUri
	 * @param SiteList $sites
	 * @param EntityLookup $entityLookup
	 * @param EntityRevisionLookup $entityRevisionLookup
	 * @param PropertyDataTypeLookup $propertyLookup
	 *
	 * @throws MWException
	 * @return RdfDumpGenerator
	 */
	public static function createDumpGenerator(
			$format,
			$output,
			$baseUri,
			$dataUri,
			SiteList $sites,
			EntityLookup $entityLookup,
			EntityRevisionLookup $entityRevisionLookup,
			PropertyDataTypeLookup $propertyLookup
	) {
		$rdfFormat = RdfSerializer::getRdfWriter( $format );
		if( !$rdfFormat ) {
			throw new MWException( "Unknown format: $format" );
		}
		$entitySerializer = new RdfSerializer( $rdfFormat,
				$baseUri,
				$dataUri,
				$sites,
				$propertyLookup,
				$entityLookup,
				RdfProducer::PRODUCE_ALL_STATEMENTS | RdfProducer::PRODUCE_TRUTHY_STATEMENTS |
				RdfProducer::PRODUCE_QUALIFIERS | RdfProducer::PRODUCE_REFERENCES |
				RdfProducer::PRODUCE_SITELINKS | RdfProducer::PRODUCE_FULL_VALUES,
				new HashBagOStuff()
		);
		return new RdfDumpGenerator( $output, $entityRevisionLookup, $entitySerializer );
	}

}
