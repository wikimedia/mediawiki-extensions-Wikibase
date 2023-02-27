<?php

namespace Wikibase\Repo\Tests\Store\Sql;

use MediaWikiIntegrationTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Wikibase\Lib\Rdbms\RepoDomainDb;
use Wikibase\Lib\Tests\Rdbms\LocalRepoDbTestHelper;
use Wikibase\Repo\Store\Sql\DispatchStats;
use Wikimedia\Rdbms\ConnectionManager;
use Wikimedia\Rdbms\DBConnRef;
use Wikimedia\Rdbms\SelectQueryBuilder;

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

	private function getRepoDomainDbMock( SelectQueryBuilder $selectQueryBuilder ): RepoDomainDb {
		$dbConnRefMock = $this->createMock( DBConnRef::class );
		$dbConnRefMock->method( 'newSelectQueryBuilder' )->willReturn( $selectQueryBuilder );
		$connManagerMock = $this->createMock( ConnectionManager::class );
		$connManagerMock->method( 'getReadConnection' )->willReturn( $dbConnRefMock );
		$dbMock = $this->createMock( RepoDomainDb::class );
		$dbMock->method( 'connections' )->willReturn( $connManagerMock );

		return $dbMock;
	}

	/** @return MockObject|SelectQueryBuilder */
	private function getSelectQueryBuilderMock() {
		$mock = $this->createMock( SelectQueryBuilder::class );
		$mock->method( $this->anythingBut(
			'fetchResultSet', 'fetchField', 'fetchFieldValues', 'fetchRow',
			'fetchRowCount', 'estimateRowCount'
		) )->willReturnSelf();
		return $mock;
	}

	public function testGetDispatchStats_empty(): void {
		$selectQueryBuilder = $this->getSelectQueryBuilderMock();
		$selectQueryBuilder->method( 'fetchRowCount' )->willReturn( 0 );
		$dispatchStats = new DispatchStats( $this->getRepoDomainDbMock( $selectQueryBuilder ) );

		$actualStats = $dispatchStats->getDispatchStats();

		$this->assertSame( [ 'numberOfChanges' => 0 ], $actualStats );
	}

	public function testGetDispatchStats_exact(): void {
		$selectQueryBuilder = $this->getSelectQueryBuilderMock();
		$selectQueryBuilder->method( 'fetchRowCount' )->willReturn( 3 );
		$selectQueryBuilder->method( 'fetchRow' )->willReturn( (object)[
			'freshestTime' => '20211018155646',
			'stalestTime' => '20211018155100',
		] );
		$selectQueryBuilder->method( 'fetchField' )->willReturn( '2' );
		$dispatchStats = new DispatchStats( $this->getRepoDomainDbMock( $selectQueryBuilder ) );

		$actualStats = $dispatchStats->getDispatchStats();

		$this->assertSame( [
			'numberOfChanges' => 3,
			'numberOfEntities' => 2,
			'freshestTime' => '20211018155646',
			'stalestTime' => '20211018155100',
		], $actualStats );
	}

	public function testGetDispatchStats_estimated(): void {
		$selectQueryBuilder = $this->getSelectQueryBuilderMock();
		$selectQueryBuilder->method( 'fetchRowCount' )->willReturn( 5001 );
		$selectQueryBuilder->method( 'estimateRowCount' )->willReturn( 30000 );
		$selectQueryBuilder->method( 'fetchRow' )->willReturn( (object)[
			'freshestTime' => '20211018155646',
			'stalestTime' => '20211018155100',
		] );
		$dispatchStats = new DispatchStats( $this->getRepoDomainDbMock( $selectQueryBuilder ) );

		$actualStats = $dispatchStats->getDispatchStats();

		$this->assertSame( [
			'estimatedNumberOfChanges' => 30000,
			'freshestTime' => '20211018155646',
			'stalestTime' => '20211018155100',
		], $actualStats );
	}

	public function testGetDispatchStats_estimateOutdated(): void {
		$selectQueryBuilder = $this->getSelectQueryBuilderMock();
		$selectQueryBuilder->method( 'fetchRowCount' )->willReturn( 5001 );
		$selectQueryBuilder->method( 'fetchRow' )->willReturn( (object)[
			'freshestTime' => '20211018155646',
			'stalestTime' => '20211018155100',
		] );
		$selectQueryBuilder->method( 'estimateRowCount' )->willReturn( 400 );
		$dispatchStats = new DispatchStats( $this->getRepoDomainDbMock( $selectQueryBuilder ) );

		$actualStats = $dispatchStats->getDispatchStats();

		$this->assertSame( [
			'minimumNumberOfChanges' => 5001,
			'freshestTime' => '20211018155646',
			'stalestTime' => '20211018155100',
		], $actualStats );
	}

}
