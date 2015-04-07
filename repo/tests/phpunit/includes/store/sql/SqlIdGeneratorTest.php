<?php

namespace Wikibase\Test;

use Wikibase\IdGenerator;
use Wikibase\Repo\WikibaseRepo;

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
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SqlIdGeneratorTest extends \MediaWikiTestCase {

	public function testGetNewId() {
		$generator = WikibaseRepo::getDefaultInstance()->getStore()->newIdGenerator();

		$idType = 'wikibase-kittens';
		$id = $generator->getNewId( $idType );

		// 1 is in the blacklist, so count starts with 2
		$this->assertEquals( 2, $id );
	}

	public function testIdBlacklisting() {
		$generator = WikibaseRepo::getDefaultInstance()->getStore()->newIdGenerator();
		$idBlacklist = WikibaseRepo::getDefaultInstance()->
			getSettings()->getSetting( 'idBlacklist' );

		for ( $i = 0; $i < 45; ++$i ) {
			$this->assertFalse( in_array( $generator->getNewId( 'blacklisttest' ), $idBlacklist ) );
		}
	}

}
