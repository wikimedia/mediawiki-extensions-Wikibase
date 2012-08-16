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
		return array(
			array( ItemContent::newEmpty(), false, false, true ),
			array( ItemContent::newEmpty(), 9999999, false, false ),
			array( ItemContent::newEmpty(), false, 9999999, false ),
			array( ItemContent::newEmpty(), 9999999, 9999999, false ),
			array(
				ItemContent::newFromArray( array(
					'label' => array( 'en' => 'test' ),
					'description' => array( 'en' => 'more testing' ),
					'aliases' => array(),
					'links' => array(),
					'aliases' => array()
				) ),
				0,
				1,
				true,
				array(
					'label' => array( 'en' => 'bar' ),
					'description' => array( 'en' => 'more testing' ),
					'aliases' => array(),
					'links' => array(),
					'aliases' => array()
				)
			),
			array(
				ItemContent::newFromArray( array(
					'label' => array( 'de' => 'test' ),
					'description' => array( 'en' => 'more testing' ),
					'aliases' => array(),
					'links' => array(),
					'aliases' => array()
				) ),
				0,
				1,
				true,
				array(
					'label' => array( 'en' => 'bar', 'de' => 'test' ),
					'description' => array( 'en' => 'more testing' ),
					'aliases' => array(),
					'links' => array(),
					'aliases' => array()
				)
			),
			array(
				ItemContent::newFromArray( array(
					'label' => array( 'en' => 'test', 'de' => 'test' ),
					'description' => array( 'en' => 'more testing' ),
					'aliases' => array(),
					'links' => array(),
					'aliases' => array()
				) ),
				0,
				1,
				true,
				array(
					'label' => array( 'en' => 'bar', 'de' => 'test' ),
					'description' => array( 'en' => 'more testing' ),
					'aliases' => array(),
					'links' => array(),
					'aliases' => array()
				)
			),
			array(
				ItemContent::newFromArray( array(
					'label' => array( 'de' => 'test', 'de' => 'bar' ),
					'description' => array( 'en' => 'more testing' ),
					'aliases' => array(),
					'links' => array(),
					'aliases' => array()
				) ),
				0,
				1,
				true,
				array(
					'label' => array( 'en' => 'bar', 'de' => 'bar' ),
					'description' => array( 'en' => 'more testing' ),
					'aliases' => array(),
					'links' => array(),
					'aliases' => array()
				)
			),
			array(
				ItemContent::newFromArray( array(
					'label' => array( 'nl' => 'test', 'da' => 'bar' ),
					'description' => array( 'en' => 'more testing' ),
					'aliases' => array(),
					'links' => array(),
					'aliases' => array()
				) ),
				0,
				1,
				true,
				array(
					'label' => array( 'nl' => 'test', 'da' => 'bar', 'en' => 'bar' ),
					'description' => array( 'en' => 'more testing' ),
					'aliases' => array(),
					'links' => array(),
					'aliases' => array()
				)
			),
		);
	}

	/**
	 * @dataProvider providerEditEntity
	 */
	public function testEditEntity( $item, $baseRevisionIdx, $applicableRevisionIdx, $typeTest, $expectedBase = null ) {
		$revisions = array();
		$itemContent = ItemContent::newEmpty();
		$itemContent->getEntity()->setLabel('en', "foo");
		$itemContent->save();
		$page = $itemContent->getWikiPage();
		$page->clear();
		$revisions[] = $itemContent->getWikiPage()->getRevision()->getId();
		$itemContent->getEntity()->setLabel('en', "bar");
		$itemContent->save();
		$page->clear();
		$revisions[] = $itemContent->getWikiPage()->getRevision()->getId();
		$itemContent->getEntity()->setLabel('de', "bar");
		$itemContent->save();
		$page->clear();
		$revisions[] = $itemContent->getWikiPage()->getRevision()->getId();
		$itemContent->getEntity()->setLabel('en', "test");
		$itemContent->getEntity()->setDescription('en', "more testing");
		$baseRevisionId = isset( $revisions[$baseRevisionIdx] ) ? $revisions[$baseRevisionIdx] : $baseRevisionIdx;
		$applicableRevisionId = isset( $revisions[$applicableRevisionIdx] ) ? $revisions[$applicableRevisionIdx] : $applicableRevisionIdx;
		$entity = $item->getEntity();
		$editEntity = EditEntity::newEditEntity( $entity, \User::newFromId(1), $baseRevisionId, $applicableRevisionId );

		if ( $typeTest ) {
			$this->assertTrue( $editEntity instanceof EditEntity );
			if ( isset( $expectedBase ) ) {
				// Assertions to be readded later (the methods does not exist in this version)
				//$this->assertTrue( $editEntity->getBaseDiff() instanceof \Wikibase\ItemDiff );
				//$this->assertTrue( $editEntity->getApplicableDiff() instanceof \Wikibase\ItemDiff );
				//$patched = $editEntity->getPatchedEntity();
				//$data = $patched->toArray();
				//unset( $data['entity'] );
				//$this->assertEquals( $expectedBase, $data );
			}
		}
		else {
			$this->assertEquals( null, $editEntity );
		}
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