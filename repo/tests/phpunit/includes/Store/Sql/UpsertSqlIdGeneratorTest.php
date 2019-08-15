<?php

namespace Wikibase\Repo\Tests\Store\Sql;

use MediaWiki\MediaWikiServices;
use Wikibase\Repo\Store\Sql\UpsertSqlIdGenerator;

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
class UpsertSqlIdGeneratorTest extends \MediaWikiTestCase {

	public function setUp() {
		parent::setUp();
		if ( $this->db->getType() !== 'mysql' ) {
			$this->markTestSkipped( 'Can only be tested with a mysql DB' );
		}
	}

	public function testGetNewId_noBlacklist() {
		$generator = new UpsertSqlIdGenerator( MediaWikiServices::getInstance()->getDBLoadBalancer() );

		$id = $generator->getNewId( 'wikibase-upsert-kittens' );
		$this->assertSame( 1, $id );
	}

	public function testIdBlacklisting() {
		$generator = new UpsertSqlIdGenerator(
			MediaWikiServices::getInstance()->getDBLoadBalancer(),
			[ 'wikibase-upsert-blacklist' => [ 1, 2 ] ]
		);

		$id = $generator->getNewId( 'wikibase-upsert-blacklist' );
		$this->assertSame( 3, $id );
	}

	public function testIdBlacklisting_onlyAppliesForSpecifiedEntityType() {
		$generator = new UpsertSqlIdGenerator(
			MediaWikiServices::getInstance()->getDBLoadBalancer(),
			[ 'wikibase-upsert-blacklist' => [ 1, 2 ] ]
		);

		$id = $generator->getNewId( 'wikibase-upsert-non-blacklist' );
		$this->assertSame( 1, $id );
	}

}
