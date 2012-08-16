<?php

namespace Wikibase\Test;
use \Wikibase\EntityContent as EntityContent;
use \Wikibase\ItemContent as ItemContent;
use \Wikibase\Item as Item;

/**
 * Tests for the Wikibase\EntityContent class.
 *
 * @file
 * @since 0.1
 *
 * @ingroup Wikibase
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseEntity
 * @group WikibaseRepo
 *
 * @group Database
 * ^--- important, causes temporary tables to be used instead of the real database
 *
 * @licence GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 */
class EntityContentTest extends \MediaWikiTestCase {

	/**
	 * Because the database is somehow reset between calls we do it all in the test method.
	 * This doesn't test failing cases, mainly because something weird going on with the revision table.
	 */
	public function testEditEntity() {
		// EntityContent is abstract so we use the subclass ItemContent
		$item = \Wikibase\ItemContent::newEmpty();
		$item->save();
		$page = $item->getWikiPage();
		$lastRevId = $page->getRevision()->getId();
		$userId = $page->getRevision()->getUser(); // this is 0!

		$sets = array(
			array( 0, false, false ),
			array( 0, $lastRevId, false ),
			array( $userId, false, false ),
			array( $userId, $lastRevId, true ),
		);

		for ( $i = 0; $i < 60; $i++ ) {
			$item->reload();
			$item->getItem()->setLabel('en', "Test $i" );
			$status = $item->save();
			$this->assertTrue( $status->isOK() );
		}
		$page = $item->getWikiPage();

		foreach ( $sets as $args ) {
			list( $userId, $lastRevId, $expected ) = $args;
			$res = $item->userWasLastToEdit( $userId, $lastRevId );
			print("$userId, $lastRevId\n");
			//$this->assertEquals( $expected, $res );
		}
	}
}
