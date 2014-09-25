<?php

namespace Wikibase\Test\Dumpers;

use MWContentSerializationException;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\SiteLink;
use Wikibase\Dumpers\JsonDumpGenerator;
use Wikibase\EntityFactory;
use Wikibase\Lib\Serializers\DispatchingEntitySerializer;
use Wikibase\Lib\Serializers\SerializationOptions;
use Wikibase\Lib\Serializers\SerializerFactory;
use Wikibase\Repo\Store\EntityIdPager;

/**
 * @covers Wikibase\Dumpers\JsonDumpGenerator
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group JsonDump
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class JsonDumpGeneratorTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @var SerializerFactory
	 */
	public $serializerFactory = null;

	/**
	 * @var SerializationOptions
	 */
	public $serializationOptions = null;

	public function setUp() {
		parent::setUp();

		$this->serializerFactory = new SerializerFactory();
		$this->serializationOptions = new SerializationOptions();
	}

	/**
	 * @param EntityId[] $ids
	 *
	 * @return Entity[]
	 */
	protected function makeEntities( array $ids ) {
		$entities = array();

		foreach ( $ids as $id ) {
			$entity = $this->makeEntity( $id );

			$key = $id->getPrefixedId();
			$entities[$key] = $entity;
		}

		return $entities;
	}

	/**
	 * @param EntityId $id
	 *
	 * @return Entity
	 */
	protected function makeEntity( EntityId $id ) {
		$entity = EntityFactory::singleton()->newEmpty( $id->getEntityType() );
		$entity->setId( $id );
		$entity->setLabel( 'en', 'label:' . $id->getSerialization() );

		if ( $entity instanceof Property ) {
			$entity->setDataTypeId( 'wibblywobbly' );
		}

		if ( $entity instanceof Item ) {
			$entity->addSiteLink( new SiteLink( 'test', 'Foo' ) );
		}

		return $entity;
	}

	/**
	 * @param EntityId[] $ids
	 * @param EntityId[] $missingIds
	 *
	 * @return JsonDumpGenerator
	 */
	protected function newDumpGenerator( array $ids = array(), array $missingIds = array() ) {
		$out = fopen( 'php://output', 'w' );

		$serializer = new DispatchingEntitySerializer( $this->serializerFactory );

		$entities = $this->makeEntities( $ids );

		$entityLookup = $this->getMock( 'Wikibase\Lib\Store\EntityLookup' );
		$entityLookup->expects( $this->any() )
			->method( 'getEntity' )
			->will( $this->returnCallback( function ( EntityId $id ) use ( $entities, $missingIds ) {
					if ( in_array( $id, $missingIds ) ) {
						return null;
					}

					$key = $id->getPrefixedId();
					return $entities[$key];
				}
			) );

		return new JsonDumpGenerator( $out, $entityLookup, $serializer );
	}

	/**
	 * Callback for providing dummy entity lists for the EntityIdPager mock.
	 *
	 * @param EntityId[] $ids
	 * @param string $entityType
	 * @param int $limit
	 * @param int $offset
	 *
	 * @return array
	 */
	public function listEntities ( array $ids, $entityType, $limit, &$offset = 0 ) {
		$result = array();
		$size = count( $ids );

		for ( ; $offset < $size && count( $result ) < $limit; $offset++ ) {
			$id = $ids[ $offset ];

			if ( $entityType !== null && $entityType !== $id->getEntityType() ) {
				continue;
			}

			$result[] = $id;
		}

		return $result;
	}

	/**
	 * @param EntityId[] $ids
	 * @param string|null $entityType
	 *
	 * @return EntityIdPager
	 */
	protected function makeIdPager( array $ids, $entityType = null ) {
		$pager = $this->getMock( 'Wikibase\Repo\Store\EntityIdPager' );

		$this_ = $this;
		$offset = 0;

		$pager->expects( $this->any() )
			->method( 'fetchIds' )
			->will( $this->returnCallback(
				function ( $limit ) use ( $ids, $entityType, &$offset, $this_ ) {
					$res = $this_->listEntities( $ids, $entityType, $limit, $offset );
					return $res;
				}
			) );

		return $pager;
	}

	/**
	 * @dataProvider idProvider
	 */
	public function testGenerateDump( array $ids ) {
		$this->testTypeFilterDump( $ids, null, $ids );
	}

	/**
	 * @dataProvider idProvider
	 */
	public function testGenerateDump_HandlesMWContentSerializationException( array $ids ) {
		$jsonDumper = $this->getJsonDumperWithExceptionHandler( $ids );
		$pager = $this->makeIdPager( $ids );

		ob_start();
		$jsonDumper->generateDump( $pager );
		$json = ob_get_clean();

		$data = json_decode( $json, true );
		$this->assertEquals( array(), $data );
	}

	private function getJsonDumperWithExceptionHandler( array $ids ) {
		$entityLookup = $this->getEntityLookupThrowsMWContentSerializationException();
		$out = fopen( 'php://output', 'w' );
		$serializer = new DispatchingEntitySerializer( $this->serializerFactory );

		$jsonDumper = new JsonDumpGenerator( $out, $entityLookup, $serializer );

		$exceptionHandler = $this->getMock( 'Wikibase\Lib\Reporting\ExceptionHandler' );
		$exceptionHandler->expects( $this->exactly( count( $ids ) ) )
			->method( 'handleException' );

		$jsonDumper->setExceptionHandler( $exceptionHandler );

		return $jsonDumper;
	}

	private function getEntityLookupThrowsMWContentSerializationException() {
		$entityLookup = $this->getMock( 'Wikibase\Lib\Store\EntityLookup' );
		$entityLookup->expects( $this->any() )
			->method( 'getEntity' )
			->will( $this->returnCallback( function ( EntityId $id ) {
					throw new MWContentSerializationException( 'cannot deserialize!' );
				}
			) );

		return $entityLookup;
	}

	public static function idProvider() {
		$p10 = new PropertyId( 'P10' );
		$q30 = new ItemId( 'Q30' );

		return array(
			'empty' => array( array() ),
			'some entities' => array( array( $p10, $q30 ) ),
		);
	}

	/**
	 * @dataProvider typeFilterProvider
	 */
	public function testTypeFilterDump( array $ids, $type, $expectedIds ) {
		$dumper = $this->newDumpGenerator( $ids );
		$pager = $this->makeIdPager( $ids );

		$dumper->setEntityTypeFilter( $type );

		ob_start();
		$dumper->generateDump( $pager );
		$json = ob_get_clean();

		// check that the resulting json contains all the ids we asked for.
		$data = json_decode( $json, true );

		$this->assertTrue( is_array( $data ), 'decode failed: ' . $json );

		$actualIds = array_map( function( $entityData ) {
			return $entityData['id'];
		}, $data );

		$this->assertEquals( $expectedIds, $actualIds );

		$idParser = new BasicEntityIdParser();

		foreach ( $data as $serialization ) {
			$id = $idParser->parse( $serialization['id'] );
			$this->assertEntitySerialization( $id, $serialization );
		}
	}

	protected function assertEntitySerialization( EntityId $id, $data ) {
		$expectedEntity = $this->makeEntity( $id );

		if ( !$expectedEntity ) {
			return;
		}

		$serializer = $this->serializerFactory->newUnserializerForEntity( $id->getEntityType(), $this->serializationOptions );
		$actualEntity = $serializer->newFromSerialization( $data );

		$this->assertTrue( $expectedEntity->equals( $actualEntity ), 'Round trip failed for ' . $id->getSerialization() );
	}

	public static function typeFilterProvider() {
		$p10 = new PropertyId( 'P10' );
		$q30 = new ItemId( 'Q30' );

		return array(
			'empty' => array( array(), null, array() ),
			'some entities' => array( array( $p10, $q30 ), null, array( $p10, $q30 ) ),
			'just properties' => array( array( $p10, $q30 ), Property::ENTITY_TYPE, array( $p10 ) ),
			'no matches' => array( array( $p10 ), Item::ENTITY_TYPE, array() ),
		);
	}

	/**
	 * @dataProvider shardingProvider
	 */
	public function testSharding( array $ids, $shardingFactor ) {
		$actualIds = array();
		$dumper = $this->newDumpGenerator( $ids );

		// Generate and check a dump for each shard,
		// then combine the results and check again.
		for ( $shard = 0; $shard < $shardingFactor; $shard++ ) {
			$pager = $this->makeIdPager( $ids );
			$dumper->setShardingFilter( $shardingFactor, $shard );

			// Generate the dump and grab the output
			ob_start();
			$dumper->generateDump( $pager );
			$json = ob_get_clean();

			// check that the resulting json contains all the ids we asked for.
			$data = json_decode( $json, true );
			$this->assertTrue( is_array( $data ), 'decode failed: ' . $json );

			$shardIds = array_map( function( $entityData ) {
				return $entityData['id'];
			}, $data );

			// check shard
			$this->assertEquals(
				array(),
				array_intersect( $actualIds, $shardIds ),
				'shard ' . $shard . ' overlaps previous shards'
			);

			// collect ids from all shards
			$actualIds = array_merge( $actualIds, $shardIds );
		}

		$expectedIds = array_map( function( EntityId $id ) {
			return $id->getPrefixedId();
		}, $ids );

		sort( $actualIds );
		sort( $expectedIds );
		$this->assertEquals( $expectedIds, $actualIds, 'bad sharding' );
	}

	public static function shardingProvider() {
		$ids = array();

		for ( $i = 10; $i < 20; $i++ ) {
			$ids[] = new PropertyId( "P$i" );
			$ids[] = new ItemId( "Q$i" );
		}

		for ( $i = 50; $i < 101; $i += 10 ) {
			$ids[] = new PropertyId( "P$i" );
			$ids[] = new ItemId( "Q$i" );
		}

		return array(
			'empty sharding' => array( array(), 2 ),
			'no sharding' => array( $ids, 1 ),
			'two shards' => array( $ids, 2 ),
			'three shards' => array( $ids, 3 ),
			'five shards' => array( $ids, 5 ),
			'ten shards' => array( $ids, 10 ),
		);
	}

	/**
	 * @dataProvider badShardingProvider
	 */
	public function testInvalidSharding( $shardingFactor, $shard ) {
		$dumper = $this->newDumpGenerator( array() );

		$this->setExpectedException( 'InvalidArgumentException' );

		$dumper->setShardingFilter( $shardingFactor, $shard );
	}

	public function badShardingProvider() {
		return array(
			array( 0, 0 ),
			array( 1, 1 ),
			array( 2, 3 ),
			array( 2, -1 ),
			array( -2, 2 ),
			array( -3, -2 ),
			array( -2, -3 ),
			array( array(), 1 ),
			array( 2, array() ),
			array( null, 1 ),
			array( 2, null ),
			array( '2', 1 ),
			array( 2, '1' ),
		);
	}

	/**
	 * @dataProvider dataProvider
	 */
	public function testEncode( $data ) {
		$dumper = $this->newDumpGenerator();
		$json = $dumper->encode( $data );

		$actual = json_decode( $json, true );
		$this->assertEquals( $data, $actual );
	}

	public static function dataProvider() {
		return array(
			'string' => array( 'bla' ),
			'list' => array( array( 'a', 'b', 'c' ) ),
			'map' => array( array( 'a' => 1, 'b' => 2, 'c' => 3 ) ),
		);
	}

	public function testExceptionHandler() {
		$ids = array();
		$missingIds = array();

		for ( $i = 1; $i<=100; $i++) {
			$id = new ItemId( "Q$i" );
			$ids[] = $id;

			if ( ( $i % 10 ) === 0 ) {
				$missingIds[] = $id;
			}
		}

		$dumper = $this->newDumpGenerator( $ids, $missingIds );
		$pager = $this->makeIdPager( $ids );

		$exceptionHandler = $this->getMock( 'Wikibase\Lib\Reporting\ExceptionHandler' );
		$exceptionHandler->expects( $this->exactly( count( $missingIds ) ) )
			->method( 'handleException' );

		$dumper->setExceptionHandler( $exceptionHandler );

		ob_start();
		$dumper->generateDump( $pager );
		$json = ob_get_clean();

		// make sure we get valid json even if there were exceptions.
		$data = json_decode( $json, true );
		$this->assertInternalType( 'array', $data, 'invalid json generated' );
	}

	public function testProgressReporter() {
		$ids = array();

		for ( $i = 1; $i<=100; $i++) {
			$id = new ItemId( "Q$i" );
			$ids[] = $id;
		}

		$dumper = $this->newDumpGenerator( $ids );
		$pager = $this->makeIdPager( $ids );

		$progressReporter = $this->getMock( 'Wikibase\Lib\Reporting\MessageReporter' );
		$progressReporter->expects( $this->exactly( count( $ids ) / 10 ) )
			->method( 'reportMessage' );

		$dumper->setBatchSize( 10 );
		$dumper->setProgressReporter( $progressReporter );

		ob_start();
		$dumper->generateDump( $pager );
		ob_end_clean();
	}

	/**
	 * @dataProvider useSnippetsProvider
	 */
	public function testSnippets(
		array $ids,
		$shardingFactor,
		$shard
	) {
		$dumper = $this->newDumpGenerator( $ids );
		$dumper->setUseSnippets( true );

		$pager = $this->makeIdPager( $ids );
		$dumper->setShardingFilter( $shardingFactor, $shard );

		// Generate the dump and grab the output
		ob_start();
		$dumper->generateDump( $pager );
		$json = trim( ob_get_clean() );

		$this->assertStringStartsWith( '{', $json, 'Snippet starts with {' );
		$this->assertStringEndsWith( '}', $json, 'Snippet ends with }' );
	}

	public static function useSnippetsProvider() {
		$ids = array();

		for ( $i = 1; $i < 5; $i++ ) {
			$ids[] = new ItemId( "Q$i" );
		}

		return array(
			// Only one shard
			array( $ids, 1, 0, ),

			// Three shards
			array( $ids, 3, 0, ),
			array( $ids, 3, 1, ),
			array( $ids, 3, 2, ),
		);
	}

}
