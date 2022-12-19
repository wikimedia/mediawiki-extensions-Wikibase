<?php

namespace Wikibase\Repo\Tests\Rdf;

use HashSiteStore;
use InvalidArgumentException;
use MediaWiki\Revision\SlotRecord;
use Site;
use SiteLookup;
use Wikibase\DataAccess\DatabaseEntitySource;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataAccess\PrefetchingTermLookup;
use Wikibase\DataAccess\Tests\InMemoryPrefetchingTermLookup;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\Lib\Store\EntityContentDataCodec;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\RedirectRevision;
use Wikibase\Lib\SubEntityTypesMapper;
use Wikibase\Lib\Tests\MockEntityRevisionLookup;
use Wikibase\Lib\Tests\MockRepository;
use Wikibase\Repo\Rdf\RdfVocabulary;
use Wikibase\Repo\WikibaseRepo;
use Wikimedia\Purtle\NTriplesRdfWriter;

/**
 * Helper class for accessing data files for RdfBuilder related tests.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Stas Malyshev
 */
class RdfBuilderTestData {

	public const URI_BASE = 'http://acme.test/';
	public const URI_DATA = 'http://data.acme.test/';

	private const URI_BASE_FOREIGN = 'http://foreign.test/';
	private const URI_DATA_FOREIGN = 'http://data.foreign.test/';

	/**
	 * @var EntityContentDataCodec|null
	 */
	private $codec = null;

	/**
	 * @var string
	 */
	private $dataDir;

	/**
	 * @var string
	 */
	private $entityDir;

	/**
	 * @param string $entityDir directory containing entity data (JSON files)
	 * @param string $dataDir directory containing RDF data (n-triples files)
	 */
	public function __construct( $entityDir, $dataDir ) {
		$this->entityDir = $entityDir;
		$this->dataDir = $dataDir;
	}

	/**
	 * @return EntityContentDataCodec
	 */
	private function getCodec() {
		if ( $this->codec === null ) {
			$this->codec = WikibaseRepo::getEntityContentDataCodec();
		}

		return $this->codec;
	}

	/**
	 * Load entity from JSON
	 *
	 * @param string $idString
	 *
	 * @return EntityDocument
	 */
	public function getEntity( $idString ) {
		return $this->getCodec()->decodeEntity(
			file_get_contents( "{$this->entityDir}/$idString.json" ),
			CONTENT_FORMAT_JSON
		);
	}

	/**
	 * @param string $dataSetName
	 *
	 * @return string
	 */
	private function getDataSetFileName( $dataSetName ) {
		return "{$this->dataDir}/$dataSetName.nt";
	}

	/**
	 * @param string $dataSetName
	 *
	 * @return bool
	 */
	public function hasDataSet( $dataSetName ) {
		return file_exists( $this->getDataSetFileName( $dataSetName ) );
	}

	/**
	 * Load serialized ntriples.
	 *
	 * @param string... $dataSets one or more data set names
	 *
	 * @return string N-Triples
	 */
	public function getNTriples( ...$dataSets ) {
		$triples = [];

		foreach ( $dataSets as $dataSetName ) {
			$filename = $this->getDataSetFileName( $dataSetName );

			if ( !file_exists( $filename ) || !is_readable( $filename ) ) {
				throw new InvalidArgumentException( 'No such file: ' . $filename );
			}

			$lines = file( $filename );
			$lines = array_map( 'trim', $lines );
			$triples = array_merge( $triples,  $lines );
		}

		$triples = array_unique( $triples );

		return $triples;
	}

	/**
	 * Writes the given ntriples lines to the test data file with the given name.
	 * Existing files will not be overwritten.
	 *
	 * @note This is intended as a helper function for building test cases, it should
	 *       not be used while testing.
	 *
	 * @param string $dataSetName
	 * @param string[]|string $lines
	 * @param string $suffix File name suffix
	 *
	 * @return string The filename the data was written to, or false if no data was written.
	 */
	public function putTestData( $dataSetName, $lines, $suffix = '' ) {
		$filename = $this->getDataSetFileName( $dataSetName ) . $suffix;

		$data = implode( "\n", (array)$lines );
		file_put_contents( $filename, $data );

		return $filename;
	}

	/**
	 * Returns the vocabulary to use with the test data.
	 *
	 * @return RdfVocabulary
	 */
	public function getVocabulary() {
		return new RdfVocabulary(
			[ 'wikidata' => self::URI_BASE, 'foreign' => self::URI_BASE_FOREIGN ],
			[ 'wikidata' => self::URI_DATA, 'foreign' => self::URI_DATA_FOREIGN ],
			new EntitySourceDefinitions(
				[
					new DatabaseEntitySource(
						'wikidata',
						'wikidatadb',
						[ 'item' => [ 'namespaceId' => 700, 'slot' => SlotRecord::MAIN ] ],
						self::URI_BASE,
						'wd',
						'',
						''
					),
					new DatabaseEntitySource(
						'foreign',
						'foreigndb',
						[ 'property' => [ 'namespaceId' => 900, 'slot' => SlotRecord::MAIN ] ],
						self::URI_BASE_FOREIGN,
						'for',
						'for',
						''
					),
				],
				new SubEntityTypesMapper( [] )
			),
			[ 'wikidata' => '', 'foreign' => 'foreign' ],
			[ 'wikidata' => '', 'foreign' => 'foreign' ],
			[],
			[],
			[],
			'http://creativecommons.org/publicdomain/zero/1.0/'
		);
	}

	/**
	 * Returns a new NTriplesRdfWriter, with vocabulary namespaces registered.
	 *
	 * @param bool $start whether to call start() on the writer.
	 *
	 * @return NTriplesRdfWriter
	 */
	public function getNTriplesWriter( $start = true ) {
		$writer = new NTriplesRdfWriter( NTriplesRdfWriter::DOCUMENT_ROLE, new NoopBNodeLabeler() );

		foreach ( $this->getVocabulary()->getNamespaces() as $ns => $uri ) {
			$writer->prefix( $ns, $uri );
		}

		if ( $start ) {
			$writer->start();
		}

		return $writer;
	}

	/**
	 * Get site definitions matching the test data.
	 *
	 * @return SiteLookup
	 */
	public function getSiteLookup() {
		$list = [];

		$wiki = new Site();
		$wiki->setGlobalId( 'enwiki' );
		$wiki->setLanguageCode( 'en' );
		$wiki->setLinkPath( 'http://enwiki.acme.test/$1' );
		$wiki->setGroup( 'wikipedia' );
		$list['enwiki'] = $wiki;

		$wiki = new Site();
		$wiki->setGlobalId( 'ruwiki' );
		$wiki->setLanguageCode( 'ru' );
		$wiki->setLinkPath( 'http://ruwiki.acme.test/$1' );
		$list['ruwiki'] = $wiki;

		return new HashSiteStore( $list );
	}

	/**
	 * Define a set of fake properties
	 * @return array[] A list of properties used in the test data. Each element is a pair
	 *         of an NumericPropertyId and a data type ID.
	 */
	public static function getTestProperties() {
		return [
			[ new NumericPropertyId( 'P2' ), 'wikibase-item' ],
			[ new NumericPropertyId( 'P3' ), 'commonsMedia' ],
			[ new NumericPropertyId( 'P4' ), 'globe-coordinate' ],
			[ new NumericPropertyId( 'P5' ), 'monolingualtext' ],
			[ new NumericPropertyId( 'P6' ), 'quantity' ],
			[ new NumericPropertyId( 'P7' ), 'string' ],
			[ new NumericPropertyId( 'P8' ), 'time' ],
			[ new NumericPropertyId( 'P9' ), 'url' ],
			[ new NumericPropertyId( 'P10' ), 'geo-shape' ],
			[ new NumericPropertyId( 'P11' ), 'external-id' ],
			[ new NumericPropertyId( 'foreign:P12' ), 'string' ],
			[ new NumericPropertyId( 'foreign:P13' ), 'wikibase-item' ],
		];
	}

	/**
	 * @return array[] A list of properties used in the test data. Each element is a pair
	 *         of an NumericPropertyId and a data type ID.
	 */
	public static function getTestProperties_noPrefixedIds() {
		return [
			[ new NumericPropertyId( 'P2' ), 'wikibase-item' ],
			[ new NumericPropertyId( 'P3' ), 'commonsMedia' ],
			[ new NumericPropertyId( 'P4' ), 'globe-coordinate' ],
			[ new NumericPropertyId( 'P5' ), 'monolingualtext' ],
			[ new NumericPropertyId( 'P6' ), 'quantity' ],
			[ new NumericPropertyId( 'P7' ), 'string' ],
			[ new NumericPropertyId( 'P8' ), 'time' ],
			[ new NumericPropertyId( 'P9' ), 'url' ],
			[ new NumericPropertyId( 'P10' ), 'geo-shape' ],
			[ new NumericPropertyId( 'P11' ), 'external-id' ],
		];
	}

	public function getMockTermLookup( bool $loadEntitiesIfNotPrefetched = true ): PrefetchingTermLookup {
		$termLookup = new InMemoryPrefetchingTermLookup( $loadEntitiesIfNotPrefetched );
		$entities = [];
		foreach ( self::getTestProperties() as $propertyTypeMapping ) {
			/** @var NumericPropertyId $id */
			$id = $propertyTypeMapping[0];
			$fingerprint = new Fingerprint();
			$fingerprint->setLabel( 'en', 'Property' . $id->getNumericId() );
			$property = new Property( $id,
				$fingerprint,
				$propertyTypeMapping[1] );

			$entities[] = $property;
		}
		$itemId = new ItemId( 'Q42' );
		$fingerprint = new Fingerprint();
		$fingerprint->setLabel( 'en', 'Item42' );
		$fingerprint->setAliasGroup( 'en', [ 'Item42' ] );
		$q42 = new Item( $itemId, $fingerprint );
		$entities[] = $q42;
		$q2 = $this->getEntity( 'Q2' );
		$entities[] = $q2;
		$termLookup->setData( $entities );
		return $termLookup;
	}

	public function getMockEntityRevsionLookup() {
		$revisionNumber = 1;
		$q42string = 'Q42';
		$q4242 = 'Q4242';
		$q42Id = new ItemId( $q42string );
		$redirects = [
			$q4242 => new RedirectRevision(
				new EntityRedirect( new ItemId( $q4242 ),
					$q42Id ),
				$revisionNumber
			),
		];

		$entities = [];
		foreach ( self::getTestProperties() as $propertyTypeMapping ) {
			$revisionNumber++;
			/** @var NumericPropertyId $id */
			$id = $propertyTypeMapping[0];
			$fingerprint = new Fingerprint();
			$fingerprint->setLabel( 'en', 'Property' . $id->getNumericId() );
			$property = new Property( $id,
				$fingerprint,
				$propertyTypeMapping[1] );

			$entities[ $id->getSerialization() ][$revisionNumber] = new EntityRevision(
				$property,
				$revisionNumber
			);
		}

		$revisionNumber++;
		$fingerprint = new Fingerprint();
		$fingerprint->setLabel( 'en', 'Item42' );
		$q42Item = new Item( $q42Id, $fingerprint );
		$entities[ $q42Id->getSerialization() ][$revisionNumber] = new EntityRevision(
			$q42Item,
			$revisionNumber
		);

		return new MockEntityRevisionLookup( $redirects, $entities );
	}

	/**
	 * Construct mock repository matching the test data.
	 *
	 * @return MockRepository
	 */
	public function getMockRepository() {
		static $repo;

		if ( !empty( $repo ) ) {
			return $repo;
		}

		$repo = new MockRepository();

		foreach ( self::getTestProperties() as list( $id, $type ) ) {
			$fingerprint = new Fingerprint();
			$fingerprint->setLabel( 'en', 'Property' . $id->getNumericId() );
			$entity = new Property( $id, $fingerprint, $type );
			$repo->putEntity( $entity );
		}

		return $repo;
	}

}
