<?php

namespace Wikibase\Test\Dumpers;

use ArrayObject;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Dumpers\JsonDumpGenerator;
use Wikibase\Entity;
use Wikibase\EntityContentFactory;
use Wikibase\EntityFactory;
use Wikibase\Item;
use Wikibase\Property;
use Wikibase\StorageException;

/**
 * @covers Wikibase\Dumpers\JsonDumpGenerator
 *
 * @group Wikibase
 * @group WikibaseLib
 * @group JsonDump
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class JsonDumpGeneratorTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @param EntityId[] $ids
	 *
	 * @return Entity[]
	 */
	protected function makeEntities( array $ids ) {
		$entities = array();

		/* @var EntityId $id */
		foreach ( $ids as $id ) {
			$entity = EntityFactory::singleton()->newEmpty( $id->getEntityType() );
			$entity->setId( $id );

			$key = $id->getPrefixedId();
			$entities[$key] = $entity;
		}

		return $entities;
	}

	/**
	 * @param EntityId[] $ids
	 *
	 * @return JsonDumpGenerator
	 */
	protected function newDumpGenerator( array $ids = array(), array $missingIds = array() ) {
		$out = fopen( 'php://output', 'w' );

		$serializer = $this->getMock( 'Wikibase\Lib\Serializers\Serializer' );
		$serializer->expects( $this->any() )
			->method( 'getSerialized' )
			->will( $this->returnCallback( function ( Entity $entity ) {
						return array(
							'id' => $entity->getId()->getPrefixedId()
						);
					}
			) );

		$entities = $this->makeEntities( $ids );

		$entityLookup = $this->getMock( 'Wikibase\EntityLookup' );
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
	 * @dataProvider idProvider
	 */
	public function testGenerateDump( array $ids ) {
		$this->testTypeFilterDump( $ids, null, $ids );
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
		$idList = new ArrayObject( $ids );

		$dumper->setEntityTypeFilter( $type );

		ob_start();
		$dumper->generateDump( $idList );
		$json = ob_get_clean();

		// check that the resulting json contains all the ids we asked for.
		$data = json_decode( $json, true );

		$this->assertTrue( is_array( $data ), 'decode failed: ' . $json );

		$actualIds = array_map( function( $entityData ) {
			return $entityData['id'];
		}, $data );

		$this->assertEquals( $expectedIds, $actualIds );
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
		$dumper = $this->newDumpGenerator( $ids );
		$idList = new ArrayObject( $ids );

		$actualIds = array();

		// Generate and check a dump for each shard,
		// then combine the results and check again.
		for ( $shard = 0; $shard < $shardingFactor; $shard++ ) {
			$dumper->setShardingFilter( $shardingFactor, $shard );

			// Generate the dump and grab the output
			ob_start();
			$dumper->generateDump( $idList );
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
		$idList = new ArrayObject( $ids );

		$exceptionHandler = $this->getMock( 'ExceptionHandler' );
		$exceptionHandler->expects( $this->exactly( count( $missingIds ) ) )
			->method( 'handleException' );

		$dumper->setExceptionHandler( $exceptionHandler );

		ob_start();
		$dumper->generateDump( $idList );
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
		$idList = new ArrayObject( $ids );

		$progressReporter = $this->getMock( 'MessageReporter' );
		$progressReporter->expects( $this->exactly( ( count( $ids ) / 10 ) +1 ) )
			->method( 'reportMessage' );

		$dumper->setProgressInterval( 10 );
		$dumper->setProgressReporter( $progressReporter );

		ob_start();
		$dumper->generateDump( $idList );
		ob_end_clean();
	}
}
