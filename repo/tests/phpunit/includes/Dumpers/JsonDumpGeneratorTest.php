<?php

namespace Wikibase\Repo\Tests\Dumpers;

use DataValues\Serializers\DataValueSerializer;
use Exception;
use InvalidArgumentException;
use MWContentSerializationException;
use Onoi\MessageReporter\MessageReporter;
use stdClass;
use Wikibase\DataModel\Deserializers\DeserializerFactory;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Serializers\SerializerFactory;
use Wikibase\DataModel\Services\Entity\NullEntityPrefetcher;
use Wikibase\DataModel\Services\EntityId\EntityIdPager;
use Wikibase\DataModel\Services\Lookup\EntityLookupException;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\Lib\Reporting\ExceptionHandler;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\RevisionedUnresolvedRedirectException;
use Wikibase\Repo\Dumpers\JsonDumpGenerator;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\Dumpers\JsonDumpGenerator
 * @covers \Wikibase\Repo\Dumpers\DumpGenerator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Addshore
 */
class JsonDumpGeneratorTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @var SerializerFactory|null
	 */
	private $serializerFactory = null;

	/**
	 * @var DeserializerFactory|null
	 */
	private $deserializerFactory = null;

	protected function setUp(): void {
		parent::setUp();

		$serializerOptions = SerializerFactory::OPTION_SERIALIZE_MAIN_SNAKS_WITHOUT_HASH +
			SerializerFactory::OPTION_SERIALIZE_REFERENCE_SNAKS_WITHOUT_HASH;
		$this->serializerFactory = new SerializerFactory( new DataValueSerializer(), $serializerOptions );
		$this->deserializerFactory = WikibaseRepo::getBaseDataModelDeserializerFactory();
	}

	/**
	 * @param EntityId[] $ids
	 *
	 * @return EntityRevision[]
	 */
	public function makeEntityRevisions( array $ids ) {
		$entityRevisions = [];

		foreach ( $ids as $id ) {
			$entity = $this->makeEntity( $id );
			$entityRevision = new EntityRevision( $entity, 12, '19700112134640' );

			$key = $id->getSerialization();
			$entityRevisions[$key] = $entityRevision;
		}

		return $entityRevisions;
	}

	/**
	 * @param EntityId $id
	 *
	 * @throws InvalidArgumentException
	 * @return Item|Property
	 */
	protected function makeEntity( EntityId $id ) {
		if ( $id instanceof ItemId ) {
			$entity = new Item( $id );
			$entity->getSiteLinkList()->addNewSiteLink( 'test', 'Foo' . $id->getSerialization() );
		} elseif ( $id instanceof PropertyId ) {
			$entity = new Property( $id, null, 'wibblywobbly' );
		} else {
			throw new InvalidArgumentException( 'Unsupported entity type ' . $id->getEntityType() );
		}

		$entity->setLabel( 'en', 'label:' . $id->getSerialization() );

		return $entity;
	}

	/**
	 * @param EntityId[] $ids
	 * @param EntityId[] $missingIds
	 * @param EntityId[] $redirectedIds
	 *
	 * @return JsonDumpGenerator
	 */
	protected function newDumpGenerator( array $ids = [], array $missingIds = [], array $redirectedIds = [] ) {
		$out = fopen( 'php://output', 'w' );

		$serializer = $this->serializerFactory->newEntitySerializer();

		$entityRevisions = $this->makeEntityRevisions( $ids );

		$entityRevisionLookup = $this->createMock( EntityRevisionLookup::class );
		$entityRevisionLookup->method( 'getEntityRevision' )
			->willReturnCallback( function( EntityId $id ) use ( $entityRevisions, $missingIds, $redirectedIds ) {
				if ( in_array( $id, $missingIds ) ) {
					return null;
				}
				if ( in_array( $id, $redirectedIds ) ) {
					throw new RevisionedUnresolvedRedirectException( $id, new ItemId( 'Q123' ) );
				}

				$key = $id->getSerialization();
				return $entityRevisions[$key];
			} );

		return new JsonDumpGenerator(
			$out,
			$entityRevisionLookup,
			$serializer,
			new NullEntityPrefetcher(),
			$this->getMockPropertyDataTypeLookup(),
			$this->newMockPropertyIdParser(),
			WikibaseRepo::getEntityTitleStoreLookup()
		);
	}

	/**
	 * Callback for providing dummy entity lists for the EntityIdPager mock.
	 *
	 * @param EntityId[] $ids
	 * @param string $entityType
	 * @param int $limit
	 * @param int $offset
	 *
	 * @return EntityId[]
	 */
	public function listEntities( array $ids, $entityType, $limit, &$offset = 0 ) {
		$result = [];
		$size = count( $ids );

		while ( $offset < $size && count( $result ) < $limit ) {
			$id = $ids[ $offset ];
			$offset++;

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
	public function makeIdPager( array $ids, $entityType = null ) {
		$pager = $this->createMock( EntityIdPager::class );

		$offset = 0;

		$pager->method( 'fetchIds' )
			->willReturnCallback( function( $limit ) use ( $ids, $entityType, &$offset ) {
				return $this->listEntities( $ids, $entityType, $limit, $offset );
			} );

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
		$ex = new MWContentSerializationException( 'cannot deserialize!' );
		$jsonDumper = $this->getJsonDumperWithExceptionHandler( $ids, $ex );
		$pager = $this->makeIdPager( $ids );

		ob_start();
		$jsonDumper->generateDump( $pager );
		$json = ob_get_clean();

		$data = json_decode( $json, true );
		$this->assertEquals( [], $data );
	}

	/**
	 * @dataProvider idProvider
	 */
	public function testGenerateDump_HandlesEntityRevisionLookupException( array $ids ) {
		$ex = new EntityLookupException( new ItemId( 'Q2' ), 'Whatever' );
		$jsonDumper = $this->getJsonDumperWithExceptionHandler( $ids, $ex );
		$pager = $this->makeIdPager( $ids );

		ob_start();
		$jsonDumper->generateDump( $pager );
		$json = ob_get_clean();

		$data = json_decode( $json, true );
		$this->assertEquals( [], $data );
	}

	private function getJsonDumperWithExceptionHandler( array $ids, Exception $ex ) {
		$entityRevisionLookup = $this->getEntityRevisionLookupThrows( $ex );
		$out = fopen( 'php://output', 'w' );
		$serializer = $this->serializerFactory->newItemSerializer();

		$jsonDumper = new JsonDumpGenerator(
			$out,
			$entityRevisionLookup,
			$serializer,
			new NullEntityPrefetcher(),
			$this->getMockPropertyDataTypeLookup(),
			$this->newMockPropertyIdParser(),
			WikibaseRepo::getEntityTitleStoreLookup()
		);

		$exceptionHandler = $this->createMock( ExceptionHandler::class );
		$exceptionHandler->expects( $this->exactly( count( $ids ) ) )
			->method( 'handleException' );

		$jsonDumper->setExceptionHandler( $exceptionHandler );

		return $jsonDumper;
	}

	/**
	 * Returns a mock PropertyDataTypeLookup that will return the
	 * type id "string" for any property.
	 *
	 * @return PropertyDataTypeLookup
	 */
	public function getMockPropertyDataTypeLookup() {
		$mock = $this->createMock( PropertyDataTypeLookup::class );
		$mock->method( 'getDataTypeIdForProperty' )
			->willReturn( 'string' );

		return $mock;
	}

	/**
	 * @param Exception $ex
	 *
	 * @return EntityRevisionLookup
	 */
	private function getEntityRevisionLookupThrows( Exception $ex ) {
		$entityRevisionLookup = $this->createMock( EntityRevisionLookup::class );
		$entityRevisionLookup->method( 'getEntityRevision' )
			->willReturnCallback( function( EntityId $id ) use ( $ex ) {
				throw $ex;
			} );

		return $entityRevisionLookup;
	}

	public function idProvider() {
		$p10 = new NumericPropertyId( 'P10' );
		$q30 = new ItemId( 'Q30' );

		return [
			'empty' => [ [] ],
			'some entities' => [ [ $p10, $q30 ] ],
		];
	}

	/**
	 * @dataProvider typeFilterProvider
	 */
	public function testTypeFilterDump( array $ids, ?array $types, array $expectedIds ) {
		$dumper = $this->newDumpGenerator( $ids );
		$pager = $this->makeIdPager( $ids );

		$dumper->setEntityTypesFilter( $types );

		ob_start();
		$dumper->generateDump( $pager );
		$json = ob_get_clean();

		// check that the resulting json contains all the ids we asked for.
		$data = json_decode( $json, true );

		$this->assertIsArray( $data, 'decode failed: ' . $json );

		$actualIds = array_column( $data, 'id' );

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

		$deserializer = $this->deserializerFactory->newEntityDeserializer();
		$actualEntity = $deserializer->deserialize( $data );

		$this->assertTrue(
			$expectedEntity->equals( $actualEntity ),
			'Round trip failed for ' . $id->getSerialization()
		);
	}

	public function typeFilterProvider() {
		$p10 = new NumericPropertyId( 'P10' );
		$q30 = new ItemId( 'Q30' );

		return [
			'empty' => [ [], null, [] ],
			'some entities' => [ [ $p10, $q30 ], null, [ $p10, $q30 ] ],
			'just properties' => [ [ $p10, $q30 ], [ Property::ENTITY_TYPE ], [ $p10 ] ],
			'no matches' => [ [ $p10 ], [ Item::ENTITY_TYPE ], [] ],
			'two types' => [ [ $p10, $q30 ], [ Item::ENTITY_TYPE, Property::ENTITY_TYPE ], [ $p10, $q30 ] ],
		];
	}

	/**
	 * @dataProvider shardingProvider
	 */
	public function testSharding( array $ids, $shardingFactor ) {
		$actualIds = [];
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
			$this->assertIsArray( $data, 'decode failed: ' . $json );

			$shardIds = array_column( $data, 'id' );

			// check shard
			$this->assertEquals(
				[],
				array_intersect( $actualIds, $shardIds ),
				'shard ' . $shard . ' overlaps previous shards'
			);

			// collect ids from all shards
			$actualIds = array_merge( $actualIds, $shardIds );
		}

		$expectedIds = array_map( function( EntityId $id ) {
			return $id->getSerialization();
		}, $ids );

		sort( $actualIds );
		sort( $expectedIds );
		$this->assertEquals( $expectedIds, $actualIds, 'bad sharding' );
	}

	public function shardingProvider() {
		$ids = [];

		for ( $i = 10; $i < 20; $i++ ) {
			$ids[] = new NumericPropertyId( "P$i" );
			$ids[] = new ItemId( "Q$i" );
		}

		for ( $i = 50; $i < 101; $i += 10 ) {
			$ids[] = new NumericPropertyId( "P$i" );
			$ids[] = new ItemId( "Q$i" );
		}

		return [
			'empty sharding' => [ [], 2 ],
			'no sharding' => [ $ids, 1 ],
			'two shards' => [ $ids, 2 ],
			'three shards' => [ $ids, 3 ],
			'five shards' => [ $ids, 5 ],
			'ten shards' => [ $ids, 10 ],
		];
	}

	/**
	 * @dataProvider badShardingProvider
	 */
	public function testInvalidSharding( $shardingFactor, $shard ) {
		$dumper = $this->newDumpGenerator( [] );

		$this->expectException( InvalidArgumentException::class );

		$dumper->setShardingFilter( $shardingFactor, $shard );
	}

	public function badShardingProvider() {
		return [
			[ 0, 0 ],
			[ 1, 1 ],
			[ 2, 3 ],
			[ 2, -1 ],
			[ -2, 2 ],
			[ -3, -2 ],
			[ -2, -3 ],
			[ [], 1 ],
			[ 2, [] ],
			[ null, 1 ],
			[ 2, null ],
			[ '2', 1 ],
			[ 2, '1' ],
		];
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

	public function dataProvider() {
		return [
			'string' => [ 'bla' ],
			'list' => [ [ 'a', 'b', 'c' ] ],
			'map' => [ [ 'a' => 1, 'b' => 2, 'c' => 3 ] ],
		];
	}

	public function testExceptionHandler() {
		$ids = [];
		$missingIds = [];

		for ( $i = 1; $i <= 100; $i++ ) {
			$id = new ItemId( "Q$i" );
			$ids[] = $id;

			if ( ( $i % 10 ) === 0 ) {
				$missingIds[] = $id;
			}
		}

		$dumper = $this->newDumpGenerator( $ids, $missingIds );
		$pager = $this->makeIdPager( $ids );

		$exceptionHandler = $this->createMock( ExceptionHandler::class );
		$exceptionHandler->expects( $this->exactly( count( $missingIds ) ) )
			->method( 'handleException' );

		$dumper->setExceptionHandler( $exceptionHandler );

		ob_start();
		$dumper->generateDump( $pager );
		$json = ob_get_clean();

		// make sure we get valid json even if there were exceptions.
		$data = json_decode( $json, true );
		$this->assertIsArray( $data, 'invalid json generated' );
	}

	public function testProgressReporter() {
		$ids = [];

		for ( $i = 1; $i <= 100; $i++ ) {
			$id = new ItemId( "Q$i" );
			$ids[] = $id;
		}

		$dumper = $this->newDumpGenerator( $ids );
		$pager = $this->makeIdPager( $ids );

		$progressReporter = $this->createMock( MessageReporter::class );
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

	public function useSnippetsProvider() {
		$ids = [];

		for ( $i = 1; $i < 5; $i++ ) {
			$ids[] = new ItemId( "Q$i" );
		}

		return [
			// Only one shard
			[ $ids, 1, 0 ],

			// Three shards
			[ $ids, 3, 0 ],
			[ $ids, 3, 1 ],
			[ $ids, 3, 2 ],
		];
	}

	public function testRedirectsNotIncluded() {
		$ids = [];

		for ( $i = 1; $i <= 10; $i++ ) {
			$id = new ItemId( "Q$i" );
			$ids[] = $id;
		}

		$dumper = $this->newDumpGenerator( $ids, [], [ new ItemId( 'Q9' ) ] );
		$pager = $this->makeIdPager( $ids );

		$dumper->setBatchSize( 10 );

		ob_start();
		$dumper->generateDump( $pager );
		$json = trim( ob_get_clean() );

		$this->assertCount( 9, json_decode( $json ), 'Redirected Item Q9 not in dump' );
	}

	public function testCallbackCalled(): void {
		$ids = [];
		for ( $i = 1; $i <= 100; $i++ ) {
			$ids[] = new ItemId( "Q$i" );
		}

		$dumper = $this->newDumpGenerator( $ids );
		$pager = $this->makeIdPager( $ids );

		$callbackChecker = $this->getMockBuilder( stdClass::class )
			->addMethods( [ 'callback' ] )
			->getMock();
		$callbackChecker->expects( $this->exactly( count( $ids ) / 10 + 1 ) )
			->method( 'callback' );

		$dumper->setBatchSize( 10 );
		$dumper->setBatchCallback( [ $callbackChecker, 'callback' ] );

		ob_start();
		$dumper->generateDump( $pager );
		ob_end_clean();
	}

	private function newMockPropertyIdParser(): EntityIdParser {
		$propertyIdParser = $this->createStub( EntityIdParser::class );
		$propertyIdParser->method( 'parse' )
			->willReturnCallback( static function ( string $id ) {
				return new NumericPropertyId( $id );
			} );

		return $propertyIdParser;
	}

}
