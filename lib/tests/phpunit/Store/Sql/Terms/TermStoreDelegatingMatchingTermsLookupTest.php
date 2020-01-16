<?php

namespace Wikibase\Lib\Tests\Store\Sql\Terms;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Wikibase\Lib\Store\MatchingTermsLookup;
use Wikibase\Lib\Store\Sql\Terms\TermStoreDelegatingMatchingTermsLookup;
use Wikibase\TermIndexEntry;

/**
 * @covers \Wikibase\Lib\Store\Sql\Terms\TermStoreDelegatingMatchingTermsLookup
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class TermStoreDelegatingMatchingTermsLookupTest extends TestCase {

	/**
	 * @var MockObject|MatchingTermsLookup
	 */
	private $oldTermStoreLookup;

	/**
	 * @var MockObject|MatchingTermsLookup
	 */
	private $newTermStoreLookup;

	/**
	 * @var int
	 */
	private $itemMigrationStage;

	/**
	 * @var int
	 */
	private $propertyMigrationStage;

	public function setUp() : void {
		parent::setUp();

		$this->oldTermStoreLookup = $this->createMock( MatchingTermsLookup::class );
		$this->newTermStoreLookup = $this->createMock( MatchingTermsLookup::class );

		$this->itemMigrationStage = MIGRATION_OLD;
		$this->propertyMigrationStage = MIGRATION_OLD;
	}

	public function testGivenPropertyReadOld_getMatchingTermsUsesNewStore() {
		$expected = [ $this->createMock( TermIndexEntry::class ) ];
		$this->oldTermStoreLookup->expects( $this->once() )
			->method( 'getMatchingTerms' )
			->willReturn( $expected );
		$this->newTermStoreLookup->expects( $this->never() )
			->method( $this->anything() );

		$this->propertyMigrationStage = MIGRATION_OLD; // could be anything < MIGRATION_NEW

		$this->assertSame(
			$expected,
			$this->newTermStoreDelegatingMatchingTermsLookup()
				->getMatchingTerms( [], null, 'property' )
		);
	}

	public function testGivenPropertyReadNew_getMatchingTermsUsesNewStore() {
		$expected = [ $this->createMock( TermIndexEntry::class ) ];
		$this->oldTermStoreLookup->expects( $this->never() )
			->method( $this->anything() );
		$this->newTermStoreLookup->expects( $this->once() )
			->method( 'getMatchingTerms' )
			->willReturn( $expected );

		$this->propertyMigrationStage = MIGRATION_NEW;

		$this->assertSame(
			$expected,
			$this->newTermStoreDelegatingMatchingTermsLookup()
				->getMatchingTerms( [], null, 'property' )
		);
	}

	public function testGivenItemReadOld_getMatchingTermsUsesOldStore() {
		$expected = [ $this->createMock( TermIndexEntry::class ) ];
		$this->oldTermStoreLookup->expects( $this->once() )
			->method( 'getMatchingTerms' )
			->willReturn( $expected );
		$this->newTermStoreLookup->expects( $this->never() )
			->method( $this->anything() );

		$this->itemMigrationStage = MIGRATION_OLD;

		$this->assertSame(
			$expected,
			$this->newTermStoreDelegatingMatchingTermsLookup()
				->getMatchingTerms( [], null, 'property' )
		);
	}

	public function testGivenItemReadNew_getMatchingTermsUsesNewStore() {
		$expected = [ $this->createMock( TermIndexEntry::class ) ];
		$this->oldTermStoreLookup->expects( $this->never() )
			->method( $this->anything() );
		$this->newTermStoreLookup->expects( $this->once() )
			->method( 'getMatchingTerms' )
			->willReturn( $expected );

		$this->itemMigrationStage = MIGRATION_NEW;

		$this->assertSame(
			$expected,
			$this->newTermStoreDelegatingMatchingTermsLookup()
				->getMatchingTerms( [], null, 'item' )
		);
	}

	public function testGivenItemAndPropertyReadOld_getMatchingTermsUsesOldStore() {
		$expected = [ $this->createMock( TermIndexEntry::class ) ];
		$this->oldTermStoreLookup->expects( $this->once() )
			->method( 'getMatchingTerms' )
			->willReturn( $expected );
		$this->newTermStoreLookup->expects( $this->never() )
			->method( $this->anything() );

		$this->itemMigrationStage = MIGRATION_OLD;
		$this->propertyMigrationStage = MIGRATION_OLD;

		$this->assertSame(
			$expected,
			$this->newTermStoreDelegatingMatchingTermsLookup()
				->getMatchingTerms( [], null, null )
		);
	}

	public function testGivenItemAndPropertyReadNew_getMatchingTermsUsesNewStore() {
		$expected = [ $this->createMock( TermIndexEntry::class ) ];
		$this->oldTermStoreLookup->expects( $this->never() )
			->method( $this->anything() );
		$this->newTermStoreLookup->expects( $this->once() )
			->method( 'getMatchingTerms' )
			->willReturn( $expected );

		$this->itemMigrationStage = MIGRATION_NEW;
		$this->propertyMigrationStage = MIGRATION_NEW;

		$this->assertSame(
			$expected,
			$this->newTermStoreDelegatingMatchingTermsLookup()
				->getMatchingTerms( [], null, null )
		);
	}

	public function testGivenPropertyReadNewItemReadOld_getMatchingTermsCombinesResults() {
		$expectedPropertyTerms = [ $this->createMock( TermIndexEntry::class ) ];
		$expectedItemTerms = [ $this->createMock( TermIndexEntry::class ) ];
		$this->oldTermStoreLookup->expects( $this->once() )
			->method( 'getMatchingTerms' )
			->willReturn( $expectedItemTerms );
		$this->newTermStoreLookup->expects( $this->once() )
			->method( 'getMatchingTerms' )
			->willReturn( $expectedPropertyTerms );

		$this->itemMigrationStage = MIGRATION_NEW;
		$this->propertyMigrationStage = MIGRATION_OLD;

		$this->assertEquals(
			array_merge( $expectedItemTerms, $expectedPropertyTerms ),
			$this->newTermStoreDelegatingMatchingTermsLookup()
				->getMatchingTerms( [], null, null )
		);
	}

	public function testGivenPropertyReadOldItemReadNew_getMatchingTermsCombinesResults() {
		$expectedItemTerms = [ $this->createMock( TermIndexEntry::class ) ];
		$expectedPropertyTerms = [ $this->createMock( TermIndexEntry::class ) ];
		$this->oldTermStoreLookup->expects( $this->once() )
			->method( 'getMatchingTerms' )
			->willReturn( $expectedPropertyTerms );
		$this->newTermStoreLookup->expects( $this->once() )
			->method( 'getMatchingTerms' )
			->willReturn( $expectedItemTerms );

		$this->itemMigrationStage = MIGRATION_NEW;
		$this->propertyMigrationStage = MIGRATION_OLD;

		$this->assertEquals(
			array_merge( $expectedItemTerms, $expectedPropertyTerms ),
			$this->newTermStoreDelegatingMatchingTermsLookup()
				->getMatchingTerms( [], null, null )
		);
	}

	public function testGivenLimit_getMatchingTermsCutsOffAtLimitForCombinedResult() {
		$expectedItemTerms = [
			$this->createMock( TermIndexEntry::class ),
			$this->createMock( TermIndexEntry::class ),
		];
		$expectedPropertyTerms = [
			$this->createMock( TermIndexEntry::class ),
			$this->createMock( TermIndexEntry::class ),
		];

		$this->oldTermStoreLookup->expects( $this->once() )
			->method( 'getMatchingTerms' )
			->willReturn( $expectedPropertyTerms );
		$this->newTermStoreLookup->expects( $this->once() )
			->method( 'getMatchingTerms' )
			->willReturn( $expectedItemTerms );

		$this->itemMigrationStage = MIGRATION_NEW;
		$this->propertyMigrationStage = MIGRATION_OLD;

		$this->assertEquals(
			[ $expectedItemTerms[0], $expectedItemTerms[1], $expectedPropertyTerms[0] ],
			$this->newTermStoreDelegatingMatchingTermsLookup()
				->getMatchingTerms( [], null, null, [ 'LIMIT' => 3 ] )
		);
	}

	public function testGivenPropertyReadOld_getTopMatchingTermsUsesNewStore() {
		$expected = [ $this->createMock( TermIndexEntry::class ) ];
		$this->oldTermStoreLookup->expects( $this->once() )
			->method( 'getTopMatchingTerms' )
			->willReturn( $expected );
		$this->newTermStoreLookup->expects( $this->never() )
			->method( $this->anything() );

		$this->propertyMigrationStage = MIGRATION_OLD; // could be anything < MIGRATION_NEW

		$this->assertSame(
			$expected,
			$this->newTermStoreDelegatingMatchingTermsLookup()
				->getTopMatchingTerms( [], null, 'property' )
		);
	}

	public function testGivenPropertyReadNew_getTopMatchingTermsUsesNewStore() {
		$expected = [ $this->createMock( TermIndexEntry::class ) ];
		$this->oldTermStoreLookup->expects( $this->never() )
			->method( $this->anything() );
		$this->newTermStoreLookup->expects( $this->once() )
			->method( 'getTopMatchingTerms' )
			->willReturn( $expected );

		$this->propertyMigrationStage = MIGRATION_NEW;

		$this->assertSame(
			$expected,
			$this->newTermStoreDelegatingMatchingTermsLookup()
				->getTopMatchingTerms( [], null, 'property' )
		);
	}

	public function testGivenItemReadOld_getTopMatchingTermsUsesOldStore() {
		$expected = [ $this->createMock( TermIndexEntry::class ) ];
		$this->oldTermStoreLookup->expects( $this->once() )
			->method( 'getTopMatchingTerms' )
			->willReturn( $expected );
		$this->newTermStoreLookup->expects( $this->never() )
			->method( $this->anything() );

		$this->itemMigrationStage = MIGRATION_OLD;

		$this->assertSame(
			$expected,
			$this->newTermStoreDelegatingMatchingTermsLookup()
				->getTopMatchingTerms( [], null, 'property' )
		);
	}

	public function testGivenItemReadNew_getTopMatchingTermsUsesNewStore() {
		$expected = [ $this->createMock( TermIndexEntry::class ) ];
		$this->oldTermStoreLookup->expects( $this->never() )
			->method( $this->anything() );
		$this->newTermStoreLookup->expects( $this->once() )
			->method( 'getTopMatchingTerms' )
			->willReturn( $expected );

		$this->itemMigrationStage = MIGRATION_NEW;

		$this->assertSame(
			$expected,
			$this->newTermStoreDelegatingMatchingTermsLookup()
				->getTopMatchingTerms( [], null, 'item' )
		);
	}

	public function testGivenItemAndPropertyReadOld_getTopMatchingTermsUsesOldStore() {
		$expected = [ $this->createMock( TermIndexEntry::class ) ];
		$this->oldTermStoreLookup->expects( $this->once() )
			->method( 'getTopMatchingTerms' )
			->willReturn( $expected );
		$this->newTermStoreLookup->expects( $this->never() )
			->method( $this->anything() );

		$this->itemMigrationStage = MIGRATION_OLD;
		$this->propertyMigrationStage = MIGRATION_OLD;

		$this->assertSame(
			$expected,
			$this->newTermStoreDelegatingMatchingTermsLookup()
				->getTopMatchingTerms( [], null, null )
		);
	}

	public function testGivenItemAndPropertyReadNew_getTopMatchingTermsUsesNewStore() {
		$expected = [ $this->createMock( TermIndexEntry::class ) ];
		$this->oldTermStoreLookup->expects( $this->never() )
			->method( $this->anything() );
		$this->newTermStoreLookup->expects( $this->once() )
			->method( 'getTopMatchingTerms' )
			->willReturn( $expected );

		$this->itemMigrationStage = MIGRATION_NEW;
		$this->propertyMigrationStage = MIGRATION_NEW;

		$this->assertSame(
			$expected,
			$this->newTermStoreDelegatingMatchingTermsLookup()
				->getTopMatchingTerms( [], null, null )
		);
	}

	public function testGivenPropertyReadNewItemReadOld_getTopMatchingTermsCombinesResults() {
		$expectedPropertyTerms = [ $this->createMock( TermIndexEntry::class ) ];
		$expectedItemTerms = [ $this->createMock( TermIndexEntry::class ) ];
		$this->oldTermStoreLookup->expects( $this->once() )
			->method( 'getTopMatchingTerms' )
			->willReturn( $expectedItemTerms );
		$this->newTermStoreLookup->expects( $this->once() )
			->method( 'getTopMatchingTerms' )
			->willReturn( $expectedPropertyTerms );

		$this->itemMigrationStage = MIGRATION_NEW;
		$this->propertyMigrationStage = MIGRATION_OLD;

		$this->assertEquals(
			array_merge( $expectedItemTerms, $expectedPropertyTerms ),
			$this->newTermStoreDelegatingMatchingTermsLookup()
				->getTopMatchingTerms( [], null, null )
		);
	}

	public function testGivenPropertyReadOldItemReadNew_getTopMatchingTermsCombinesResults() {
		$expectedItemTerms = [ $this->createMock( TermIndexEntry::class ) ];
		$expectedPropertyTerms = [ $this->createMock( TermIndexEntry::class ) ];
		$this->oldTermStoreLookup->expects( $this->once() )
			->method( 'getTopMatchingTerms' )
			->willReturn( $expectedPropertyTerms );
		$this->newTermStoreLookup->expects( $this->once() )
			->method( 'getTopMatchingTerms' )
			->willReturn( $expectedItemTerms );

		$this->itemMigrationStage = MIGRATION_NEW;
		$this->propertyMigrationStage = MIGRATION_OLD;

		$this->assertEquals(
			array_merge( $expectedItemTerms, $expectedPropertyTerms ),
			$this->newTermStoreDelegatingMatchingTermsLookup()
				->getTopMatchingTerms( [], null, null )
		);
	}

	public function testGivenLimit_getTopMatchingTermsCutsOffAtLimitForCombinedResult() {
		$expectedItemTerms = [
			$this->createMock( TermIndexEntry::class ),
			$this->createMock( TermIndexEntry::class ),
		];
		$expectedPropertyTerms = [
			$this->createMock( TermIndexEntry::class ),
			$this->createMock( TermIndexEntry::class ),
		];

		$this->oldTermStoreLookup->expects( $this->once() )
			->method( 'getTopMatchingTerms' )
			->willReturn( $expectedPropertyTerms );
		$this->newTermStoreLookup->expects( $this->once() )
			->method( 'getTopMatchingTerms' )
			->willReturn( $expectedItemTerms );

		$this->itemMigrationStage = MIGRATION_NEW;
		$this->propertyMigrationStage = MIGRATION_OLD;

		$this->assertEquals(
			[ $expectedItemTerms[0], $expectedItemTerms[1], $expectedPropertyTerms[0] ],
			$this->newTermStoreDelegatingMatchingTermsLookup()
				->getTopMatchingTerms( [], null, null, [ 'LIMIT' => 3 ] )
		);
	}

	private function newTermStoreDelegatingMatchingTermsLookup(): TermStoreDelegatingMatchingTermsLookup {
		return new TermStoreDelegatingMatchingTermsLookup(
			$this->oldTermStoreLookup,
			$this->newTermStoreLookup,
			$this->itemMigrationStage,
			$this->propertyMigrationStage
		);
	}
}
