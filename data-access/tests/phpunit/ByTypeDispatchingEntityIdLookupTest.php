<?php

namespace Wikibase\DataAccess\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Title;
use Wikibase\DataAccess\ByTypeDispatchingEntityIdLookup;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\EntityIdLookup;

/**
 * @covers \Wikibase\DataAccess\ByTypeDispatchingEntityIdLookup
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ByTypeDispatchingEntityIdLookupTest extends TestCase {

	public function testGivenNotStringValuesArrayContentModel_constructorThrowsException() {
		$this->expectException( InvalidArgumentException::class );
		new ByTypeDispatchingEntityIdLookup(
			[ 'item' => 123 ],
			[],
			$this->createMock( EntityIdLookup::class )
		);
	}

	public function testGivenNotStringIndexedArrayContentModel_constructorThrowsException() {
		$this->expectException( InvalidArgumentException::class );
		new ByTypeDispatchingEntityIdLookup(
			[ 'wikibase-item' ],
			[],
			$this->createMock( EntityIdLookup::class )
		);
	}

	public function testGivenNotCallableValuesArrayLookups_constructorThrowsException() {
		$this->expectException( InvalidArgumentException::class );
		new ByTypeDispatchingEntityIdLookup(
			[ 'item' => 'wikibase-item' ],
			[ 'item' => 'BADVALUE' ],
			$this->createMock( EntityIdLookup::class )
		);
	}

	public function testGivenNotStringIndexedArrayLookups_constructorThrowsException() {
		$this->expectException( InvalidArgumentException::class );
		new ByTypeDispatchingEntityIdLookup(
			[ 'item' => 'wikibase-item' ],
			[ function () {
				return $this->createMock( EntityIdLookup::class );
			} ],
			$this->createMock( EntityIdLookup::class )
		);
	}

	private function mockEntityIdLookup( array $map ) {
		$lookup = $this->createMock( EntityIdLookup::class );
		$lookup
			->method( 'getEntityIds' )
			->willReturnCallback( function ( $titles ) use ( $map ) {
				$results = [];
				foreach ( $titles as $title ) {
					$dbKey = $title->getPrefixedDBkey();
					if ( isset( $map[$dbKey] ) ) {
						$results[$title->getArticleId()] = $map[$dbKey];
					}
				}
				return $results;
			} );
		$lookup
			->method( 'getEntityIdForTitle' )
			->willReturnCallback( function ( $title ) use ( $map ) {
				return $map[$title->getPrefixedDBkey()] ?? null;
			} );
		return $lookup;
	}

	public function testGivenTitleOfKnownContentModel_getEntityIdRetrievesFromDefaultLookup() {
		$entityId = new ItemId( 'Q1' );
		$title = Title::newFromDBkey( 'Q1' );
		$title->resetArticleID( 1 );
		$title->setContentModel( 'wikibase-item' );

		$lookup = new ByTypeDispatchingEntityIdLookup(
			[],
			[],
			$this->mockEntityIdLookup( [
				$title->getPrefixedDBkey() => $entityId,
			] )
		);

		$result = $lookup->getEntityIdForTitle( $title );
		$this->assertEquals( $entityId->getSerialization(), $result->getSerialization() );

		$results = $lookup->getEntityIds( [ $title ] );
		$this->assertArrayHasKey( $title->getArticleID(), $results );
		$this->assertEquals( $entityId->getSerialization(), $results[$title->getArticleID()]->getSerialization() );
	}

	public function testGivenTitleOfKnownContentModel_getEntityIdRetrievesFromSpecificLookup() {
		$entityId = new ItemId( 'Q1' );
		$title = Title::newFromDBkey( 'Q1' );
		$title->resetArticleID( 1 );
		$title->setContentModel( 'wikibase-item' );

		$lookup = new ByTypeDispatchingEntityIdLookup(
			[ 'item' => 'wikibase-item' ],
			[ 'item' => function () use ( $title, $entityId ) {
				return $this->mockEntityIdLookup( [
					$title->getPrefixedDBkey() => $entityId,
				] );
			} ],
			$this->createMock( EntityIdLookup::class )
		);

		$result = $lookup->getEntityIdForTitle( $title );
		$this->assertEquals( $entityId->getSerialization(), $result->getSerialization() );

		$results = $lookup->getEntityIds( [ $title ] );
		$this->assertArrayHasKey( $title->getArticleID(), $results );
		$this->assertEquals( $entityId->getSerialization(), $results[$title->getArticleID()]->getSerialization() );
	}

	public function testGivenTitleOfKnownContentModel_getEntityIdPrefersSpecificLookup() {
		$entityIdSpecific = new ItemId( 'Q1' );
		$entityIdDefault = new ItemId( 'Q2' );
		$title = Title::newFromDBkey( 'Q1' );
		$title->resetArticleID( 123 );
		$title->setContentModel( 'wikibase-item' );

		$lookup = new ByTypeDispatchingEntityIdLookup(
			[ 'item' => 'wikibase-item' ],
			[ 'item' => function () use ( $title, $entityIdSpecific ) {
				return $this->mockEntityIdLookup( [
					$title->getPrefixedDBkey() => $entityIdSpecific,
				] );
			} ],
			$this->mockEntityIdLookup( [
				$title->getPrefixedDBkey() => $entityIdDefault,
			] )
		);

		$result = $lookup->getEntityIdForTitle( $title );
		$this->assertEquals( $entityIdSpecific->getSerialization(), $result->getSerialization() );

		$results = $lookup->getEntityIds( [ $title ] );
		$this->assertArrayHasKey( $title->getArticleID(), $results );
		$this->assertEquals( $entityIdSpecific->getSerialization(), $results[$title->getArticleID()]->getSerialization() );
	}

	public function testGivenTitleOfUnknownContentModel_getEntityIdReturnsNull() {
		$entityId = new ItemId( 'Q1' );
		$title = Title::newFromDBkey( 'Q1' );
		$title->resetArticleID( 1 );
		$title->setContentModel( 'wikitext' );

		$lookup = new ByTypeDispatchingEntityIdLookup(
			[ 'item' => 'wikibase-item' ],
			[ 'item' => function () use ( $title, $entityId ) {
				return $this->mockEntityIdLookup( [
					$title->getPrefixedDBkey() => $entityId,
				] );
			} ],
			$this->mockEntityIdLookup( [] )
		);

		// lookup is for `wikibase-item`, but Title is `wikitext` & default will come up empty...
		$result = $lookup->getEntityIdForTitle( $title );
		$this->assertNull( $result );

		$results = $lookup->getEntityIds( [ $title ] );
		$this->assertSame( [], $results );
	}

	public function testGivenNonExistingTitle_getEntityIdReturnsNull() {
		$entityId = new ItemId( 'Q1' );
		$title = Title::newFromDBkey( 'Q1' );
		$title->resetArticleID( 1 );
		$title->setContentModel( 'wikitext' );

		$lookup = new ByTypeDispatchingEntityIdLookup(
			[],
			[],
			$this->mockEntityIdLookup( [ $title->getPrefixedDBkey() => $entityId ] )
		);

		// lookup is for `wikibase-item`, but Title is `wikitext` & default will come up empty...
		$result = $lookup->getEntityIdForTitle( Title::newFromDBkey( 'P99' ) );
		$this->assertNull( $result );

		$results = $lookup->getEntityIds( [ Title::newFromDBkey( 'P99' ) ] );
		$this->assertSame( [], $results );
	}

}
