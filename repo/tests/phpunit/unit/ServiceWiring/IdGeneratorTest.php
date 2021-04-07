<?php

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use InvalidArgumentException;
use Wikibase\Lib\SettingsArray;
use Wikibase\Repo\Store\RateLimitingIdGenerator;
use Wikibase\Repo\Store\Sql\SqlIdGenerator;
use Wikibase\Repo\Store\Sql\UpsertSqlIdGenerator;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;
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
				'idGeneratorRateLimiting' => false,
			] ) );

		$idGenerator = $this->getService( 'WikibaseRepo.IdGenerator' );

		$this->assertInstanceOf( SqlIdGenerator::class, $idGenerator );
		$idGenerator = TestingAccessWrapper::newFromObject( $idGenerator );
		$this->assertSame( $reservedIds, $idGenerator->reservedIds );
		$this->assertSame( true, $idGenerator->separateDbConnection );
	}

	public function testConstructionMysqlUpsert() {
		$reservedIds = [ 'item' => [ 1, 2, 3, 4, 5 ] ];
		$this->mockService( 'WikibaseRepo.Settings',
			new SettingsArray( [
				'idGenerator' => 'mysql-upsert',
				'reservedIds' => $reservedIds,
				'idGeneratorSeparateDbConnection' => true,
				'idGeneratorRateLimiting' => false,
			] ) );

		$idGenerator = $this->getService( 'WikibaseRepo.IdGenerator' );

		$this->assertInstanceOf( UpsertSqlIdGenerator::class, $idGenerator );
		$idGenerator = TestingAccessWrapper::newFromObject( $idGenerator );
		$this->assertSame( $reservedIds, $idGenerator->reservedIds );
		$this->assertSame( true, $idGenerator->separateDbConnection );
	}

	public function testConstructionUnknownType() {
		$this->mockService( 'WikibaseRepo.Settings',
			new SettingsArray( [
				'idGenerator' => 'unknown',
			] ) );

		$this->expectException( InvalidArgumentException::class );
		$this->getService( 'WikibaseRepo.IdGenerator' );
	}

	public function testConstructionWithRateLimiting() {
		$this->mockService( 'WikibaseRepo.Settings',
			new SettingsArray( [
				'idGenerator' => 'original',
				'reservedIds' => [],
				'idGeneratorSeparateDbConnection' => false,
				'idGeneratorRateLimiting' => true,
			] ) );

		$idGenerator = $this->getService( 'WikibaseRepo.IdGenerator' );

		$this->assertInstanceOf( RateLimitingIdGenerator::class, $idGenerator );
	}

}
