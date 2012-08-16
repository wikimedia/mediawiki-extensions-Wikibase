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
			array( ItemContent::newEmpty(), 9999999, 9999999, true ),
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
		$editEntity = EditEntity::newEditEntity( $item->getEntity(), $baseRevisionId, $applicableRevisionId );

		if ( $typeTest ) {
			$this->assertTrue( $editEntity instanceof EditEntity );
			if ( isset( $expectedBase ) ) {
				$this->assertTrue( $editEntity->getBaseDiff() instanceof \Wikibase\ItemDiff );
				$this->assertTrue( $editEntity->getApplicableDiff() instanceof \Wikibase\ItemDiff );
				$patched = $editEntity->getPatchedEntity();
				$data = $patched->toArray();
				unset( $data['entity'] );
				$this->assertEquals( $expectedBase, $data );
			}
		}
		else {
			$this->assertEquals( null, $editEntity );
		}
	}

	/**
	 * @TODO: test userWasLastToEdit
	 */
	// public function testUserWasLastToEdit() {
	// }
}