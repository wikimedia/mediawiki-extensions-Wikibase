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
			] ) );

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

		$idGenerator = $this->getService( 'WikibaseRepo.IdGenerator' );

		$this->assertInstanceOf( RateLimitingIdGenerator::class, $idGenerator );

		// Look at the 1st test to see the purpose of this
		$idGenerator = TestingAccessWrapper::newFromObject( $idGenerator );

		$this->assertInstanceOf( UpsertSqlIdGenerator::class, $idGenerator->idGenerator );

		$innerGenerator = TestingAccessWrapper::newFromObject( $idGenerator->idGenerator );

		$this->assertSame( $reservedIds, $innerGenerator->reservedIds );
		$this->assertSame( true, $innerGenerator->separateDbConnection );
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
			] ) );

		$idGenerator = $this->getService( 'WikibaseRepo.IdGenerator' );

		$this->assertInstanceOf( RateLimitingIdGenerator::class, $idGenerator );
	}

}
