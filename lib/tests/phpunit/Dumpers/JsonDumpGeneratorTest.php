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
use Wikibase\Property;

/**
 * JsonDumpGeneratorTest
 *
 * @covers JsonDumpGenerator
 *
 *
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
	protected function newDumpGenerator( array $ids = array() ) {
		$out = fopen( 'php://output', 'w' ); // eek

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
			->will( $this->returnCallback( function ( EntityId $id ) use ( $entities ) {
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
		$dumper = $this->newDumpGenerator( $ids );
		$idList = new ArrayObject( $ids );

		ob_start();
		$dumper->generateDump( $idList );
		$json = ob_get_clean();

		// check that the resulting json contains all the ids we asked for.
		$data = json_decode( $json, true );

		$this->assertTrue( is_array( $data ), 'decode failed: ' . $json );

		$actualIds = array_map( function( $entityData ) {
			return $entityData['id'];
		}, $data );

		$expectedIds = array_map( function( EntityId $id ) {
			return $id->getPrefixedId();
		}, $ids );

		$this->assertEquals( $expectedIds, $actualIds );
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

}
