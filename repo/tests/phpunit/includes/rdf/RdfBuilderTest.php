<?php

namespace Wikibase\Test;

use SiteList;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Term\Fingerprint;
use Wikimedia\Purtle\NTriplesRdfWriter;
use Wikibase\RdfBuilder;
use Wikibase\RdfProducer;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;

/**
 * @covers Wikibase\RdfBuilder
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseRdf
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class RdfBuilderTest extends \MediaWikiTestCase {

	const URI_BASE = 'http://acme.test/';
	const URI_DATA = 'http://data.acme.test/';

	private $codec;

	/**
	 * Initialize repository data
	 */
	private function getCodec()
	{
		if( empty($this->codec) ) {
			$wikibaseRepo = WikibaseRepo::getDefaultInstance();
	        $wikibaseRepo->getSettings()->setSetting( 'internalEntitySerializerClass', null );
	        $wikibaseRepo->getSettings()->setSetting( 'useRedirectTargetColumn', true );
	        $this->codec = $wikibaseRepo->getEntityContentDataCodec();
		}
		return $this->codec;
	}

	/**
	 * Define a set of fake properties
	 * @return array
	 */
	private static function getTestProperties() {
		return array(
				array(2, 'wikibase-entityid'),
				array(3, 'commonsMedia'),
				array(4, 'globecoordinate'),
				array(5, 'monolingualtext'),
				array(6, 'quantity'),
				array(7, 'string'),
				array(8, 'time'),
				array(9, 'url'),
		);
	}

	/**
	 * Construct mock repository
	 * @return \Wikibase\Test\MockRepository
	 */
	public static function getMockRepository() {
		static $repo;

		if ( !empty($repo) ) {
			return $repo;
		}

		$repo = new MockRepository();

		foreach( self::getTestProperties() as $prop ) {
			list($id, $type) = $prop;
			$fingerprint = Fingerprint::newEmpty();
			$fingerprint->setLabel( 'en', "Property$id" );
			$entity = new Property( PropertyId::newFromNumber($id), $fingerprint, $type );
			$repo->putEntity( $entity );
		}
		$fingerprint = Fingerprint::newEmpty();
		$fingerprint->setLabel( 'en', "Item42" );
		$entity = new Item( ItemId::newFromNumber(42), $fingerprint );
		$repo->putEntity( $entity );
		return $repo;
	}

	/**
	 * @return RdfBuilder
	 */
	private static function newRdfBuilder( $produce, \BagOStuff $dedup = null ) {
		if( !$dedup ) {
			$dedup = new \HashBagOStuff();
		}
		$emitter = new NTriplesRdfWriter();
		$builder = new RdfBuilder(
			self::getSiteList(),
			self::URI_BASE,
			self::URI_DATA,
			self::getMockRepository(),
			$produce,
			$emitter,
			$dedup
		);
		$builder->startDocument();
		return $builder;
	}

	/**
	 * Get site list
	 * @return \SiteList
	 */
	public static function getSiteList() {
		$list = new SiteList();

		$wiki = new \Site();
		$wiki->setGlobalId( 'enwiki' );
		$wiki->setLanguageCode( 'en' );
		$wiki->setLinkPath( 'http://enwiki.acme.test/$1' );
		$list['enwiki'] = $wiki;

		$wiki = new \Site();
		$wiki->setGlobalId( 'ruwiki' );
		$wiki->setLanguageCode( 'ru' );
		$wiki->setLinkPath( 'http://ruwiki.acme.test/$1' );
		$list['ruwiki'] = $wiki;

		return $list;
	}

	/**
	 * Load entity from JSON
	 * @param string $entityId
	 * @return Entity
	 */
	public function getEntityData( $entityId )
	{
		return $this->getCodec()->decodeEntity(
			file_get_contents(__DIR__ . "/../../data/rdf/$entityId.json"), CONTENT_FORMAT_JSON );
	}

	/**
	 * Load serialized ntriples
	 * @param string $testName
	 * @return array
	 */
	public function getSerializedData( $testName )
	{
		$filename = __DIR__ . "/../../data/rdf/$testName.nt";
		if ( !file_exists( $filename ) )
		{
			return array ();
		}
		$data = trim( file_get_contents( $filename ) );
		$data = explode( "\n", $data );
		sort( $data );
		return $data;
	}

	public function getRdfTests() {
		$rdfTests = array(
				array('Q1', 'Q1_simple'),
				array('Q2', 'Q2_labels'),
				array('Q3', 'Q3_links'),
				array('Q4', 'Q4_claims'),
				array('Q5', 'Q5_badges'),
				array('Q6', 'Q6_qualifiers'),
				array('Q7', 'Q7_references'),
				array('Q8', 'Q8_baddates'),
		);

		$testData = array();
		foreach ( $rdfTests as $test ) {
			$testData[$test[1]] = array (
					$this->getEntityData( $test[0] ),
					$this->getSerializedData( $test[1] )
			);
		}
		return $testData;
	}

	/**
	 * Extract text test data from RDF builder
	 * @param RdfBuilder $builder
	 * @return string[] ntriples lines
	 */
	private function getDataFromBuilder( RdfBuilder $builder ) {
		$data = $builder->getRDF();
		$dataSplit = explode( "\n", trim( $data ) );
		sort( $dataSplit );
		return $dataSplit;
	}

	/**
	 * @dataProvider getRdfTests
	 */
	public function testRdfBuild( Entity $entity, array $correctData ) {
		$builder = self::newRdfBuilder( RdfProducer::PRODUCE_ALL_STATEMENTS |
				RdfProducer::PRODUCE_TRUTHY_STATEMENTS |
				RdfProducer::PRODUCE_QUALIFIERS |
				RdfProducer::PRODUCE_REFERENCES |
				RdfProducer::PRODUCE_SITELINKS |
				RdfProducer::PRODUCE_VERSION_INFO |
				RdfProducer::PRODUCE_FULL_VALUES);
		$builder->addEntity( $entity );
		$builder->addEntityRevisionInfo( $entity->getId(), 42, "2014-11-04T03:11:05Z" );
		$this->assertEquals( $correctData, $this->getDataFromBuilder( $builder ) );
	}

	public function getProduceOptions() {
		$produceTests = array(
			array( 'Q4', RdfProducer::PRODUCE_ALL_STATEMENTS, 'Q4_all_statements' ),
			array( 'Q4', RdfProducer::PRODUCE_TRUTHY_STATEMENTS, 'Q4_truthy_statements' ),
			array( 'Q6', RdfProducer::PRODUCE_ALL_STATEMENTS, 'Q6_no_qualifiers' ),
			array( 'Q6', RdfProducer::PRODUCE_ALL_STATEMENTS | RdfProducer::PRODUCE_QUALIFIERS, 'Q6_with_qualifiers' ),
			array( 'Q7', RdfProducer::PRODUCE_ALL_STATEMENTS , 'Q7_no_refs' ),
			array( 'Q7', RdfProducer::PRODUCE_ALL_STATEMENTS | RdfProducer::PRODUCE_REFERENCES, 'Q7_refs' ),
			array( 'Q3', RdfProducer::PRODUCE_SITELINKS, 'Q3_sitelinks' ),
			array( 'Q4', RdfProducer::PRODUCE_ALL_STATEMENTS | RdfProducer::PRODUCE_PROPERTIES, 'Q4_props' ),
			array( 'Q4', RdfProducer::PRODUCE_ALL_STATEMENTS | RdfProducer::PRODUCE_FULL_VALUES, 'Q4_values' ),
			array( 'Q1', RdfProducer::PRODUCE_VERSION_INFO, 'Q1_info' ),
			array( 'Q4', RdfProducer::PRODUCE_TRUTHY_STATEMENTS | RdfProducer::PRODUCE_RESOLVED_ENTITIES, 'Q4_resolved' ),
		);

		$testData = array();
		foreach($produceTests as $test) {
			$testData[$test[2]] = array( $this->getEntityData($test[0]), $test[1], $this->getSerializedData($test[2]) );
		}
		return $testData;

	}

	/**
	 * @dataProvider getProduceOptions
	 */
	public function testRdfOptions( Entity $entity, $produceOption, array $correctData ) {
		$builder = self::newRdfBuilder( $produceOption );
		$builder->addEntity( $entity );
		$builder->addEntityRevisionInfo( $entity->getId(), 42, "2013-10-04T03:31:05Z" );
		$builder->resolveMentionedEntities( self::getMockRepository() );
		$data = $this->getDataFromBuilder( $builder );
		$this->assertEquals( $correctData, $data);
	}

	public function testDumpHeader() {
		$builder = self::newRdfBuilder( RdfProducer::PRODUCE_VERSION_INFO );
		$builder->addDumpHeader( 1426110695 );
		$data = $this->getDataFromBuilder( $builder );
		$this->assertEquals( $this->getSerializedData( 'dumpheader' ),  $data);
	}

	public function testDeduplication() {
		$bag = new \HashBagOStuff();
		$builder = self::newRdfBuilder( RdfProducer::PRODUCE_ALL, $bag );
		$builder->addEntity( $this->getEntityData( 'Q7' ) );
		$data1 = $this->getDataFromBuilder( $builder );

		$builder = self::newRdfBuilder( RdfProducer::PRODUCE_ALL, $bag );
		$builder->addEntity( $this->getEntityData( 'Q9' ) );
		$data2 = $this->getDataFromBuilder( $builder );

		$data = array_merge($data1, $data2);
		sort($data);

		$this->assertArrayEquals($this->getSerializedData( 'Q7_Q9_dedup' ), $data);
	}

}
