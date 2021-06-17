<?php

namespace Wikibase\Repo\Tests\Store\Sql;

use MediaWikiIntegrationTestCase;
use Wikibase\Lib\Rdbms\RepoDomainDb;
use Wikibase\Repo\Store\Sql\SqlIdGenerator;
use Wikimedia\Rdbms\LBFactorySingle;

/**
 * @covers \Wikibase\Repo\Store\Sql\SqlIdGenerator
 *
 * @group Wikibase
 * @group WikibaseStore
 * @group Database
 *
 * @group medium
 *
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class SqlIdGeneratorTest extends MediaWikiIntegrationTestCase {

	public function testGetNewId_noReservedIds() {
		$generator = new SqlIdGenerator( $this->getRepoDomainDb() );

		$id = $generator->getNewId( 'wikibase-kittens' );
		$this->assertSame( 1, $id );
	}

	public function testReservedIds() {
		$generator = new SqlIdGenerator(
			$this->getRepoDomainDb(),
			[ 'wikibase-reserved' => [ 1, 2 ] ]
		);

		$id = $generator->getNewId( 'wikibase-reserved' );
		$this->assertSame( 3, $id );
	}

	public function testReservedIds_onlyAppliesForSpecifiedEntityType() {
		$generator = new SqlIdGenerator(
			$this->getRepoDomainDb(),
			[ 'wikibase-reserved' => [ 1, 2 ] ]
		);

		$id = $generator->getNewId( 'wikibase-non-reserved' );
		$this->assertSame( 1, $id );
	}

	private function getRepoDomainDb(): RepoDomainDb {
		return new RepoDomainDb(
			LBFactorySingle::newFromConnection( $this->db ),
			$this->db->getDomainID()
		);
	}

}
