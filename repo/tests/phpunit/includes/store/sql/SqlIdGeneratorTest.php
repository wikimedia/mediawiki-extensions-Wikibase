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

	public function testIdBlacklisting() {
		/**
		 * @var IdGenerator $clone
		 */
		$generator = WikibaseRepo::getDefaultInstance()->getStore()->newIdGenerator();
		$idBlacklist = WikibaseRepo::getDefaultInstance()->
			getSettings()->getSetting( 'idBlacklist' );

		for ( $i = 0; $i < 45; ++$i ) {
			$this->assertFalse( in_array( $generator->getNewId( 'blacklisttest' ), $idBlacklist ) );
		}
	}

}
