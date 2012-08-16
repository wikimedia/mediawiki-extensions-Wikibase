<?php

namespace Wikibase\Test;
use \Wikibase\EditEntity as EditEntity;
use \Wikibase\ItemContent as ItemContent;

/**
 * Test EditEntity.
 *
 * @file
 * @since 0.1
 *
 * @ingroup Wikibase
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group EditEntity
 *
 * The database group has as a side effect that temporal database tables are created. This makes
 * it possible to test without poisoning a production database.
 * @group Database
 *
 * @licence GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 *
 */
class EditEntityTest extends \MediaWikiTestCase {

	public function providerEditEntity() {
		$fatal = \Status::newGood();
		$fatal->setResult( false );

		return array(
			array( // #0: case I: no base rev given.
				array(
					'label' => array( 'en' => 'test' ),
					'description' => array( 'en' => 'more testing' ),
					'aliases' => array(),
					'links' => array(),
				),
				false,
				3,
				\Status::newGood( 'ok' ),
				array(
					'label' => array( 'en' => 'test' ),
					'description' => array( 'en' => 'more testing' ),
					'aliases' => array(),
					'links' => array(),
				)
			),
			array( // #1: case II: base rev == current
				array(
					'label' => array( 'en' => 'test' ),
					'description' => array( 'en' => 'more testing' ),
					'aliases' => array(),
					'links' => array(),
				),
				3,
				3,
				\Status::newGood( 'only-to-edit' ),
				array(
					'label' => array( 'en' => 'test' ),
					'description' => array( 'en' => 'more testing' ),
					'aliases' => array(),
					'links' => array(),
				)
			),
			array( // #2: case III: user was last to edit
				array(
					'label' => array( 'en' => 'test', 'de' => 'test' ),
					'description' => array( 'en' => 'more testing' ),
					'aliases' => array(),
					'links' => array(),
				),
				2,
				3,
				\Status::newGood( 'last-to-edit' ),
				array(
					'label' => array( 'en' => 'test', 'de' => 'test' ),
					'description' => array( 'en' => 'more testing' ),
					'aliases' => array(),
					'links' => array(),
				)
			),
			array( // #3: case V: patch failed
				array(
					'label' => array( 'nl' => 'test', 'de' => 'bar' ),
					'description' => array( 'en' => 'more testing' ),
					'aliases' => array(),
				),
				0,
				3,
				$fatal,
				null
			),
			/* // patchign not yet supported
			array( // #4: case IV: patch successful
				array(
					'label' => array( 'nl' => 'test', 'dk' => 'bar' ),
					'description' => array( 'en' => 'more testing' ),
					'aliases' => array(),
					'links' => array(),
				),
				0,
				3,
				\Status::newGood( 'ok' ), //TODO: should contain a warning!
				array(
					'label' => array( 'en' => 'bar', 'nl' => 'test', 'dk' => 'bar' ),
					'description' => array( 'en' => 'more testing' ),
					'aliases' => array(),
					'links' => array(),
				)
			),
			*/
		);
	}

	private static $testRevisions = null;

	protected static function getTestRevisions() {
		global $wgUser;

		if ( self::$testRevisions === null ) {
			$otherUser = \User::newFromName( "EditEntityTestUser2" );

			if ( $otherUser->getId() === 0 ) {
				$otherUser = \User::createNew( $otherUser->getName() );
			}

			$itemContent = ItemContent::newEmpty();
			$itemContent->getEntity()->setLabel('en', "foo");
			$itemContent->save( "rev 0", $wgUser );
			self::$testRevisions[] = $itemContent->getWikiPage()->getRevision();

			$itemContent->getEntity()->setLabel('en', "bar");
			$itemContent->save( "rev 1", $otherUser );
			self::$testRevisions[] = $itemContent->getWikiPage()->getRevision();

			$itemContent->getEntity()->setLabel('de', "bar");
			$itemContent->save( "rev 2", $wgUser );
			self::$testRevisions[] = $itemContent->getWikiPage()->getRevision();

			$itemContent->getEntity()->setLabel('en', "test");
			$itemContent->getEntity()->setDescription('en', "more testing");
			$itemContent->save( "rev 3", $wgUser );
			self::$testRevisions[] = $itemContent->getWikiPage()->getRevision();
		}

		return self::$testRevisions;
	}

	/**
	 * @dataProvider providerEditEntity
	 */
	public function testEditEntity( array $inputData, $baseRevisionIdx, $applicableRevisionIdx, \Status $expectedStatus, array $expectedData = null ) {
		global $wgUser;

		$revisions = self::getTestRevisions();

		$baseRevisionId = $baseRevisionIdx !== false ? $revisions[$baseRevisionIdx]->getId() : false;
		$applicableRevisionId = $applicableRevisionIdx !== false ? $revisions[$applicableRevisionIdx]->getId() : false;

		$content = \Wikibase\ItemContent::newFromArray( $inputData );
		$entity = $content->getEntity();
		$editEntity = EditEntity::newEditEntity( $entity, $wgUser, $baseRevisionId, $applicableRevisionId );

		$status = $editEntity->getStatus();

		if ( $expectedStatus->isOK() ) {
			//$this->assertTrue( $editEntity->isSuccess() );

			$this->assertTrue( $status->isOK() );
			$this->assertEquals( $expectedStatus->value, $status->value );
			$this->assertArrayEquals( $expectedStatus->getWarningsArray(), $status->getWarningsArray() );

			if ( $expectedData !== null ) {
				$patched = $editEntity->getPatchedEntity();
				$this->assertNotNull( $patched );

				$data = $patched->toArray();
				unset( $data['entity'] );

				$this->assertEquals( $expectedData, $data );
			}
		} else {
			$this->assertFalse( $editEntity->isSuccess() );
			$this->assertArrayEquals( $expectedStatus->getErrorsArray(), $status->getErrorsArray() );
		}

		//TODO: we should also test that EntityContent::save and WikiPage::doEdit fail if the
		//      true last revision is not $applicableRevisionIdx. But that needs a different
		//      test setup.
	}

	/**
	 * @TODO: test userWasLastToEdit
	 */
	public function testUserWasLastToEdit() {
		// EntityContent is abstract so we use the subclass ItemContent
		// to get a concrete class to instantiate. Still note that our
		// test target is EntityContent::userWasLastToEdit.
		$limit = 50;
		$anonUser = \User::newFromId(0);
		$sysopUser = \User::newFromId(1);
		$itemContent = \Wikibase\ItemContent::newEmpty();

		// check for default values, last revision by anon --------------------
		$itemContent->getItem()->setLabel( 'en', "Test Anon default" );
		$status = $itemContent->save( 'testedit for anon', $anonUser, EDIT_NEW );
		$this->assertTrue( $status->isGood() );
		$res = EditEntity::userWasLastToEdit( false, false );
		$this->assertFalse( $res );

		// check for default values, last revision by sysop --------------------
		$itemContent->getItem()->setLabel( 'en', "Test SysOp default" );
		$status = $itemContent->save( 'testedit for sysop', $sysopUser, EDIT_UPDATE );
		$this->assertTrue( $status->isGood() );
		$res = EditEntity::userWasLastToEdit( false, false );
		$this->assertFalse( $res );

		// check for default values, last revision by anon --------------------
		$itemContent->getItem()->setLabel( 'en', "Test Anon with user" );
		$status = $itemContent->save( 'testedit for anon', $anonUser, EDIT_UPDATE );
		$this->assertTrue( $status->isGood() );
		$res = EditEntity::userWasLastToEdit( $anonUser->getId(), false );
		$this->assertFalse( $res );

		// check for default values, last revision by sysop --------------------
		$itemContent->getItem()->setLabel( 'en', "Test SysOp with user" );
		$status = $itemContent->save( 'testedit for sysop', $sysopUser, EDIT_UPDATE );
		$this->assertTrue( $status->isGood() );
		$res = EditEntity::userWasLastToEdit( $sysopUser->getId(), false );
		$this->assertFalse( $res );

		// create an edit and check if the anon user is last to edit --------------------
		$page = $itemContent->getWikiPage();
		$lastRevId = $page->getRevision()->getId();
		$itemContent->getItem()->setLabel( 'en', "Test Anon" );
		$status = $itemContent->save( 'testedit for anon', $anonUser, EDIT_UPDATE );
		$this->assertTrue( $status->isGood() );
		$res = EditEntity::userWasLastToEdit( $anonUser->getId(), $lastRevId );
		$this->assertTrue( $res );
		// also check that there is a failure if we use the sysop user
		$res = EditEntity::userWasLastToEdit( $sysopUser->getId(), $lastRevId );
		$this->assertFalse( $res );

		// create an edit and check if the sysop user is last to edit --------------------
		$page = $itemContent->getWikiPage();
		$lastRevId = $page->getRevision()->getId();
		$itemContent->getItem()->setLabel( 'en', "Test SysOp" );
		$status = $itemContent->save( 'testedit for sysop', $sysopUser, EDIT_UPDATE );
		$this->assertTrue( $status->isGood() );
		$res = EditEntity::userWasLastToEdit( $sysopUser->getId(), $lastRevId );
		$this->assertTrue( $res );
		// also check that there is a failure if we use the anon user
		$res = EditEntity::userWasLastToEdit( $anonUser->getId(), $lastRevId );
		$this->assertFalse( $res );
	}
}