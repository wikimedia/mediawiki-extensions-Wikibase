<?php

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Changes\EntityChange;
use Wikibase\Lib\Tests\Changes\ChangeRowTest;
use Wikibase\Lib\Tests\Changes\TestChanges;

/**
 * Class RepoEntityChangeTest
 * @group Wikibase
 * @license GPL-2.0-or-later
 */
class RepoEntityChangeTest extends ChangeRowTest {

	public function testSetTimestamp() {
		$q7 = new ItemId( 'Q7' );

		$changeFactory = TestChanges::getEntityChangeFactory();
		$change = $changeFactory->newForEntity( EntityChange::UPDATE, $q7 );

		$timestamp = '20140523' . '174422';
		$change->setTimestamp( $timestamp );
		$this->assertSame( $timestamp, $change->getTime() );
	}
}
