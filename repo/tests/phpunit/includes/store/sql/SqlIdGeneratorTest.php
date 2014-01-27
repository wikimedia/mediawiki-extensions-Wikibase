<?php

namespace Wikibase\Test;

use Wikibase\IdGenerator;
use Wikibase\Settings;
use Wikibase\StoreFactory;

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
		$generator = StoreFactory::getStore( 'sqlstore' )->newIdGenerator();

		for ( $i = 0; $i < 45; ++$i ) {
			$this->assertFalse( in_array( $generator->getNewId( 'blacklisttest' ), Settings::get( 'idBlacklist' ) ) );
		}
	}

}
