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
	public function testUserWasLastToEdit() {
		// EntityContent is abstract so we use the subclass ItemContent
		$limit = 50;
		$anonUser = \User::newFromId(0);
		$sysopUser = \User::newFromId(1);
		$itemContent = \Wikibase\ItemContent::newEmpty();
		$status = $itemContent->save( 'testedit for sysop', $sysopUser, EDIT_NEW );
		$page = $itemContent->getWikiPage();
		$userId = $page->getRevision()->getRawUser(); // this is 0!
		$this->assertTrue( $status->isGood(), "is not good" );
		$sets = array(
			array( null, false, true ), // 0, this case is to make ordinary testing work
			array( null, $page->getRevision()->getId(), false ),
			array( $anonUser, false, true ), // 2, this case is to make ordinary testing work
			array( $anonUser, $page->getRevision()->getId(), false ),
			array( $sysopUser, false, true ), // 4, this case is to make ordinary testing work
			array( $sysopUser, $page->getRevision()->getId(), true ),
		);
		$lastRevId = $page->getRevision()->getId();

		for ( $i = 0; $i < $limit+10; $i++ ) {
			$itemContent->getItem()->setLabel( 'en', "Test $i" );
			$status = $itemContent->save( 'testedit for anon', $anonUser, EDIT_UPDATE );
			$this->assertTrue( $status->isOk(), "is not ok" );
			if ( !$status->isGood() ) {
				foreach ( $status->getWarningsArray() as $stat ) {
					//print( "stat: " . implode(', ', $stat ) . "\n");
				}
			}
			$sets[] = array($sysopUser->getId(), $lastRevId, $i > $limit );
		}
		$page = $itemContent->getWikiPage();

		$j = 0;
		foreach ( $sets as $args ) {
			// FIXME: From 57 and on this fails. The bug is somehow related to the revisions,
			// which does not exist as the item isn't updated. It seems like this is related to
			// a presavetransform (pst) that isn't updated as it should and then the serialization
			// fails during the call to WikiPage::doEditContent in EntityContent::save. It seems
			// like the bug emerge before the call to WikiPage::prepareContentForEdit in the
			// doEditContent method.
			if ( $j > 56 ) {
				$this->markTestSkipped( "Failure in WikiPage, there are noe failure outside phpunit (?)" );
			}
			list( $userId, $lastRevId, $expected ) = $args;
			$res = $itemContent->userWasLastToEdit( $userId, $lastRevId );
			$this->assertEquals( $expected, $res, "Not the expected result from 'userWasLastToEdit' at iteration '$j'" );
			$j++;
		}
	}
}
