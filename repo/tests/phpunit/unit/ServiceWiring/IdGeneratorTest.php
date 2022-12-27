<?php

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use InvalidArgumentException;
use Wikibase\Lib\Rdbms\RepoDomainDb;
use Wikibase\Lib\Rdbms\RepoDomainDbFactory;
use Wikibase\Lib\SettingsArray;
use Wikibase\Repo\Store\RateLimitingIdGenerator;
use Wikibase\Repo\Store\Sql\SqlIdGenerator;
use Wikibase\Repo\Store\Sql\UpsertSqlIdGenerator;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;
use Wikimedia\Rdbms\ConnectionManager;
use Wikimedia\Rdbms\DBConnRef;
use Wikimedia\TestingAccessWrapper;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class IdGeneratorTest extends ServiceWiringTestCase {

	public function testConstructionOriginal() {
		$reservedIds = [ 'item' => [ 1, 2, 3, 4, 5 ] ];
		$this->mockService( 'WikibaseRepo.Settings',
			new SettingsArray( [
				'idGenerator' => 'original',
				'reservedIds' => $reservedIds,
				'idGeneratorSeparateDbConnection' => true,
			] ) );
		$dbFactory = $this->createMock( RepoDomainDbFactory::class );
		$dbFactory->expects( $this->once() )
			->method( 'newRepoDb' );
		$this->mockService( 'WikibaseRepo.RepoDomainDbFactory',
			$dbFactory );

		$idGenerator = $this->getService( 'WikibaseRepo.IdGenerator' );

		$this->assertInstanceOf( RateLimitingIdGenerator::class, $idGenerator );

		// the inner generator of the RateLimitingIdGenerator is private
		// we need to use TestingAccessWrapper to reach that property
		$idGenerator = TestingAccessWrapper::newFromObject( $idGenerator );

		$this->assertInstanceOf( SqlIdGenerator::class, $idGenerator->idGenerator );

		// the inner properties of SqlIdGenerator are private
		// we need to use TestingAccessWrapper to reach those
		$innerGenerator = TestingAccessWrapper::newFromObject( $idGenerator->idGenerator );

		$this->assertSame( $reservedIds, $innerGenerator->reservedIds );
		$this->assertSame( true, $innerGenerator->separateDbConnection );
	}

	public function testConstructionMysqlUpsert() {
		$reservedIds = [ 'item' => [ 1, 2, 3, 4, 5 ] ];
		$this->mockService( 'WikibaseRepo.Settings',
			new SettingsArray( [
				'idGenerator' => 'mysql-upsert',
				'reservedIds' => $reservedIds,
				'idGeneratorSeparateDbConnection' => true,
			] ) );
		$dbFactory = $this->createMock( RepoDomainDbFactory::class );
		$dbFactory->expects( $this->once() )
			->method( 'newRepoDb' );
		$this->mockService( 'WikibaseRepo.RepoDomainDbFactory',
			$dbFactory );

		$idGenerator = $this->getService( 'WikibaseRepo.IdGenerator' );

		$this->assertInstanceOf( RateLimitingIdGenerator::class, $idGenerator );

		// Look at the 1st test to see the purpose of this
		$idGenerator = TestingAccessWrapper::newFromObject( $idGenerator );

		$this->assertInstanceOf( UpsertSqlIdGenerator::class, $idGenerator->idGenerator );

		$innerGenerator = TestingAccessWrapper::newFromObject( $idGenerator->idGenerator );

		$this->assertSame( $reservedIds, $innerGenerator->reservedIds );
		$this->assertSame( true, $innerGenerator->separateDbConnection );
	}

	public function testConstructionAutoMysqlUpsert() {
		$this->mockService( 'WikibaseRepo.Settings',
			new SettingsArray( [
				'idGenerator' => 'auto',
				'reservedIds' => [],
				'idGeneratorSeparateDbConnection' => false,
			] ) );
		$connection = $this->createMock( DBConnRef::class );
		$connection->expects( $this->once() )
			->method( 'getType' )
			->willReturn( 'mysql' );
		$connections = $this->createMock( ConnectionManager::class );
		$connections->expects( $this->once() )
			->method( 'getWriteConnection' )
			->willReturn( $connection );
		$db = $this->createMock( RepoDomainDb::class );
		$db->expects( $this->once() )
			->method( 'connections' )
			->willReturn( $connections );
		$dbFactory = $this->createMock( RepoDomainDbFactory::class );
		$dbFactory->expects( $this->once() )
			->method( 'newRepoDb' )
			->willReturn( $db );
		$this->mockService( 'WikibaseRepo.RepoDomainDbFactory',
			$dbFactory );

		$idGenerator = $this->getService( 'WikibaseRepo.IdGenerator' );

		$this->assertInstanceOf( RateLimitingIdGenerator::class, $idGenerator );

		// Look at the 1st test to see the purpose of this
		$idGenerator = TestingAccessWrapper::newFromObject( $idGenerator );

		$this->assertInstanceOf( UpsertSqlIdGenerator::class, $idGenerator->idGenerator );
	}

	public function testConstructionAutoOriginal() {
		$this->mockService( 'WikibaseRepo.Settings',
			new SettingsArray( [
				'idGenerator' => 'auto',
				'reservedIds' => [],
				'idGeneratorSeparateDbConnection' => false,
			] ) );
		$connection = $this->createMock( DBConnRef::class );
		$connection->expects( $this->once() )
			->method( 'getType' )
			->willReturn( 'sqlite' );
		$connections = $this->createMock( ConnectionManager::class );
		$connections->expects( $this->once() )
			->method( 'getWriteConnection' )
			->willReturn( $connection );
		$db = $this->createMock( RepoDomainDb::class );
		$db->expects( $this->once() )
			->method( 'connections' )
			->willReturn( $connections );
		$dbFactory = $this->createMock( RepoDomainDbFactory::class );
		$dbFactory->expects( $this->once() )
			->method( 'newRepoDb' )
			->willReturn( $db );
		$this->mockService( 'WikibaseRepo.RepoDomainDbFactory',
			$dbFactory );

		$idGenerator = $this->getService( 'WikibaseRepo.IdGenerator' );

		$this->assertInstanceOf( RateLimitingIdGenerator::class, $idGenerator );

		// Look at the 1st test to see the purpose of this
		$idGenerator = TestingAccessWrapper::newFromObject( $idGenerator );

		$this->assertInstanceOf( SqlIdGenerator::class, $idGenerator->idGenerator );
	}

	public function testConstructionUnknownType() {
		$this->mockService( 'WikibaseRepo.Settings',
			new SettingsArray( [
				'idGenerator' => 'unknown',
			] ) );
		$db = $this->createMock( RepoDomainDb::class );
		$db->expects( $this->never() )
			->method( $this->anything() );
		$dbFactory = $this->createMock( RepoDomainDbFactory::class );
		$dbFactory->expects( $this->once() )
			->method( 'newRepoDb' )
			->willReturn( $db );
		$this->mockService( 'WikibaseRepo.RepoDomainDbFactory',
			$dbFactory );

		$this->expectException( InvalidArgumentException::class );
		$this->getService( 'WikibaseRepo.IdGenerator' );
	}

	public function testConstructionWithRateLimiting() {
		$this->mockService( 'WikibaseRepo.Settings',
			new SettingsArray( [
				'idGenerator' => 'original',
				'reservedIds' => [],
				'idGeneratorSeparateDbConnection' => false,
			] ) );
		$dbFactory = $this->createMock( RepoDomainDbFactory::class );
		$dbFactory->expects( $this->once() )
			->method( 'newRepoDb' );
		$this->mockService( 'WikibaseRepo.RepoDomainDbFactory',
			$dbFactory );

		$idGenerator = $this->getService( 'WikibaseRepo.IdGenerator' );

		$this->assertInstanceOf( RateLimitingIdGenerator::class, $idGenerator );
	}

}
