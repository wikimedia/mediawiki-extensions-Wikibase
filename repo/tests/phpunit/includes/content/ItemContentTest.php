<?php

namespace Wikibase\Test;
use \Wikibase\ItemContent as ItemContent;
use \Wikibase\Item as Item;

/**
 * Tests for the Wikibase\ItemContent class.
 *
 * @file
 * @since 0.1
 *
 * @ingroup Wikibase
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseItem
 * @group WikibaseRepo
 *
 * @group Database
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Daniel Kinzler
 */
class ItemContentTest extends \MediaWikiTestCase {

	protected $permissions;
	protected $userGroups;

	function setUp() {
		global $wgGroupPermissions, $wgUser;

		parent::setUp();

		$this->permissions = $wgGroupPermissions;
		$this->userGroups = $wgUser->getGroups();

		\TestSites::insertIntoDb();
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

	public function dataGetTextForSearchIndex() {
		return array( // runs
			array( // #0
				array( // data
					'label' => array( 'en' => 'Test', 'de' => 'Testen' ),
					'aliases' => array( 'en' => array( 'abc', 'cde' ), 'de' => array( 'xyz', 'uvw' ) )
				),
				array( // patterns
					'/^Test$/',
					'/^Testen$/',
					'/^abc$/',
					'/^cde$/',
					'/^uvw$/',
					'/^xyz$/',
					'/^(?!abcde).*$/',
				),
			),
		);
	}

	/**
	 * Tests @see WikibaseItem::getTextForSearchIndex
	 *
	 * @dataProvider dataGetTextForSearchIndex
	 *
	 * @param array $data
	 * @param array $patterns
	 */
	public function testGetTextForSearchIndex( array $data, array $patterns ) {
		$item = ItemContent::newFromArray( $data );
		$text = $item->getTextForSearchIndex();

		foreach ( $patterns as $pattern ) {
			$this->assertRegExp( $pattern . 'm', $text );
		}
	}

	public function testRepeatedSave() {
		$itemContent = \Wikibase\ItemContent::newEmpty();

		// create
		$itemContent->getItem()->setLabel( 'en', "First" );
		$status = $itemContent->save( 'create item', null, EDIT_NEW );
		$this->assertTrue( $status->isOK(), "save failed" );
		$this->assertTrue( $status->isGood(), $status->getMessage() );

		// change
		$prev_id = $itemContent->getWikiPage()->getLatest();
		$itemContent->getItem()->setLabel( 'en', "Second" );
		$status = $itemContent->save( 'modify item', null, EDIT_UPDATE );
		$this->assertTrue( $status->isOK(), "save failed" );
		$this->assertTrue( $status->isGood(), $status->getMessage() );
		$this->assertNotEquals( $prev_id, $itemContent->getWikiPage()->getLatest(), "revision ID should change on edit" );

		// change again
		$prev_id = $itemContent->getWikiPage()->getLatest();
		$itemContent->getItem()->setLabel( 'en', "Third" );
		$status = $itemContent->save( 'modify item again', null, EDIT_UPDATE );
		$this->assertTrue( $status->isOK(), "save failed" );
		$this->assertTrue( $status->isGood(), $status->getMessage() );
		$this->assertNotEquals( $prev_id, $itemContent->getWikiPage()->getLatest(), "revision ID should change on edit" );

		// save unchanged
		$prev_id = $itemContent->getWikiPage()->getLatest();
		$status = $itemContent->save( 'save unmodified', null, EDIT_UPDATE );
		$this->assertTrue( $status->isOK(), "save failed" );
		$this->assertEquals( $prev_id, $itemContent->getWikiPage()->getLatest(), "revision ID should stay the same if no change was made" );
	}

	public function dataCheckPermissions() {
		return array(
			array( #0: read allowed
				'read',
				'user',
				array( 'read' => true ),
				false,
				true,
			),
			array( #1: edit and createpage allowed for new item
				'edit',
				'user',
				array( 'read' => true, 'edit' => true, 'createpage' => true ),
				false,
				true,
			),
			array( #2: edit allowed but createpage not allowed for new item
				'edit',
				'user',
				array( 'read' => true, 'edit' => true, 'createpage' => false ),
				false,
				false,
			),
			array( #3: edit allowed but createpage not allowed for existing item
				'edit',
				'user',
				array( 'read' => true, 'edit' => true, 'createpage' => false ),
				true,
				true,
			),
			array( #4: edit not allowed for existing item
				'edit',
				'user',
				array( 'read' => true, 'edit' => false ),
				true,
				false,
			),
			array( #5: delete not allowed
				'delete',
				'user',
				array( 'read' => true, 'delete' => false ),
				false,
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
	 * @dataProvider dataCheckPermissions
	 */
	public function testCheckPermission( $action, $group, $permissions, $create, $expectedOK ) {
		$content = $this->prepareItemForPermissionCheck( $group, $permissions, $create );

		$status = $content->checkPermission( $action );

		$this->assertEquals( $expectedOK, $status->isOK() );
	}

	/**
	 * @dataProvider dataCheckPermissions
	 */
	public function testUserCan( $action, $group, $permissions, $create, $expectedOK ) {
		$content = $this->prepareItemForPermissionCheck( $group, $permissions, $create );

		$status = $content->checkPermission( $action );

		$this->assertEquals( $expectedOK, $content->userCan( $action ) );
	}


	public function dataUserCanEdit() {
		return array(
			array( #0: edit and createpage allowed for new item
				array( 'read' => true, 'edit' => true, 'createpage' => true ),
				false,
				true,
			),
			array( #1: edit allowed but createpage not allowed for new item
				array( 'read' => true, 'edit' => true, 'createpage' => false ),
				false,
				false,
			),
			array( #2: edit allowed but createpage not allowed for existing item
				array( 'read' => true, 'edit' => true, 'createpage' => false ),
				true,
				true,
			),
			array( #3: edit not allowed for existing item
				array( 'read' => true, 'edit' => false ),
				true,
				false,
			),
		);
	}

	/**
	 * @dataProvider dataUserCanEdit
	 */
	public function testUserCanEdit( $permissions, $create, $expectedOK ) {
		$content = $this->prepareItemForPermissionCheck( 'user', $permissions, $create );

		$this->assertEquals( $expectedOK, $content->userCanEdit() );
	}
}
	