<?php

namespace Wikibase\Test;
use \Wikibase\EntityContent as EntityContent;
use \Wikibase\EditEntity as EditEntity;
use \Wikibase\ItemContent as ItemContent;
use \Status as Status;
use \FauxRequest as FauxRequest;

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

	protected $permissions;
	protected $userGroups;

	function setUp() {
		global $wgGroupPermissions, $wgUser;
		global $wgOut, $wgTitle;

		parent::setUp();

		$this->permissions = $wgGroupPermissions;
		$this->userGroups = $wgUser->getGroups();

		if ( $wgTitle === null ) {
			$wgTitle = \Title::newFromText( "Test" );
		}

		$wgOut->setTitle( $wgTitle );

		static $hasTestSites = false;

		if ( !$hasTestSites ) {
			\TestSites::insertIntoDb();
			$hasTestSites = true;
		}
	}

	function tearDown() {
		global $wgGroupPermissions, $wgUser;

		$wgGroupPermissions = $this->permissions;

		$userGroups = $wgUser->getGroups();

		foreach ( array_diff( $this->userGroups, $userGroups ) as $group ) {
			$wgUser->addGroup( $group );
		}

		foreach ( array_diff( $userGroups, $this->userGroups ) as $group ) {
			$wgUser->removeGroup( $group );
		}

		$wgUser->getEffectiveGroups( true ); // recache

		parent::tearDown();
	}

	public function providerHasEditConflicts() {
		return array(
			array( // #0: case I: no base rev given.
				null, // input data
				null, // base rev index
				false, // expected conflict
			),
			array( // #1: case II: base rev == current
				null, // input data
				3,    // base rev index
				false // expected conflict
			),
			array( // #2: case III: user was last to edit
				null, // input data
				1,    // base rev index
				false, // expected conflict
			),
			array( // #3: case IV: patch applied
				array( // input data
					'label' => array( 'nl' => 'test', 'fr' => 'frrrrtt' ),
				),
				0,    // base rev index
				true,  // expected conflict //@todo: change this to "false" once we have full support for patching
				null    // expected data
				/*array(
					'label' => array( 'nl' => 'test', 'fr' => 'frrrrtt', 'en' => 'bar' ),
				)*/
			),
			array( // #4: case V: patch failed
				array( // input data
					'label' => array( 'nl' => 'test', 'de' => 'bar' ),
				),
				0,    // base rev index
				true,  // expected conflict
				null  // expected data
			),
		);
	}

	/**
	 * @dataProvider providerHasEditConflicts
	 */
	public function testHasEditConflicts( $inputData, $baseRevisionIdx, $expectedConflict, array $expectedData = null ) {
		global $wgUser;

		/* @var $content \Wikibase\EntityContent */
		/* @var $revision \Revision */

		$revisions = self::getTestRevisions();

		$baseRevisionId = is_int( $baseRevisionIdx ) ? $revisions[$baseRevisionIdx]->getId() : null;
		$revision = is_int( $baseRevisionIdx ) ? $revisions[$baseRevisionIdx] : $revisions[ count( $revisions ) -1 ];
		$content = $revision->getContent();
		$entity = $content->getEntity();

		// change entity ----------------------------------
		if ( $inputData === null ) {
			$entity->clear();
		} else {
			if ( !empty( $inputData['labels'] ) ) {
				foreach ( $inputData['labels'] as $k => $v ) {
					$entity->setLabel( $k, $v );
				}
			}

			if ( !empty( $inputData['description'] ) ) {
				foreach ( $inputData['description'] as $k => $v ) {
					$entity->setDescription( $k, $v );
				}
			}

			if ( !empty( $inputData['aliases'] ) ) {
				foreach ( $inputData['aliases'] as $k => $v ) {
					$entity->setAliases( $k, $v );
				}
			}
		}

		// save entity ----------------------------------
		$editEntity = new EditEntity( $content, $wgUser, $baseRevisionId );

		$conflict = $editEntity->hasEditConflict();

		$this->assertEquals( $expectedConflict, $conflict, 'hasEditConflict()' );
		$this->assertEquals( $expectedConflict, $editEntity->hasError( EditEntity::EDIT_CONFLICT_ERROR ) );
		$this->assertEquals( $expectedConflict, $editEntity->showErrorPage() );
		$this->assertNotEquals( $expectedConflict, $editEntity->getStatus()->isOK() );

		if ( $expectedData !== null ) {
			$this->assertArrayEquals( $expectedData, $editEntity->getNewContent()->getEntity()->toArray() );
		}
	}

	public function provideAttemptSaveWithLateConflict() {
		return array(
			array( true, true ),
			array( false, false ),
		);
	}

	/**
	 * @dataProvider provideAttemptSaveWithLateConflict
	 */
	public function testAttemptSaveWithLateConflict( $baseRevId, $expectedConflict ) {
		global $wgUser;

		// create item
		$content = ItemContent::newEmpty();
		$content->getEntity()->setLabel( 'en', 'Test' );
		$content->save( "rev 0", $wgUser );


		// begin editing the entity
		$content->getEntity()->setLabel( 'en', 'Trust' );

		$editEntity = new EditEntity( $content, $wgUser, $baseRevId );
		$editEntity->getCurrentRevision(); // make sure EditEntity has page and revision

		$this->assertEquals( $baseRevId, $editEntity->doesCheckForEditConflicts(), 'doesCheckForEditConflicts()' );

		// create independent EntityContent instance for the same entity, and modify and save it
		$page = \WikiPage::factory( $content->getTitle() );
		$content2 = $page->getContent();
		$content2->getEntity()->setLabel( 'en', 'Toast' );
		$content2->save( 'Trolololo!' );

		// now try to save the original edit. The conflict should still be detected
		$status = $editEntity->attemptSave( "Testing", EDIT_UPDATE );

		$this->assertNotEquals( $expectedConflict, $status->isOK(),
			'saving should have failed late if and only if a base rev was provided' );

		$this->assertEquals( $expectedConflict, $editEntity->hasError(),
			'saving should have failed late if and only if a base rev was provided' );

		$this->assertEquals( $expectedConflict, $status->hasMessage( 'edit-conflict' ),
			'saving should have failed late if and only if a base rev was provided' );

		$this->assertEquals( $expectedConflict, $editEntity->showErrorPage(),
			'if and only if there was an error, an error page should be show' );

	}

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

	public function dataCheckEditPermissions() {
		return array(
			array( #0: edit and createpage allowed for new item
				'user',
				array( 'read' => true, 'edit' => true, 'createpage' => true ),
				false,
				true,
			),
			array( #1: edit allowed but createpage not allowed for new item
				'user',
				array( 'read' => true, 'edit' => true, 'createpage' => false ),
				false,
				false,
			),
			array( #2: edit allowed but createpage not allowed for existing item
				'user',
				array( 'read' => true, 'edit' => true, 'createpage' => false ),
				true,
				true,
			),
			array( #3: edit not allowed for existing item
				'user',
				array( 'read' => true, 'edit' => false ),
				true,
				false,
			),
		);
	}

	protected function prepareItemForPermissionCheck( $group, $permissions, $create ) {
		global $wgUser;

		$content = ItemContent::newEmpty();

		if ( $create ) {
			$content->getItem()->setLabel( 'de', 'Test' );
			$content->save( "testing" );
		}

		if ( !in_array( $group, $wgUser->getEffectiveGroups() ) ) {
			$wgUser->addGroup( $group );
		}

		if ( $permissions !== null ) {
			ApiModifyItemBase::applyPermissions( array(
				'*' => $permissions,
				'user' => $permissions,
				$group => $permissions,
			) );
		}

		return $content;
	}

	/**
	 * @dataProvider dataCheckEditPermissions
	 */
	public function testCheckEditPermissions( $group, $permissions, $create, $expectedOK ) {
		$content = $this->prepareItemForPermissionCheck( $group, $permissions, $create );
		$content->getItem()->setLabel( 'xx', 'Foo' );

		$edit = new EditEntity( $content );

		try {
			$edit->checkEditPermissions();

			$this->assertTrue( $expectedOK, 'this permission check was expected to fail!' );
		} catch ( \PermissionsError $ex ) {
			$this->assertFalse( $expectedOK, 'this permission check was expected to pass! '
				. $ex->permission . ': ' . var_export( $ex->errors, true ) );
		}

		$this->assertEquals( $expectedOK, $edit->getStatus()->isOK() );
		$this->assertNotEquals( $expectedOK, $edit->hasError( EditEntity::PERMISSION_ERROR ) );
		$this->assertNotEquals( $expectedOK, $edit->showErrorPage() );
	}

	/**
	 * @dataProvider dataCheckEditPermissions
	 */
	public function testAttemptSavePermissions( $group, $permissions, $create, $expectedOK ) {
		$content = $this->prepareItemForPermissionCheck( $group, $permissions, $create );
		$content->getItem()->setLabel( 'xx', 'Foo' );

		$edit = new EditEntity( $content );

		try {
			$edit->attemptSave( "testing" );

			$this->assertTrue( $expectedOK, 'this permission check was expected to fail!' );
		} catch ( \PermissionsError $ex ) {
			$this->assertFalse( $expectedOK, 'this permission check was expected to pass! '
				. $ex->permission . ': ' . var_export( $ex->errors, true )  );
		}

		$this->assertEquals( $expectedOK, $edit->getStatus()->isOK() );
		$this->assertNotEquals( $expectedOK, $edit->hasError( EditEntity::PERMISSION_ERROR ) );
		$this->assertNotEquals( $expectedOK, $edit->showErrorPage() );
	}

	public function provideIsTokenOk() {
		return array(
			array( #0
				true, # use a newly generated valid token
				true, # should work
			),
			array( #1
				"xyz", # use an invalid token
				false, # should fail
			),
			array( #2
				"", # use an empty token
				false, # should fail
			),
			array( #3
				null, # use no token
				false, # should fail
			),
		);
	}

	/**
	 * @dataProvider provideIsTokenOk
	 */
	public function testIsTokenOk( $token, $shouldWork ) {
		global $wgUser;

		$content = ItemContent::newEmpty();
		$edit = new EditEntity( $content );

		// check valid token --------------------
		if ( $token === true ) {
			$token = $wgUser->getEditToken();
		}

		$this->assertEquals( $shouldWork, $edit->isTokenOK( $token ) );

		$this->assertEquals( $shouldWork, $edit->getStatus()->isOK() );
		$this->assertNotEquals( $shouldWork, $edit->hasError( EditEntity::TOKEN_ERROR ) );
		$this->assertNotEquals( $shouldWork, $edit->showErrorPage() );
	}
}