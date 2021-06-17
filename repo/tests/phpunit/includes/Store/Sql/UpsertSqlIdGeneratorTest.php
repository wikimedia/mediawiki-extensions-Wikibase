<?php

namespace Wikibase\Repo\Tests\Store\Sql;

use MediaWikiIntegrationTestCase;
use Wikibase\Lib\Rdbms\RepoDomainDb;
use Wikibase\Repo\Store\Sql\UpsertSqlIdGenerator;
use Wikimedia\Rdbms\LBFactorySingle;

/**
 * @covers \Wikibase\Repo\Store\Sql\UpsertSqlIdGenerator
 *
 * @group Wikibase
 * @group WikibaseStore
 * @group Database
 *
 * @group medium
 *
 * @license GPL-2.0-or-later
 */
class UpsertSqlIdGeneratorTest extends MediaWikiIntegrationTestCase {

	protected function setUp(): void {
		parent::setUp();
		if ( $this->db->getType() !== 'mysql' ) {
			$this->markTestSkipped( 'Can only be tested with a mysql DB' );
		}
	}

	public function testGetNewId_noReservedIds() {
		$generator = new UpsertSqlIdGenerator( $this->getRepoDomainDb() );

		$id = $generator->getNewId( 'wikibase-upsert-kittens' );
		$this->assertSame( 1, $id );
	}

	public function testReservedIds() {
		$generator = new UpsertSqlIdGenerator(
			$this->getRepoDomainDb(),
			[ 'wikibase-upsert-reserved' => [ 1, 2 ] ]
		);

		$id = $generator->getNewId( 'wikibase-upsert-reserved' );
		$this->assertSame( 3, $id );
	}

	public function testReservedIds_onlyAppliesForSpecifiedEntityType() {
		$generator = new UpsertSqlIdGenerator(
			$this->getRepoDomainDb(),
			[ 'wikibase-upsert-reserved' => [ 1, 2 ] ]
		);

		$id = $generator->getNewId( 'wikibase-upsert-non-reserved' );
		$this->assertSame( 1, $id );
	}

	private function getRepoDomainDb(): RepoDomainDb {
		return new RepoDomainDb(
			LBFactorySingle::newFromConnection( $this->db ),
			$this->db->getDomainID()
		);
	}

}
