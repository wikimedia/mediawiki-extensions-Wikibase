<?php

namespace Wikibase\Repo\Tests\Store\Sql;

use Wikibase\DataModel\Entity\Item;
use Wikibase\SqlIdGenerator;

/**
 * @covers Wikibase\SqlIdGenerator
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
class SqlIdGeneratorTest extends \MediaWikiTestCase {

	public function testGetNewId_noBlacklist() {
		$generator = new SqlIdGenerator( wfGetLB() );

		$id = $generator->getNewId( 'wikibase-kittens' );
		$this->assertSame( 1, $id );
	}

	public function testIdBlacklisting() {
		$generator = new SqlIdGenerator( wfGetLB(), [ 'wikibase-blacklist' => [ 1, 2 ] ] );

		$id = $generator->getNewId( 'wikibase-blacklist' );
		$this->assertSame( 3, $id );
	}

	public function testIdBlacklisting_onlyAppliesForSpecifiedEntityType() {
		$generator = new SqlIdGenerator( wfGetLB(), [ 'wikibase-blacklist' => [ 1, 2 ] ] );

		$id = $generator->getNewId( 'wikibase-non-blacklist' );
		$this->assertSame( 1, $id );
	}

}
