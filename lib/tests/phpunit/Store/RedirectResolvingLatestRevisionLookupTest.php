<?php

namespace Wikibase\Lib\Tests\Store;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\LatestRevisionIdResult;
use Wikibase\Lib\Store\RedirectResolvingLatestRevisionLookup;

/**
 * @covers \Wikibase\Lib\Store\RedirectResolvingLatestRevisionLookup
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class RedirectResolvingLatestRevisionLookupTest extends TestCase {

	private const SOME_REV_TIMESTAMP = '20220101001122';

	public function testLooksUpLatestRevision() {
		$id = new ItemId( 'Q123' );
		$revision = 777;

		$revisionLookup = $this->createMock( EntityRevisionLookup::class );
		$revisionLookup->expects( $this->once() )
			->method( 'getLatestRevisionId' )
			->with( $id )
			->willReturn( LatestRevisionIdResult::concreteRevision( $revision, self::SOME_REV_TIMESTAMP ) );

		$this->assertSame(
			[ $revision, $id ],
			$this->newRedirectResolvingLatestRevisionLookup( $revisionLookup )
				->lookupLatestRevisionResolvingRedirect( $id )
		);
	}

	public function testResolvesRedirect() {
		$originalEntityId = new ItemId( 'Q321' );
		$redirectEntityId = new ItemId( 'Q123' );
		$redirectEntityRevision = 666;

		$revisionLookup = $this->createMock( EntityRevisionLookup::class );
		$revisionLookup->method( 'getLatestRevisionId' )
			->withConsecutive(
				[ $originalEntityId ],
				[ $redirectEntityId ]
			)
			->willReturnOnConsecutiveCalls(
				LatestRevisionIdResult::redirect( 777, $redirectEntityId ),
				LatestRevisionIdResult::concreteRevision( $redirectEntityRevision, self::SOME_REV_TIMESTAMP )
			);

		$this->assertEquals(
			[ $redirectEntityRevision, $redirectEntityId ],
			$this->newRedirectResolvingLatestRevisionLookup( $revisionLookup )
				->lookupLatestRevisionResolvingRedirect( $originalEntityId )
		);
	}

	public function testGivenDoubleRedirect_returnsNull() {
		$originalEntityId = new ItemId( 'Q1' );
		$redirect1 = new ItemId( 'Q2' );
		$redirect2 = new ItemId( 'Q3' );

		$revisionLookup = $this->createMock( EntityRevisionLookup::class );
		$revisionLookup->method( 'getLatestRevisionId' )
			->withConsecutive(
				[ $originalEntityId ],
				[ $redirect1 ]
			)
			->willReturnOnConsecutiveCalls(
				LatestRevisionIdResult::redirect( 777, $redirect1 ),
				LatestRevisionIdResult::redirect( 888, $redirect2 )
			);

		$this->assertNull( $this->newRedirectResolvingLatestRevisionLookup( $revisionLookup )
			->lookupLatestRevisionResolvingRedirect( $originalEntityId ) );
	}

	public function testGivenRedirectToNonExistentEntity_returnsNull() {
		$originalEntityId = new ItemId( 'Q1' );
		$redirect = new ItemId( 'Q2' );

		$revisionLookup = $this->createMock( EntityRevisionLookup::class );
		$revisionLookup->method( 'getLatestRevisionId' )
			->withConsecutive(
				[ $originalEntityId ],
				[ $redirect ]
			)
			->willReturnOnConsecutiveCalls(
				LatestRevisionIdResult::redirect( 777, $redirect ),
				LatestRevisionIdResult::nonexistentEntity()
			);

		$this->assertNull( $this->newRedirectResolvingLatestRevisionLookup( $revisionLookup )
			->lookupLatestRevisionResolvingRedirect( $originalEntityId ) );
	}

	public function testGivenCalledMultipleTimes_onlyLooksUpOnce() {
		$id = new ItemId( 'Q123' );
		$revision = 777;

		$revisionLookup = $this->createMock( EntityRevisionLookup::class );
		$revisionLookup->expects( $this->once() )
			->method( 'getLatestRevisionId' )
			->with( $id )
			->willReturn( LatestRevisionIdResult::concreteRevision( $revision, self::SOME_REV_TIMESTAMP ) );

		$revisionRedirectResolver = $this->newRedirectResolvingLatestRevisionLookup( $revisionLookup );

		$this->assertSame(
			[ $revision, $id ],
			$revisionRedirectResolver
				->lookupLatestRevisionResolvingRedirect( $id )
		);
		$this->assertSame(
			[ $revision, $id ],
			$revisionRedirectResolver
				->lookupLatestRevisionResolvingRedirect( $id ) // cached internally
		);
	}

	private function newRedirectResolvingLatestRevisionLookup(
		EntityRevisionLookup $revisionLookup
	): RedirectResolvingLatestRevisionLookup {
		return new RedirectResolvingLatestRevisionLookup(
			$revisionLookup
		);
	}

}
