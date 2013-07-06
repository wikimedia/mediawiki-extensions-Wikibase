<?php

namespace Wikibase\Test;

use \Wikibase\EntityContent;
use \Wikibase\EditEntity;
use \Wikibase\ItemContent;
use \Wikibase\Item;
use \Status;
use Wikibase\Test\Api\ModifyEntityBase;

/**
 * Test EditEntity.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @since 0.1
 *
 * @ingroup WikibaseRepoTest
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
 * Some of the tests takes more time, and needs therefor longer time before they can be aborted
 * as non-functional. The reason why tests are aborted is assumed to be set up of temporal databases
 * that hold the first tests in a pending state awaiting access to the database.
 * @group medium
 *
 * @licence GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 *
 */
class EditEntityTest extends \MediaWikiTestCase {

	private static $testRevisions = null;

	protected static function getUser( $name ) {
		$user = \User::newFromName( $name );

		if ( $user->getId() === 0 ) {
			$user = \User::createNew( $user->getName() );
		}

		return $user;
	}

	protected static function getTestRevisions() {
		global $wgUser;

		if ( self::$testRevisions === null ) {
			$otherUser = self::getUser( "EditEntityTestUser2" );

			$itemContent = ItemContent::newEmpty();
			$itemContent->getEntity()->setLabel('en', "foo");
			$itemContent->save( "rev 0", $wgUser, EDIT_NEW );
			self::$testRevisions[] = $itemContent->getWikiPage()->getRevision();

			$itemContent = $itemContent->copy();
			$itemContent->getEntity()->setLabel('en', "bar");
			$itemContent->save( "rev 1", $otherUser, EDIT_UPDATE );
			self::$testRevisions[] = $itemContent->getWikiPage()->getRevision();

			$itemContent = $itemContent->copy();
			$itemContent->getEntity()->setLabel('de', "bar");
			$itemContent->save( "rev 2", $wgUser, EDIT_UPDATE );
			self::$testRevisions[] = $itemContent->getWikiPage()->getRevision();

			$itemContent = $itemContent->copy();
			$itemContent->getEntity()->setLabel('en', "test");
			$itemContent->getEntity()->setDescription('en', "more testing");
			$itemContent->save( "rev 3", $wgUser, EDIT_UPDATE );
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

	public static function provideHasEditConflict() {
		/*
		 * Test Revisions:
		 * #0: label: array( 'en' => 'foo' );
		 * #1: label: array( 'en' => 'bar' ); // by other user
		 * #2: label: array( 'en' => 'bar', 'de' => 'bar' );
		 * #3: label: array( 'en' => 'test', 'de' => 'bar' ), description: array( 'en' => 'more testing' );
		*/

		return array(
			array( // #0: case I: no base rev given.
				null,  // input data
				null,  // base rev index
				false, // expected conflict
				false, // expected fix
			),
			array( // #1: case II: base rev == current
				null,  // input data
				3,     // base rev index
				false, // expected conflict
				false, // expected fix
			),
			array( // #2: case IIIa: user was last to edit
				array( // input data
					'label' => array( 'de' => 'yarrr' ),
				),
				2,     // base rev index
				true,  // expected conflict
				true,  // expected fix
				array( // expected data
					'label' => array( 'en' => 'test', 'de' => 'yarrr' ),
				)
			),
			array( // #3: case IIIb: user was last to edit, but intoduces a new operand
				array( // input data
					'label' => array( 'de' => 'yarrr' ),
				),
				1,     // base rev index
				true,  // expected conflict
				false, // expected failure, diff operand change
				null
			),
			array( // #4: case IV: patch applied
				array( // input data
					'label' => array( 'nl' => 'test', 'fr' => 'frrrrtt' ),
				),
				0,     // base rev index
				true,  // expected conflict
				true,  // expected fix
				array( // expected data
					'label' => array( 'de' => 'bar', 'en' => 'test',
					                  'nl' => 'test', 'fr' => 'frrrrtt' ),
				)
			),
			array( // #5: case V: patch failed, expect a conflict
				array( // input data
					'label' => array( 'nl' => 'test', 'de' => 'bar' ),
				),
				0,     // base rev index
				true,  // expected conflict
				false, // expected fix
				null   // expected data
			),
		);
	}

	/**
	 * @dataProvider provideHasEditConflict
	 */
	public function testHasEditConflict( $inputData, $baseRevisionIdx, $expectedConflict, $expectedFix, array $expectedData = null ) {
		global $wgUser;

		/* @var $content \Wikibase\EntityContent */
		/* @var $revision \Revision */

		static $user = null;
		if ( !$user ) {
			$user = \User::newFromId( 0 );
			$user->setName( '127.0.0.1' );
		}
		$this->setMwGlobals( 'wgUser', $user );

		$revisions = self::getTestRevisions();

		$baseRevisionId = is_int( $baseRevisionIdx ) ? $revisions[$baseRevisionIdx]->getId() : null;
		$revision = is_int( $baseRevisionIdx ) ? $revisions[$baseRevisionIdx] : $revisions[ count( $revisions ) -1 ];
		$content = $revision->getContent();
		$entity = $content->getEntity();

		// change entity ----------------------------------
		if ( $inputData === null ) {
			$entity->clear();
		} else {
			if ( !empty( $inputData['label'] ) ) {
				foreach ( $inputData['label'] as $k => $v ) {
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

		if ( $conflict ) {
			$fixed = $editEntity->fixEditConflict();
			$this->assertEquals( $expectedFix, $fixed, 'fixEditConflict()' );
		}

		/*
		 * //TODO: make EditEntity report errors without saving content!
		$expectedFailure = ( $expectedConflict && !$expectedFix );

		$this->assertEquals( $expectedFailure, $editEntity->hasError( EditEntity::EDIT_CONFLICT_ERROR ), 'hasError()' );
		$this->assertEquals( $expectedFailure, $editEntity->showErrorPage(), 'showErrorPage' );
		$this->assertNotEquals( $expectedFailure, $editEntity->getStatus()->isOK(), 'isOK()' );
		*/

		if ( $expectedData !== null ) {
			$data = $editEntity->getNewContent()->getEntity()->toArray();

			foreach ( $expectedData as $key => $expectedValue ) {
				$actualValue = $data[$key];
				$this->assertArrayEquals( $expectedValue, $actualValue, false, true );
			}
		}
	}

	public static function provideAttemptSaveWithLateConflict() {
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
		$content->save( "rev 0", $wgUser, EDIT_NEW );


		// begin editing the entity
		$content->getEntity()->setLabel( 'en', 'Trust' );

		$editEntity = new EditEntity( $content, $wgUser, $baseRevId );
		$editEntity->getCurrentRevision(); // make sure EditEntity has page and revision

		$this->assertEquals( $baseRevId, $editEntity->doesCheckForEditConflicts(), 'doesCheckForEditConflicts()' );

		// create independent EntityContent instance for the same entity, and modify and save it
		$page = \WikiPage::factory( $content->getTitle() );
		$content2 = $page->getContent();
		$content2->getEntity()->setLabel( 'en', 'Toast' );
		$content2->save( 'Trolololo!', null, EDIT_UPDATE );

		// now try to save the original edit. The conflict should still be detected
		$token = $wgUser->getEditToken();
		$status = $editEntity->attemptSave( "Testing", EDIT_UPDATE, $token );

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
			$content->save( "testing", null, EDIT_NEW );
		}

		if ( !in_array( $group, $wgUser->getEffectiveGroups() ) ) {
			$wgUser->addGroup( $group );
		}

		if ( $permissions !== null ) {
			ModifyEntityBase::applyPermissions( array(
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

		$edit->checkEditPermissions();

		$this->assertEquals( $expectedOK, $edit->getStatus()->isOK() );
		$this->assertNotEquals( $expectedOK, $edit->hasError( EditEntity::PERMISSION_ERROR ) );
	}

	/**
	 * @dataProvider dataCheckEditPermissions
	 */
	public function testAttemptSavePermissions( $group, $permissions, $create, $expectedOK ) {
		global $wgUser;

		$content = $this->prepareItemForPermissionCheck( $group, $permissions, $create );
		$content->getItem()->setLabel( 'xx', 'Foo' );

		$token = $wgUser->getEditToken();

		$edit = new EditEntity( $content );

		$edit->attemptSave( "testing", ( $content->isNew() ? EDIT_NEW : 0 ), $token );

		$this->assertEquals( $expectedOK, $edit->getStatus()->isOK(), var_export( $edit->getStatus()->getErrorsArray(), true ) );
		$this->assertNotEquals( $expectedOK, $edit->hasError( EditEntity::PERMISSION_ERROR ) );
	}

	/**
	 * Forces the group membership of the given user
	 *
	 * @param \User  $user
	 * @param array $groups
	 */
	protected function setUserGroups( \User $user, array $groups ) {
		if ( $user->getId() === 0 ) {
			$user = \User::createNew( $user->getName() );
		}

		$remove = array_diff( $user->getGroups(), $groups );
		$add = array_diff( $groups, $user->getGroups() );

		foreach ( $remove as $group ) {
			$user->removeGroup( $group );
		}

		foreach ( $add as $group ) {
			$user->addGroup( $group );
		}
	}

	public static function dataAttemptSaveRateLimit() {
		return array(

			array( // #0: no limits
				array(), // limits: none
				array(), // groups: none
				array(  // edits:
					array( 'item' => 'foo', 'label' => 'foo', 'ok' => true ),
					array( 'item' => 'bar', 'label' => 'bar', 'ok' => true ),
					array( 'item' => 'foo', 'label' => 'Foo', 'ok' => true ),
					array( 'item' => 'bar', 'label' => 'Bar', 'ok' => true ),
				)
			),

			array( // #1: limits bypassed with noratelimit permission
				array( // limits:
					'edit' => array(
						'user' => array( 1, 60 ), // one edit per minute
					)
				),
				array( // groups:
					'sysop' // assume sysop has the noratelimit permission, as per default
				),
				array(  // edits:
					array( 'item' => 'foo', 'label' => 'foo', 'ok' => true ),
					array( 'item' => 'bar', 'label' => 'bar', 'ok' => true ),
					array( 'item' => 'foo', 'label' => 'Foo', 'ok' => true ),
					array( 'item' => 'bar', 'label' => 'Bar', 'ok' => true ),
				)
			),

			array( // #2: per-group limit overrides with less restrictive limit
				array( // limits:
					'edit' => array(
						'user' => array( 1, 60 ), // one edit per minute
						'kittens' => array( 10, 60 ), // one edit per minute
					)
				),
				array( // groups:
					'kittens'
				),
				array(  // edits:
					array( 'item' => 'foo', 'label' => 'foo', 'ok' => true ),
					array( 'item' => 'bar', 'label' => 'bar', 'ok' => true ),
					array( 'item' => 'foo', 'label' => 'Foo', 'ok' => true ),
					array( 'item' => 'bar', 'label' => 'Bar', 'ok' => true ),
				)
			),

			array( // #3: edit limit applies
				array( // limits:
					'edit' => array(
						'user' => array( 1, 60 ), // one edit per minute
					),
				),
				array(), // groups: none
				array(  // edits:
					array( 'item' => 'foo', 'label' => 'foo', 'ok' => true ),
					array( 'item' => 'foo', 'label' => 'Foo', 'ok' => false ),
				)
			),

			array( // #4: edit limit also applies to creations
				array( // limits:
					'edit' => array(
						'user' => array( 1, 60 ), // one edit per minute
					),
					'create' => array(
						'user' => array( 10, 60 ), // ten creations per minute
					),
				),
				array(), // groups: none
				array(  // edits:
					array( 'item' => 'foo', 'label' => 'foo', 'ok' => true ),
					array( 'item' => 'bar', 'label' => 'bar', 'ok' => false ),
					array( 'item' => 'foo', 'label' => 'Foo', 'ok' => false ),
				)
			),

			array( // #5: creation limit applies in addition to edit limit
				array( // limits:
					'edit' => array(
						'user' => array( 10, 60 ), // ten edits per minute
					),
					'create' => array(
						'user' => array( 1, 60 ), // ...but only one creation
					),
				),
				array(), // groups: none
				array(  // edits:
					array( 'item' => 'foo', 'label' => 'foo', 'ok' => true ),
					array( 'item' => 'foo', 'label' => 'Foo', 'ok' => true ),
					array( 'item' => 'bar', 'label' => 'bar', 'ok' => false ),
				)
			)

		);
	}

	/**
	 * @dataProvider dataAttemptSaveRateLimit
	 */
	public function testAttemptSaveRateLimit( $limits, $groups, $edits ) {
		$this->setMwGlobals(
			'wgRateLimits',
			$limits
		);

		// make sure we have a fresh, working cache
		$this->setMwGlobals(
			'wgMemc',
			new \HashBagOStuff()
		);

		$user = \User::newFromName( "UserForTestAttemptSaveRateLimit" );
		$this->setUserGroups( $user, $groups );

		$items = array();

		foreach ( $edits as $e ) {
			$name = $e[ 'item' ];
			$label = $e[ 'label' ];
			$expectedOK = $e[ 'ok' ];

			if ( isset( $items[$name] ) ) {
				// re-use item
				$item = $items[$name];
			} else {
				// create item
				$item = Item::newEmpty();
				$items[$name] = $item;
			}

			$item->setLabel( 'en', $label );

			$content = ItemContent::newFromItem( $item );

			$edit = new EditEntity( $content, $user );
			$edit->attemptSave( "testing", ( $content->isNew() ? EDIT_NEW : 0 ), false );

			$this->assertEquals( $expectedOK, $edit->getStatus()->isOK(), var_export( $edit->getStatus()->getErrorsArray(), true ) );
			$this->assertNotEquals( $expectedOK, $edit->hasError( EditEntity::RATE_LIMIT ) );
		}
	}

	public static function provideIsTokenOk() {
		return array(
			array( //0
				true, // use a newly generated valid token
				true, // should work
			),
			array( //1
				"xyz", // use an invalid token
				false, // should fail
			),
			array( //2
				"", // use an empty token
				false, // should fail
			),
			array( //3
				null, // use no token
				false, // should fail
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

	public static function provideGetWatchDefault() {
		// $watchdefault, $watchcreations, $new, $watched, $expected

		return array(
			array( false, false, false, false, false ),
			array( false, false, false, true,  true ),
			array( false, false, true,  false, false ),
			//array( false, false, true,  true,  true ), // can't happen, a new pages is never watched

			array( false, true,  false, false, false ),
			array( false, true,  false, true,  true ),
			array( false, true,  true,  false, true ),
			//array( false, true,  true,  true,  true ), // can't happen, a new pages is never watched

			array( true,  false, false, false, true ),
			array( true,  false, false, true,  true ),
			array( true,  false, true,  false, true ),
			//array( true,  false, true,  true,  true ), // can't happen, a new pages is never watched

			array( true,  true,  false, false, true ),
			array( true,  true,  false, true,  true ),
			array( true,  true,  true,  false, true ),
			//array( true,  true,  true,  true,  true ), // can't happen, a new pages is never watched
		);
	}

	/**
	 * @dataProvider provideGetWatchDefault
	 */
	public function testGetWatchDefault( $watchdefault, $watchcreations, $new, $watched, $expected ) {
		$user = self::getUser( "EditEntityTestUser2" );

		$user->setOption( 'watchdefault', $watchdefault );
		$user->setOption( 'watchcreations', $watchcreations );

		$item = Item::newEmpty();
		$item->setLabel( "en", "Test" );

		if ( $new ) {
			$item->setId( 33224477 );
			$content = ItemContent::newFromItem( $item );
		} else {
			$content = ItemContent::newFromItem( $item );
			$stats = $content->save( "testing", null, EDIT_NEW );
			$this->assertTrue( $stats->isOK(), "failed to save" ); // sanity
		}

		$title = $content->getTitle();
		$this->assertType( 'object', $title ); // sanity

		if ( $watched ) {
			\WatchAction::doWatch( $title, $user );
		} else {
			\WatchAction::doUnwatch( $title, $user );
		}

		$edit = new EditEntity( $content, $user );
		$watch = $edit->getWatchDefault();
		$this->assertEquals( $expected, $watch, "getWatchDefault" );

		if ( $title && $title->exists() ) {
			// clean up
			$page = \WikiPage::factory( $title );
			$page->doDeleteArticle( "testing" );
		}
	}

	public static function provideUpdateWatchlist() {
		// $wasWatched, $watch, $expected

		return array(
			array( false, false, false ),
			array( false, true,  true ),
			array( true,  false, false ),
			array( true,  true,  true ),
		);
	}

	/**
	 * @dataProvider provideUpdateWatchlist
	 */
	public function testUpdateWatchlist( $wasWatched, $watch, $expected ) {
		$user = self::getUser( "EditEntityTestUser2" );

		$content = ItemContent::newEmpty();
		$content->getEntity()->setLabel( "en", "Test" );
		$content->getEntity()->setId( 33224477 );

		$title = $content->getTitle();

		if ( $wasWatched ) {
			\WatchAction::doWatch( $title, $user );
		} else {
			\WatchAction::doUnwatch( $title, $user );
		}

		$edit = new EditEntity( $content, $user );
		$edit->updateWatchlist( $watch );

		$this->assertEquals( $expected, $user->isWatched( $title ) );
	}


	public static function provideAttemptSaveWatch() {
		// $watchdefault, $watchcreations, $new, $watched, $watch, $expected

		return array(
			array( true, true, true, false, null, true ), // watch new
			array( true, true, true, false, false, false ), // override watch new

			array( true, true, false, false, null, true ), // watch edit
			array( true, true, false, false, false, false ), // override watch edit

			array( false, false, false, false, null, false ), // don't watch edit
			array( false, false, false, false, true, true ), // override don't watch edit

			array( false, false, false, true, null, true ), // watch watched
			array( false, false, false, true, false, false ), // override don't watch edit
		);
	}

	/**
	 * @dataProvider provideAttemptSaveWatch
	 */
	public function testAttemptSaveWatch( $watchdefault, $watchcreations, $new, $watched, $watch, $expected ) {
		$user = self::getUser( "EditEntityTestUser2" );

		$user->setOption( 'watchdefault', $watchdefault );
		$user->setOption( 'watchcreations', $watchcreations );

		$item = Item::newEmpty();
		$item->setLabel( "en", "Test" );

		if ( $new ) {
			$content = ItemContent::newFromItem( $item );
		} else {
			$content = ItemContent::newFromItem( $item );
			$stats = $content->save( "testing", null, EDIT_NEW );
			$this->assertTrue( $stats->isOK(), "failed to save" ); // sanity
		}

		if ( !$new ) {
			$title = $content->getTitle();
			$this->assertType( 'object', $title ); // sanity

			if ( $watched ) {
				\WatchAction::doWatch( $title, $user );
			} else {
				\WatchAction::doUnwatch( $title, $user );
			}
		}

		$edit = new EditEntity( $content, $user );
		$status = $edit->attemptSave( "testing", $new ? EDIT_NEW : EDIT_UPDATE, false, $watch );

		$this->assertTrue( $status->isOK(), "edit failed: " . $status->getWikiText() ); // sanity

		$title = $content->getTitle();
		$this->assertEquals( $expected, $user->isWatched( $title ), "watched" );

		if ( $title && $title->exists() ) {
			// clean up
			$page = \WikiPage::factory( $title );
			$page->doDeleteArticle( "testing" );
		}
	}
}
