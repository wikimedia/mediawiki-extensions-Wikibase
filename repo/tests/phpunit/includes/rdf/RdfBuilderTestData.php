<?php

namespace Wikibase\Test\Rdf;

use Site;
use SiteList;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\Lib\Store\EntityContentDataCodec;
use Wikibase\Rdf\ComplexValueRdfBuilder;
use Wikibase\Rdf\DedupeBag;
use Wikibase\Rdf\EntityMentionListener;
use Wikibase\Rdf\JulianDateTimeValueCleaner;
use Wikibase\Rdf\RdfVocabulary;
use Wikibase\Rdf\Values\CommonsMediaRdfBuilder;
use Wikibase\Rdf\Values\ComplexValueRdfHelper;
use Wikibase\Rdf\Values\EntityIdRdfBuilder;
use Wikibase\Rdf\Values\GlobeCoordinateRdfBuilder;
use Wikibase\Rdf\Values\LiteralValueRdfBuilder;
use Wikibase\Rdf\Values\MonolingualTextRdfBuilder;
use Wikibase\Rdf\Values\ObjectValueRdfBuilder;
use Wikibase\Rdf\Values\QuantityRdfBuilder;
use Wikibase\Rdf\Values\TimeRdfBuilder;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Test\MockRepository;
use Wikimedia\Purtle\NTriplesRdfWriter;
use Wikimedia\Purtle\RdfWriter;

/**
 * Helper class for accessing data files for RdfBuilder related tests.
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseRdf
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Stas Malyshev
 */
class RdfBuilderTestData {

	const URI_BASE = 'http://acme.test/';
	const URI_DATA = 'http://data.acme.test/';

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
			$wikibaseRepo = WikibaseRepo::getDefaultInstance();
			$this->codec = $wikibaseRepo->getEntityContentDataCodec();
		}

		return $this->codec;
	}

	/**
	 * Load entity from JSON
	 *
	 * @param string $idString
	 *
	 * @return Entity
	 */
	public function getEntity( $idString ) {
		return $this->getCodec()->decodeEntity(
			file_get_contents( "{$this->entityDir}/$idString.json" ),
			CONTENT_FORMAT_JSON
		);
	}

	/**
	 * Load serialized ntriples.
	 *
	 * @param string $dataSetName
	 * @return string[]|null ntriples lines, sorted, or null if
	 *         no data file was found with the given name.
	 */
	public function getNTriples( $dataSetName ) {
		$filename = "{$this->dataDir}/$dataSetName.nt";
		if ( !file_exists( $filename ) ) {
			return null;
		}

		$data = trim( file_get_contents( $filename ) );
		$data = explode( "\n", $data );
		sort( $data );
		$data = array_map( 'trim', $data );
		return $data;
	}

	/**
	 * Writes the given ntriples lines to the test data file with the given name.
	 * Existing files will not be overwritten.
	 *
	 * @note This is intended as a helper function for building test cases, it should
	 *       not be used while testing.
	 *
	 * @param string $dataSetName
	 * @param string[] $lines
	 * @param string $suffix File name suffix
	 *
	 * @return bool|int the number of bytes that were written to the file, or
	 *         false on failure.
	 */
	public function putTestData( $dataSetName, $lines, $suffix = '' ) {
		$filename = "{$this->dataDir}/$dataSetName.nt$suffix";
		if ( file_exists( $filename ) ) {
			return false;
		}

		$data = join( "\n", (array)$lines );
		return file_put_contents( $filename, $data );
	}

	/**
	 * Returns the vocabulary to use with the test data.
	 *
	 * @return RdfVocabulary
	 */
	public function getVocabulary() {
		return new RdfVocabulary( self::URI_BASE, self::URI_DATA );
	}

	/**
	 * Returns a new NTriplesRdfWriter, with vocabulary namespaces registered.
	 *
	 * @param bool $start whether to call start() on the writer.
	 *
	 * @return NTriplesRdfWriter
	 */
	public function getNTriplesWriter( $start = true ) {
		$writer = new NTriplesRdfWriter();

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
	 * @return SiteList
	 */
	public function getSiteList() {
		$list = new SiteList();

		$wiki = new Site();
		$wiki->setGlobalId( 'enwiki' );
		$wiki->setLanguageCode( 'en' );
		$wiki->setLinkPath( 'http://enwiki.acme.test/$1' );
		$list['enwiki'] = $wiki;

		$wiki = new Site();
		$wiki->setGlobalId( 'ruwiki' );
		$wiki->setLanguageCode( 'ru' );
		$wiki->setLinkPath( 'http://ruwiki.acme.test/$1' );
		$list['ruwiki'] = $wiki;

		return $list;
	}

	/**
	 * Define a set of fake properties
	 * @return array
	 */
	private static function getTestProperties() {
		return array(
			array( 2, 'wikibase-item' ),
			array( 3, 'commonsMedia' ),
			array( 4, 'globecoordinate' ),
			array( 5, 'monolingualtext' ),
			array( 6, 'quantity' ),
			array( 7, 'string' ),
			array( 8, 'time' ),
			array( 9, 'url' ),
		);
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

		foreach ( self::getTestProperties() as $prop ) {
			list( $id, $type ) = $prop;
			$fingerprint = new Fingerprint();
			$fingerprint->setLabel( 'en', "Property$id" );
			$entity = new Property( PropertyId::newFromNumber( $id ), $fingerprint, $type );
			$repo->putEntity( $entity );
		}

		$q42 = new ItemId( 'Q42' );
		$fingerprint = new Fingerprint();
		$fingerprint->setLabel( 'en', 'Item42' );
		$entity = new Item( $q42, $fingerprint );
		$repo->putEntity( $entity );

		$repo->putRedirect( new EntityRedirect( new ItemId( 'Q4242' ), $q42 ) );

		return $repo;
	}

	public function getDataValueRdfBuilderFactoryCallbacks() {
		return array(
			'VT:wikibase-entityid' => function ( $mode, RdfVocabulary $vocab, RdfWriter $writer, EntityMentionListener $tracker, DedupeBag $dedupe ) {
				return new EntityIdRdfBuilder( $vocab, $tracker );
			},
			'VT:globecoordinate' => function ( $mode, RdfVocabulary $vocab, RdfWriter $writer, EntityMentionListener $tracker, DedupeBag $dedupe ) {
				$complexValueHelper = $mode === 'simple' ? null : new ComplexValueRdfHelper( $vocab, $writer->sub() );
				return new GlobeCoordinateRdfBuilder( $complexValueHelper );
			},
			'VT:monolingualtext' => function ( $mode, RdfVocabulary $vocab, RdfWriter $writer, EntityMentionListener $tracker, DedupeBag $dedupe ) {
				return new MonolingualTextRdfBuilder();
			},
			'VT:quantity' => function ( $mode, RdfVocabulary $vocab, RdfWriter $writer, EntityMentionListener $tracker, DedupeBag $dedupe ) {
				$complexValueHelper = $mode === 'simple' ? null : new ComplexValueRdfHelper( $vocab, $writer->sub() );
				return new QuantityRdfBuilder( $complexValueHelper );
			},
			'VT:time' => function ( $mode, RdfVocabulary $vocab, RdfWriter $writer, EntityMentionListener $tracker, DedupeBag $dedupe ) {
				// TODO: if data is fixed to be always Gregorian, replace with DateTimeValueCleaner
				$dateCleaner = new JulianDateTimeValueCleaner();
				$complexValueHelper = $mode === 'simple' ? null : new ComplexValueRdfHelper( $vocab, $writer->sub() );
				return new TimeRdfBuilder( $dateCleaner, $complexValueHelper );
			},
			'PT:url' => function ( $mode, RdfVocabulary $vocab, RdfWriter $writer, EntityMentionListener $tracker, DedupeBag $dedupe ) {
				return new ObjectValueRdfBuilder();
			},
			'PT:string' => function ( $mode, RdfVocabulary $vocab, RdfWriter $writer, EntityMentionListener $tracker, DedupeBag $dedupe ) {
				return new LiteralValueRdfBuilder( null, null );
			},
			'PT:commonsMedia' => function ( $mode, RdfVocabulary $vocab, RdfWriter $writer, EntityMentionListener $tracker, DedupeBag $dedupe ) {
				return new CommonsMediaRdfBuilder( $vocab );
			},
		);
	}
}
