<?php

namespace Wikibase\Repo\Tests\IO;

use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\Lib\Reporting\ExceptionHandler;
use Wikibase\Repo\IO\EntityIdReader;
use Wikibase\Repo\IO\LineReader;

/**
 * @covers \Wikibase\Repo\IO\EntityIdReader
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class EntityIdReaderTest extends \PHPUnit\Framework\TestCase {

	protected function getTestFile() {
		return __DIR__ . '/EntityIdReaderTest.txt';
	}

	protected function openIdReader( $file, $type = null ) {
		$path = __DIR__ . '/' . $file;
		$handle = fopen( $path, 'r' );
		return new EntityIdReader( new LineReader( $handle ), new BasicEntityIdParser(), $type );
	}

	protected function getIdStrings( array $entityIds ) {
		return array_map( function( EntityId $id ) {
			return $id->getSerialization();
		}, $entityIds );
	}

	protected function assertEqualIds( array $expected, array $actual ) {
		$expectedIds = array_values( $this->getIdStrings( $expected ) );
		$actualIds = array_values( $this->getIdStrings( $actual ) );

		sort( $expectedIds );
		sort( $actualIds );
		$this->assertEquals( $expectedIds, $actualIds );
	}

	/**
	 * @dataProvider listEntitiesProvider
	 */
	public function testListEntities( $file, $type, $limit, array $expected ) {
		$reader = $this->openIdReader( $file, $type );

		$actual = $reader->fetchIds( $limit );

		$this->assertEqualIds( $expected, $actual );
	}

	public function listEntitiesProvider() {
		$q1 = new ItemId( 'Q1' );
		$p2 = new NumericPropertyId( 'P2' );
		$q3 = new ItemId( 'Q3' );
		$p4 = new NumericPropertyId( 'P4' );

		return [
			'all' => [
				'EntityIdReaderTest.txt', null, 100, [ $q1, $p2, $q3, $p4 ],
			],
			'just properties' => [
				'EntityIdReaderTest.txt', Property::ENTITY_TYPE, 100, [ $p2, $p4 ],
			],
			'limit' => [
				'EntityIdReaderTest.txt', null, 2, [ $q1, $p2 ],
			],
			'limit and filter' => [
				'EntityIdReaderTest.txt', Item::ENTITY_TYPE, 1, [ $q1 ],
			],
		];
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

	public function listEntitiesProvider_paging() {
		$q1 = new ItemId( 'Q1' );
		$p2 = new NumericPropertyId( 'P2' );
		$q3 = new ItemId( 'Q3' );
		$p4 = new NumericPropertyId( 'P4' );

		return [
			'limit' => [
				'EntityIdReaderTest.txt',
				null,
				2,
				[
					[ $q1, $p2 ],
					[ $q3, $p4 ],
					[],
				],
			],
			'limit and filter' => [
				'EntityIdReaderTest.txt',
				Item::ENTITY_TYPE,
				1,
				[
					[ $q1 ],
					[ $q3 ],
					[],
				],
			],
		];
	}

	public function testErrorHandler() {
		$expected = [
			new ItemId( 'Q23' ),
			new NumericPropertyId( 'P42' ),
		];

		$exceptionHandler = $this->createMock( ExceptionHandler::class );
		$exceptionHandler->expects( $this->exactly( 2 ) ) //two bad lines in EntityIdReaderTest.bad.txt
			->method( 'handleException' );

		$reader = $this->openIdReader( 'EntityIdReaderTest.bad.txt' );
		$reader->setExceptionHandler( $exceptionHandler );

		$actual = $reader->fetchIds( 100 );
		$reader->dispose();

		$this->assertEqualIds( $expected, $actual );
	}

}
