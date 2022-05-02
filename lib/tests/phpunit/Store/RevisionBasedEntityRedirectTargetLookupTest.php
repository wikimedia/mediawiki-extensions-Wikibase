<?php

namespace Wikibase\Lib\Tests\Store;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\EntityRedirectTargetLookup;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\LatestRevisionIdResult;
use Wikibase\Lib\Store\LookupConstants;
use Wikibase\Lib\Store\RevisionBasedEntityRedirectTargetLookup;

/**
 * @covers \Wikibase\Lib\Store\RevisionBasedEntityRedirectTargetLookup
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class RevisionBasedEntityRedirectTargetLookupTest extends TestCase {

	/**
	 * @dataProvider latestRevisionResultProvider
	 */
	public function testGivenLatestRevisionResult_getRedirectForEntityIdReturnsExpectedId(
		LatestRevisionIdResult $latestRevisionResult,
		?EntityId $redirectTarget
	) {
		$entityId = new ItemId( 'Q123' );
		$revisionLookup = $this->createMock( EntityRevisionLookup::class );
		$revisionLookup->expects( $this->once() )
			->method( 'getLatestRevisionId' )
			->with( $entityId, LookupConstants::LATEST_FROM_REPLICA )
			->willReturn( $latestRevisionResult );

		$this->assertSame(
			$redirectTarget,
			$this->newRedirectLookup( $revisionLookup )->getRedirectForEntityId( $entityId )
		);
	}

	public function latestRevisionResultProvider() {
		yield 'concrete revision -> no redirect' => [
			LatestRevisionIdResult::concreteRevision( 666, '20220101001122' ),
			null,
		];

		yield 'entity does not exist -> no redirect' => [
			LatestRevisionIdResult::nonexistentEntity(),
			null,
		];

		$redirectTarget = new ItemId( 'Q321' );
		yield 'redirect' => [
			LatestRevisionIdResult::redirect( 666, $redirectTarget ),
			$redirectTarget,
		];
	}

	public function testGivenForUpdate_getRedirectForEntityIdGetsRevisionFromMasterDb() {
		$entityId = new ItemId( 'Q23' );
		$revisionLookup = $this->createMock( EntityRevisionLookup::class );
		$revisionLookup->expects( $this->once() )
			->method( 'getLatestRevisionId' )
			->with( $entityId, LookupConstants::LATEST_FROM_MASTER )
			->willReturn( LatestRevisionIdResult::nonexistentEntity() );

		$this->newRedirectLookup( $revisionLookup )->getRedirectForEntityId(
			$entityId,
			EntityRedirectTargetLookup::FOR_UPDATE
		);
	}

	private function newRedirectLookup( EntityRevisionLookup $revisionLookup ): RevisionBasedEntityRedirectTargetLookup {
		return new RevisionBasedEntityRedirectTargetLookup( $revisionLookup );
	}

}
