<?php

namespace Wikibase\Repo\Tests\Store\Sql;

use MediaWikiIntegrationTestCase;
use Wikibase\Lib\Rdbms\RepoDomainDb;
use Wikibase\Lib\Tests\Rdbms\LocalRepoDbTestHelper;
use Wikibase\Repo\Store\Sql\DispatchStats;
use Wikimedia\Rdbms\ConnectionManager;
use Wikimedia\Rdbms\DBConnRef;

/**
 * @covers \Wikibase\Repo\Store\Sql\DispatchStats
 *
 * @group Database
 * @group Wikibase
 * @group WikibaseChange
 *
 * @license GPL-2.0-or-later
 */
class DispatchStatsTest extends MediaWikiIntegrationTestCase {

	use LocalRepoDbTestHelper;

	private function getRepoDomainDbMock( DBConnRef $connRef ): RepoDomainDb {
		$connManagerMock = $this->createMock( ConnectionManager::class );
		$connManagerMock->method( 'getReadConnectionRef' )->willReturn( $connRef );
		$dbMock = $this->createMock( RepoDomainDb::class );
		$dbMock->method( 'connections' )->willReturn( $connManagerMock );

		return $dbMock;
	}

	public function testGetDispatchStats_empty(): void {
		$connectionRefMock = $this->createMock( DBConnRef::class );
		$connectionRefMock->method( 'selectRowCount' )->willReturn( 0 );
		$dispatchStats = new DispatchStats( $this->getRepoDomainDbMock( $connectionRefMock ) );

		$actualStats = $dispatchStats->getDispatchStats();

		$this->assertSame( $actualStats, [ 'numberOfChanges' => 0 ] );
	}

	public function testGetDispatchStats_exact(): void {
		$connectionRefMock = $this->createMock( DBConnRef::class );
		$connectionRefMock->method( 'selectRowCount' )->willReturn( 3 );
		$connectionRefMock->method( 'selectRow' )->willReturn(
			(object)[
				'freshestTime' => '20211018155646',
				'stalestTime' => '20211018155100',
			],
			(object)[
				'numberOfEntities' => '2',
			]
		);
		$dispatchStats = new DispatchStats( $this->getRepoDomainDbMock( $connectionRefMock ) );

		$actualStats = $dispatchStats->getDispatchStats();

		$this->assertSame( $actualStats, [
			'numberOfChanges' => 3,
			'numberOfEntities' => 2,
			'freshestTime' => '20211018155646',
			'stalestTime' => '20211018155100',
		] );
	}

	public function testGetDispatchStats_estimated(): void {
		$connectionRefMock = $this->createMock( DBConnRef::class );
		$connectionRefMock->method( 'selectRowCount' )->willReturn( 5001 );
		$connectionRefMock->method( 'estimateRowCount' )->willReturn( 30000 );
		$connectionRefMock->method( 'selectRow' )->willReturn(
			(object)[
				'freshestTime' => '20211018155646',
				'stalestTime' => '20211018155100',
			]
		);
		$dispatchStats = new DispatchStats( $this->getRepoDomainDbMock( $connectionRefMock ) );

		$actualStats = $dispatchStats->getDispatchStats();

		$this->assertSame( $actualStats, [
			'estimatedNumberOfChanges' => 30000,
			'freshestTime' => '20211018155646',
			'stalestTime' => '20211018155100',
		] );
	}

	public function testGetDispatchStats_estimateOutdated(): void {
		$connectionRefMock = $this->createMock( DBConnRef::class );
		$connectionRefMock->method( 'selectRowCount' )->willReturn( 5001 );
		$connectionRefMock->method( 'selectRow' )->willReturn(
			(object)[
				'freshestTime' => '20211018155646',
				'stalestTime' => '20211018155100',
			]
		);
		$connectionRefMock->method( 'estimateRowCount' )->willReturn( 400 );
		$dispatchStats = new DispatchStats( $this->getRepoDomainDbMock( $connectionRefMock ) );

		$actualStats = $dispatchStats->getDispatchStats();

		$this->assertSame( $actualStats, [
			'minimumNumberOfChanges' => 5001,
			'freshestTime' => '20211018155646',
			'stalestTime' => '20211018155100',
		] );
	}

}
