<?php

namespace Wikibase\Test;
use Wikibase\EntityContent;
use Wikibase\Test\Api\ModifyItemBase;

/**
 * Tests for the Wikibase\EntityContent class.
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
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Daniel Kinzler
 */
abstract class EntityContentTest extends \MediaWikiTestCase {

	function setUp() {
		global $wgGroupPermissions, $wgUser;

		parent::setUp();
		$this->setMwGlobals(
			array(
				'wgGroupPermissions' => $wgGroupPermissions,
				'wgUser' => $wgUser
			)
		);

		\TestSites::insertIntoDb();
	}

	public function dataGetTextForSearchIndex() {
		return array( // runs
			array( // //0
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
	 * @since 0.1
	 *
	 * @return string
	 */
	protected abstract function getContentClass();

	/**
	 * @since 0.1
	 *
	 * @param array $data
	 *
	 * @return EntityContent
	 */
	protected function newFromArray( array $data ) {
		$class = $this->getContentClass();
		return $class::newFromArray( $data );
	}

	/**
	 * @since 0.1
	 *
	 * @return EntityContent
	 */
	protected function newEmpty() {
		$class = $this->getContentClass();
		return $class::newEmpty();
	}

	/**
	 * Tests @see Wikibase\Entity::getTextForSearchIndex
	 *
	 * @dataProvider dataGetTextForSearchIndex
	 *
	 * @param array $data
	 * @param array $patterns
	 */
	public function testGetTextForSearchIndex( array $data, array $patterns ) {
		$entity = $this->newFromArray( $data );
		$text = $entity->getTextForSearchIndex();

		foreach ( $patterns as $pattern ) {
			$this->assertRegExp( $pattern . 'm', $text );
		}
	}

	public function testSaveFlags() {
		\Wikibase\StoreFactory::getStore()->getTermIndex()->clear();

		$entityContent = $this->newEmpty();

		// try to create without flags
		$entityContent->getEntity()->setLabel( 'en', 'one' );
		$status = $entityContent->save( 'create item' );
		$this->assertFalse( $status->isOK(), "save should have failed" );
		$this->assertTrue( $status->hasMessage( 'edit-gone-missing' ) );

		// try to create with EDIT_UPDATE flag
		$entityContent->getEntity()->setLabel( 'en', 'two' );
		$status = $entityContent->save( 'create item', null, EDIT_UPDATE );
		$this->assertFalse( $status->isOK(), "save should have failed" );
		$this->assertTrue( $status->hasMessage( 'edit-gone-missing' ) );

		// try to create with EDIT_NEW flag
		$entityContent->getEntity()->setLabel( 'en', 'three' );
		$status = $entityContent->save( 'create item', null, EDIT_NEW );
		$this->assertTrue( $status->isOK(), $entityContent->getEntity()->getId()->getPrefixedId() );

		// ok, the item exists now in the database.

		// try to save with EDIT_NEW flag
		$entityContent->getEntity()->setLabel( 'en', 'four' );
		$status = $entityContent->save( 'create item', null, EDIT_NEW );
		$this->assertFalse( $status->isOK(), "save should have failed" );
		$this->assertTrue( $status->hasMessage( 'edit-already-exists' ) );

		// try to save with EDIT_UPDATE flag
		$entityContent->getEntity()->setLabel( 'en', 'five' );
		$status = $entityContent->save( 'create item', null, EDIT_UPDATE );
		$this->assertTrue( $status->isOK(), "save failed" );

		// try to save without flags
		$entityContent->getEntity()->setLabel( 'en', 'six' );
		$status = $entityContent->save( 'create item' );
		$this->assertTrue( $status->isOK(), "save failed" );
	}

	public function testRepeatedSave() {
		\Wikibase\StoreFactory::getStore()->getTermIndex()->clear();

		$entityContent = $this->newEmpty();

		// create
		$entityContent->getEntity()->setLabel( 'en', "First" );
		$status = $entityContent->save( 'create item', null, EDIT_NEW );
		$this->assertTrue( $status->isOK(), "save failed" );
		$this->assertTrue( $status->isGood(), $status->getMessage() );

		// change
		$prev_id = $entityContent->getWikiPage()->getLatest();
		$entityContent->getEntity()->setLabel( 'en', "Second" );
		$status = $entityContent->save( 'modify item', null, EDIT_UPDATE );
		$this->assertTrue( $status->isOK(), "save failed" );
		$this->assertTrue( $status->isGood(), $status->getMessage() );
		$this->assertNotEquals( $prev_id, $entityContent->getWikiPage()->getLatest(), "revision ID should change on edit" );

		// change again
		$prev_id = $entityContent->getWikiPage()->getLatest();
		$entityContent->getEntity()->setLabel( 'en', "Third" );
		$status = $entityContent->save( 'modify item again', null, EDIT_UPDATE );
		$this->assertTrue( $status->isOK(), "save failed" );
		$this->assertTrue( $status->isGood(), $status->getMessage() );
		$this->assertNotEquals( $prev_id, $entityContent->getWikiPage()->getLatest(), "revision ID should change on edit" );

		// save unchanged
		$prev_id = $entityContent->getWikiPage()->getLatest();
		$status = $entityContent->save( 'save unmodified', null, EDIT_UPDATE );
		$this->assertTrue( $status->isOK(), "save failed" );
		$this->assertEquals( $prev_id, $entityContent->getWikiPage()->getLatest(), "revision ID should stay the same if no change was made" );
	}

	public function dataCheckPermissions() {
		// FIXME: this is testing for some specific configuration and will break if the config is changed
		return array(
			array( //0: read allowed
				'read',
				'user',
				array( 'read' => true ),
				false,
				true,
			),
			array( //1: edit and createpage allowed for new item
				'edit',
				'user',
				array( 'read' => true, 'edit' => true, 'createpage' => true ),
				false,
				true,
			),
			array( //2: edit allowed but createpage not allowed for new item
				'edit',
				'user',
				array( 'read' => true, 'edit' => true, 'createpage' => false ),
				false,
				false,
			),
			array( //3: edit allowed but createpage not allowed for existing item
				'edit',
				'user',
				array( 'read' => true, 'edit' => true, 'createpage' => false ),
				true,
				true,
			),
			array( //4: edit not allowed for existing item
				'edit',
				'user',
				array( 'read' => true, 'edit' => false ),
				true,
				false,
			),
			array( //5: delete not allowed
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

		// TODO: Figure out what is leaking the sysop group membership
		$wgUser->removeGroup('sysop');

		$content = $this->newEmpty();

		if ( $create ) {
			$content->getEntity()->setLabel( 'de', 'Test' );
			$content->save( "testing", null, EDIT_NEW );
		}

		if ( !in_array( $group, $wgUser->getEffectiveGroups() ) ) {
			$wgUser->addGroup( $group );
		}

		if ( $permissions !== null ) {
			ModifyItemBase::applyPermissions( array(
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

		$content->checkPermission( $action );

		$this->assertEquals( $expectedOK, $content->userCan( $action ) );
	}


	public function dataUserCanEdit() {
		return array(
			array( //0: edit and createpage allowed for new item
				array( 'read' => true, 'edit' => true, 'createpage' => true ),
				false,
				true,
			),
			array( //1: edit allowed but createpage not allowed for new item
				array( 'read' => true, 'edit' => true, 'createpage' => false ),
				false,
				false,
			),
			array( //2: edit allowed but createpage not allowed for existing item
				array( 'read' => true, 'edit' => true, 'createpage' => false ),
				true,
				true,
			),
			array( //3: edit not allowed for existing item
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

	public static function provideEquals() {
		return array(
			array( #0
				array(),
				array(),
				true
			),
			array( #1
				array( 'labels' => array() ),
				array( 'descriptions' => null ),
				true
			),
			array( #2
				array( 'entity' => 'q23' ),
				array(),
				true
			),
			array( #3
				array( 'entity' => 'q23' ),
				array( 'entity' => 'q24' ),
				false
			),
			array( #4
				array( 'labels' => array(
					'en' => 'foo',
					'de' => 'bar',
				) ),
				array( 'labels' => array(
					'en' => 'foo',
				) ),
				false
			),
			array( #5
				array( 'labels' => array(
					'en' => 'foo',
					'de' => 'bar',
				) ),
				array( 'labels' => array(
					'de' => 'bar',
					'en' => 'foo',
				) ),
				true
			),
			array( #6
				array( 'aliases' => array(
					'en' => array( 'foo', 'FOO' ),
				) ),
				array( 'aliases' => array(
					'en' => array( 'foo', 'FOO', 'xyz' ),
				) ),
				false
			),
		);
	}

	/**
	 * @dataProvider provideEquals
	 */
	public function testEquals( array $a, array $b, $equals ) {
		$itemA = $this->newFromArray( $a );
		$itemB = $this->newFromArray( $b );

		$actual = $itemA->equals( $itemB );
		$this->assertEquals( $equals, $actual );

		$actual = $itemB->equals( $itemA );
		$this->assertEquals( $equals, $actual );
	}
}
