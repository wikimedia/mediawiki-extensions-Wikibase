<?php

namespace Wikibase\Test;

use Wikibase\Repo\Specials\SpecialSetSiteLink;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\SiteLink;
use Wikibase\ItemContent;
use Wikibase\Settings;

/**
 * @covers Wikibase\Repo\Specials\SpecialSetSiteLink
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group SpecialPage
 * @group WikibaseSpecialPage
 *
 * @group Database
 *        ^---- needed because we rely on Title objects internally
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class SpecialSetSitelinkTest extends SpecialPageTestBase {

	protected function newSpecialPage() {
		return new SpecialSetSiteLink();
	}

	/**
	 * Creates a new item.
	 *
	 * @param string &$itemId
	 * @param string &$badgeId
	 */
	private function createNewItem( &$itemId, &$badgeId ) {
		// create empty badge
		$badge = Item::newEmpty();
		// save badge
		ItemContent::newFromItem( $badge )->save( "testing", null, EDIT_NEW );
		// set the badge id
		$badgeId = $badge->getId()->getSerialization();
		// add badge to settings
		Settings::singleton()->setSetting( 'badgeItems', array( $badgeId => '' ) );
		// create empty item
		$item = Item::newEmpty();
		// add data and check if it is shown in the form
		$item->addSiteLink( new SiteLink( 'dewiki', 'Wikidata', array( $badgeId ) ) );
		// save the item
		ItemContent::newFromItem( $item )->save( "testing", null, EDIT_NEW );
		// set the item id
		$itemId = $item->getId()->getSerialization();
	}

	public function testExecute() {
		//TODO: Actually verify that the output is correct.
		//      Currently this just tests that there is no fatal error,
		//      and that the restriction handling is working and doesn't
		//      block. That is, the default should let the user execute
		//      the page.

		$this->createNewItem( &$itemId, &$badgeId );

		$matchers['id'] = array(
			'tag' => 'input',
			'attributes' => array(
				'id' => 'wb-modifyentity-id',
				'class' => 'wb-input',
				'name' => 'id',
			) );
		$matchers['site'] = array(
			'tag' => 'input',
			'attributes' => array(
				'id' => 'wb-setsitelink-site',
				'class' => 'wb-input',
				'name' => 'site',
			) );
		$matchers['page'] = array(
			'tag' => 'input',
			'attributes' => array(
				'id' => 'wb-setsitelink-page',
				'class' => 'wb-input',
				'name' => 'page',
			) );

		// Experimental setting of badges on the special page
		// @todo remove experimental once JS UI is in place, (also remove the experimental test case)
		if ( defined( 'WB_EXPERIMENTAL_FEATURES' ) && WB_EXPERIMENTAL_FEATURES ) {
			$matchers['badges'] = array(
				'tag' => 'input',
				'attributes' => array(
					'id' => 'wb-setsitelink-badges',
					'class' => 'wb-input',
					'name' => 'badges',
				) );
		}

		$matchers['submit'] = array(
			'tag' => 'input',
			'attributes' => array(
				'id' => 'wb-setsitelink-submit',
				'class' => 'wb-button',
				'type' => 'submit',
				'name' => 'wikibase-setsitelink-submit',
			) );

		// execute with no subpage value
		list( $output, ) = $this->executeSpecialPage( '' );
		foreach( $matchers as $key => $matcher ){
			$this->assertTag( $matcher, $output, "Failed to match html output with tag '{$key}''" );
		}

		// execute with one subpage value
		list( $output, ) = $this->executeSpecialPage( $itemId );
		$matchers['id']['attributes']['value'] = $itemId;

		foreach( $matchers as $key => $matcher ) {
			$this->assertTag( $matcher, $output, "Failed to match html output with tag '{$key}' passing one subpage value" );
		}

		// execute with two subpage values
		list( $output, ) = $this->executeSpecialPage( $itemId . '/dewiki' );
		$matchers['id']['attributes'] = array(
				'type' => 'hidden',
				'name' => 'id',
				'value' => $itemId,
			);
		$matchers['site']['attributes'] = array(
				'type' => 'hidden',
				'name' => 'language',
				'value' => 'dewiki',
			);
		$matchers['remove'] = array(
			'tag' => 'input',
			'attributes' => array(
				'type' => 'hidden',
				'name' => 'remove',
				'value' => 'remove',
			) );
		$matchers['value']['attributes']['value'] = 'Wikidata';
		// Experimental setting of badges on the special page
		// @todo remove experimental once JS UI is in place, (also remove the experimental test case)
		if ( defined( 'WB_EXPERIMENTAL_FEATURES' ) && WB_EXPERIMENTAL_FEATURES ) {
			$matchers['badges']['attributes']['value'] = $badgeId;
		}

		foreach( $matchers as $key => $matcher ) {
			$this->assertTag( $matcher, $output, "Failed to match html output with tag '{$key}' passing two subpage values" );
		}
	}

}
