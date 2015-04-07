<?php

namespace Wikibase\Test;

use Wikibase\SqlIdGenerator;

/**
 * @covers Wikibase\SqlIdGenerator
 *
 * @group Wikibase
 * @group WikibaseStore
 * @group WikibaseRepo
 * @group Database
 *
 * @group medium
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class SqlIdGeneratorTest extends \MediaWikiTestCase {

	public function testGetNewId() {
		$generator = new SqlIdGenerator( 'wb_id_counters', wfGetDB( DB_MASTER ), array() );

		$id = $generator->getNewId( 'wikibase-kittens' );
		$this->assertSame( 1, $id );
	}

	public function testIdBlacklisting() {
		$generator = new SqlIdGenerator( 'wb_id_counters', wfGetDB( DB_MASTER ), array( 1, 2 ) );

		$id = $generator->getNewId( 'wikibase-blacklist' );
		$this->assertSame( 3, $id );
	}

}
