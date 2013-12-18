<?php

namespace Wikibase\Test;

use Wikibase\IdGenerator;
use Wikibase\Settings;
use Wikibase\StoreFactory;

/**
 * @covers Wikibase\SqlIdGenerator
 *
 * @since 0.1
 *
 * @group Wikibase
 * @group WikibaseStore
 * @group WikibaseRepo
 * @group Database
 *
 * Some of the tests takes more time, and needs therefor longer time before they can be aborted
 * as non-functional. The reason why tests are aborted is assumed to be set up of temporal databases
 * that hold the first tests in a pending state awaiting access to the database.
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
