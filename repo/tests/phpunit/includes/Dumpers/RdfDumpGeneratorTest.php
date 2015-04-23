<?php

namespace Wikibase\Test\Dumpers;

use MWException;
use PHPUnit_Framework_TestCase;
use Site;
use SiteList;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Dumpers\RdfDumpGenerator;
use Wikibase\EntityRevision;
use Wikibase\Lib\Store\EntityLookup;
use Wikibase\Lib\Store\UnresolvedRedirectException;
use Wikibase\Lib\Store\NullEntityPrefetcher;
use Wikibase\Test\Rdf\RdfBuilderTest;

/**
 * @covers Wikibase\Dumpers\RdfDumpGenerator
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseRdf
 * @group RdfDump
 *
 * @license GPL 2+
 */
class RdfDumpGeneratorTest extends PHPUnit_Framework_TestCase {

	const URI_BASE = 'http://acme.test/';
	const URI_DATA = 'http://data.acme.test/';

	/**
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

		$wiki = new Site();
		$wiki->setGlobalId( 'test' );
		$wiki->setLanguageCode( 'test' );
		$wiki->setLinkPath( 'http://test.acme.test/$1' );
		$list['test'] = $wiki;

		return $list;
	}

	/**
	 * @param Entity[] $entities
	 * @param EntityId[] $redirects
	 *
	 * @return RdfDumpGenerator
	 * @throws MWException
	 */
	protected function newDumpGenerator( array $entities = array(), array $redirects = array() ) {
		$out = fopen( 'php://output', 'w' );

		$entityLookup = $this->getMock( 'Wikibase\Lib\Store\EntityLookup' );
		$entityRevisionLookup = $this->getMock( 'Wikibase\Lib\Store\EntityRevisionLookup' );
		$propertyLookup = $this->getMock( 'Wikibase\DataModel\Entity\PropertyDataTypeLookup' );

		$entityLookup->expects( $this->any() )
			->method( 'getEntity' )
			->will( $this->returnCallback( function( EntityId $id ) use ( $entities, $redirects ) {
				$key = $id->getSerialization();

				if ( isset( $redirects[$key] ) ) {
					throw new UnresolvedRedirectException( $redirects[$key] );
				}

				if ( isset( $entities[$key] ) ) {
					return $entities[$key];
				}

				return null;
			} ) );

		$entityRevisionLookup->expects( $this->any() )
			->method ( 'getEntityRevision' )
			->will( $this->returnCallback( function( EntityId $id ) use ( $entityLookup ) {
				/** @var EntityLookup $entityLookup */
				$entity = $entityLookup->getEntity( $id );
				if ( !$entity ) {
					return null;
				}
				return new EntityRevision( $entity, 12, wfTimestamp( TS_MW, 1000000 ) );
			}
		));

		return RdfDumpGenerator::createDumpGenerator(
			'ntriples',
			$out,
			self::URI_BASE,
			self::URI_DATA,
			$this->getSiteList(),
			$entityRevisionLookup,
			$propertyLookup,
			new NullEntityPrefetcher()
		);
	}

	public function idProvider() {
		$p10 = new PropertyId( 'P10' );
		$q30 = new ItemId( 'Q30' );
		$q4242 = new ItemId( 'Q4242' ); // hardcoded to be a redirect

		return array(
			'empty' => array( array(), 'empty' ),
			'some entities' => array( array( $p10, $q30 ), 'entities' ),
			'redirect' => array( array( $p10, $q4242 ), 'redirect' ),
		);
	}

	/**
	 * Brings data to normalized form - sorted array of lines
	 *
	 * @param string $data
	 *
	 * @return string[]
	 */
	public function normalizeData($data) {
		$dataSplit = explode( "\n", $data );
		sort( $dataSplit );
		return $dataSplit;
	}

	/**
	 * Load serialized ntriples
	 *
	 * @param string $testName
	 *
	 * @return string[]
	 */
	public function getSerializedData( $testName ) {
		$filename = __DIR__ . "/../../data/rdf/dump_$testName.nt";
		if ( !file_exists( $filename ) ) {
			return array();
		}
		return $this->normalizeData( file_get_contents( $filename ) );
	}

	/**
	 * @dataProvider idProvider
	 */
	public function testGenerateDump( array $ids, $dumpname ) {
		$jsonTest = new JsonDumpGeneratorTest();
		$entities = $jsonTest->makeEntities( $ids );
		$redirects = array( 'Q4242' => new ItemId( 'Q42' ) );
		$dumper = $this->newDumpGenerator( $entities, $redirects );
		$dumper->setTimestamp(1000000);
		$jsonTest = new JsonDumpGeneratorTest();
		$pager = $jsonTest->makeIdPager( $ids );

		ob_start();
		$dumper->generateDump( $pager );
		$dump = ob_get_clean();
		$dump = $this->normalizeData( $dump );
		$this->assertEquals( $this->getSerializedData( $dumpname ), $dump );
	}

	public function loadDataProvider() {
		return array(
			'references' => array( array( new ItemId( 'Q7' ), new ItemId( 'Q9' ) ), 'refs' ),
		);
	}

	/**
	 * @dataProvider loadDataProvider
	 * @param EntityId[] $ids
	 * @param string $dumpname
	 */
	public function testReferenceDedup( array $ids, $dumpname ) {
		$entities = array();
		$rdfTest = new RdfBuilderTest();

		foreach ( $ids as $id ) {
			$id = $id->getSerialization();
			$entities[$id] = $rdfTest->getEntityData( $id );
		}

		$dumper = $this->newDumpGenerator( $entities );
		$dumper->setTimestamp( 1000000 );
		$jsonTest = new JsonDumpGeneratorTest();
		$pager = $jsonTest->makeIdPager( $ids );

		ob_start();
		$dumper->generateDump( $pager );
		$dump = ob_get_clean();
		$dump = $this->normalizeData( $dump );
		$this->assertEquals( $this->getSerializedData( $dumpname ), $dump );
	}

}
