<?php

namespace Wikibase\Test\IO;

use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Repo\IO\EntityIdReader;
use Wikibase\Repo\IO\LineReader;

/**
 * @covers Wikibase\Repo\IO\EntityIdReader
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseIO
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class EntityIdReaderTest extends \PHPUnit_Framework_TestCase {

	protected function getTestFile() {
		return __DIR__ . '/EntityIdReaderTest.txt';
	}

	protected function openIdReader( $file, $type = null ) {
		$path = __DIR__ . '/' . $file;
		$handle = fopen( $path, 'r' );
		return new EntityIdReader( new LineReader( $handle ), new BasicEntityIdParser(), $type );
	}


	protected function getIdStrings( array $entityIds ) {
		$ids = array_map( function ( EntityId $entityId ) {
			return $entityId->getSerialization();
		}, $entityIds );

		return $ids;
	}

	protected function assertEqualIds( array $expected,array $actual, $msg = null ) {
		$expectedIds = array_values( $this->getIdStrings( $expected ) );
		$actualIds = array_values( $this->getIdStrings( $actual ) );

		sort( $expectedIds );
		sort( $actualIds );
		$this->assertEquals( $expectedIds, $actualIds, $msg );
	}

	/**
	 * @dataProvider listEntitiesProvider
	 */
	public function testListEntities( $file, $type, $limit, array $expected ) {
		$reader = $this->openIdReader( $file, $type );

		$actual = $reader->fetchIds( $limit );

		$this->assertEqualIds( $expected, $actual );
	}

	public static function listEntitiesProvider() {
		$q1 = new ItemId( 'Q1' );
		$p2 = new PropertyId( 'P2' );
		$q3 = new ItemId( 'Q3' );
		$p4 = new PropertyId( 'P4' );

		return array(
			'all' => array(
				'EntityIdReaderTest.txt', null, 100, array( $q1, $p2, $q3, $p4 )
			),
			'just properties' => array(
				'EntityIdReaderTest.txt', Property::ENTITY_TYPE, 100, array( $p2, $p4 )
			),
			'limit' => array(
				'EntityIdReaderTest.txt', null, 2, array( $q1, $p2 )
			),
			'limit and filter' => array(
				'EntityIdReaderTest.txt', Item::ENTITY_TYPE, 1, array( $q1 )
			),
		);
	}
	/**
	 * @dataProvider listEntitiesProvider_paging
	 */
	public function testListEntities_paging( $file, $type, $limit, array $expectedChunks ) {
		$reader = $this->openIdReader( $file, $type );

		foreach ( $expectedChunks as $expected ) {
			$actual = $reader->fetchIds( $limit );

			$this->assertEqualIds( $expected, $actual );
		}
	}

	public static function listEntitiesProvider_paging() {
		$q1 = new ItemId( 'Q1' );
		$p2 = new PropertyId( 'P2' );
		$q3 = new ItemId( 'Q3' );
		$p4 = new PropertyId( 'P4' );

		return array(
			'limit' => array(
				'EntityIdReaderTest.txt',
				null,
				2,
				array (
					array( $q1, $p2 ),
					array( $q3, $p4 ),
					array(),
				)
			),
			'limit and filter' => array(
				'EntityIdReaderTest.txt',
				Item::ENTITY_TYPE,
				1,
				array(
					array( $q1 ),
					array( $q3 ),
					array(),
				)
			)
		);
	}

	public function testErrorHandler() {
		$expected = array(
			new ItemId( 'Q23' ),
			new PropertyId( 'P42' ),
		);

		$exceptionHandler = $this->getMock( 'Wikibase\Lib\Reporting\ExceptionHandler' );
		$exceptionHandler->expects( $this->exactly( 2 ) ) //two bad lines in EntityIdReaderTest.bad.txt
			->method( 'handleException' );

		$reader = $this->openIdReader( 'EntityIdReaderTest.bad.txt' );
		$reader->setExceptionHandler( $exceptionHandler );

		$actual = $reader->fetchIds( 100 );
		$reader->dispose();

		$this->assertEqualIds( $expected, $actual );
	}

}
