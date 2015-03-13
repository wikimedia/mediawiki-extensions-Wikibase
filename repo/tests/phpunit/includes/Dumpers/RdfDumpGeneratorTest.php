<?php
namespace Wikibase\Test\Dumpers;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Dumpers\RdfDumpGenerator;
use Wikibase\RdfSerializer;
use Wikibase\RdfProducer;
use Wikibase\Lib\Store\UnresolvedRedirectException;
use Wikibase\EntityRevision;

/**
 * @covers Wikibase\Dumpers\RdfDumpGenerator
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group RdfDump
 *
 * @license GPL 2+
 */
class RdfDumpGeneratorTest extends \PHPUnit_Framework_TestCase {

	const URI_BASE = 'http://acme.test/';
	const URI_DATA = 'http://data.acme.test/';

	/**
	 * Get site list
	 * @return \SiteList
	 */
	public function getSiteList() {
		$list = new \SiteList();

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

		$wiki = new \Site();
		$wiki->setGlobalId( 'test' );
		$wiki->setLanguageCode( 'test' );
		$wiki->setLinkPath( 'http://test.acme.test/$1' );
		$list['test'] = $wiki;

		return $list;
	}

	/**
	 * @param EntityId[] $ids
	 * @param EntityId[] $missingIds
	 * @param EntityId[] $redirectedIds
	 *
	 * @return JsonDumpGenerator
	 */
	protected function newDumpGenerator( array $ids = array(), array $missingIds = array(), array $redirectedIds = array() ) {
		$out = fopen( 'php://output', 'w' );

		$jsonTest = new JsonDumpGeneratorTest();
		$entities = $jsonTest->makeEntities( $ids );
		$entityLookup = $this->getMock( 'Wikibase\Lib\Store\EntityLookup' );
		$entityRevisionLookup = $this->getMock( 'Wikibase\Lib\Store\EntityRevisionLookup' );

		$entityLookup->expects( $this->any() )
		->method( 'getEntity' )
		->will( $this->returnCallback( function( EntityId $id ) use ( $entities, $missingIds, $redirectedIds ) {
			if ( in_array( $id, $missingIds ) ) {
				return null;
			}
			if ( in_array( $id, $redirectedIds ) ) {
				throw new UnresolvedRedirectException( new ItemId( 'Q123' ) );
			}

			$key = $id->getSerialization();
			return $entities[$key];
		} ) );

		$entityRevisionLookup->expects( $this->any() )
			->method ( 'getEntityRevision' )
			->will( $this->returnCallback( function( EntityId $id ) use( $entityLookup ) {
				$e = $entityLookup->getEntity( $id );
				if( !$e ) {
					return null;
				}
				return new EntityRevision($e, 12, wfTimestamp( TS_MW, 1000000 ) );
			}
		));

		return RdfDumpGenerator::createDumpGenerator('ntriples',
				$out,
				self::URI_BASE,
				self::URI_DATA,
				$this->getSiteList(),
				$entityLookup,
				$entityRevisionLookup);
	}

	public function idProvider() {
		$p10 = new PropertyId( 'P10' );
		$q30 = new ItemId( 'Q30' );

		return array(
				'empty' => array( array(), 'empty' ),
				'some entities' => array( array( $p10, $q30 ), 'entities' ),
		);
	}

	/**
	 * Brings data to normalized form - sorted array of lines
	 * @param string $data
	 * @return array
	 */
	public function normalizeData($data) {
		$dataSplit = explode( "\n", $data );
		sort( $dataSplit );
		return $dataSplit;
	}

	/**
	 * Load serialized ntriples
	 * @param string $testName
	 * @return array
	 */
	public function getSerializedData( $testName )
	{
		$filename = __DIR__ . "/../../data/rdf/dump_$testName.nt";
		if ( !file_exists( $filename ) )
		{
			return array ();
		}
		return $this->normalizeData( file_get_contents( $filename ) );
	}

	/**
	 * @dataProvider idProvider
	 */
	public function testGenerateDump( array $ids, $dumpname ) {
		$dumper = $this->newDumpGenerator( $ids );
		$dumper->setTimestamp(1000000);
		$jsonTest = new JsonDumpGeneratorTest();
		$pager = $jsonTest->makeIdPager( $ids );

		ob_start();
		$dumper->generateDump( $pager );
		$dump = ob_get_clean();
		$dump = $this->normalizeData($dump);
		$this->assertEquals($this->getSerializedData($dumpname), $dump);

	}

}