<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\Item;
use Wikibase\Repo\WikibaseRepo;

/**
 * Test case for modify term special pages
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
abstract class SpecialModifyTermTestCase extends SpecialPageTestBase {

	/**
	 * Creates a new item and returns its id.
	 *
	 * @return string
	 */
	private function createNewItem() {
		$item = Item::newEmpty();
		// add data and check if it is shown in the form
		$item->setLabel( 'de', 'foo' );
		$item->setDescription( 'de', 'foo' );
		$item->setAliases( 'de', array( 'foo' ) );

		// save the item
		$store = WikibaseRepo::getDefaultInstance()->getEntityStore();
		$store->saveEntity( $item, "testing", $GLOBALS['wgUser'], EDIT_NEW );

		// return the id
		return $item->getId()->getSerialization();
	}

	public function testExecute() {
		$id = $this->createNewItem();

		$page = $this->newSpecialPage();

		$matchers['id'] = array(
			'tag' => 'input',
			'attributes' => array(
				'id' => 'wb-modifyentity-id',
				'class' => 'wb-input',
				'name' => 'id',
			) );
		$matchers['language'] = array(
			'tag' => 'input',
			'attributes' => array(
				'id' => 'wb-modifyterm-language',
				'class' => 'wb-input',
				'name' => 'language',
				'value' => 'en',
			) );
		$matchers['value'] = array(
			'tag' => 'input',
			'attributes' => array(
				'id' => 'wb-modifyterm-value',
				'class' => 'wb-input',
				'name' => 'value',
			) );
		$matchers['submit'] = array(
			'tag' => 'input',
			'attributes' => array(
				'id' => 'wb-' . strtolower( $page->getName() ) . '-submit',
				'class' => 'wb-button',
				'type' => 'submit',
				'name' => 'wikibase-' . strtolower( $page->getName() ) . '-submit',
			) );

		// execute with no subpage value
		list( $output, ) = $this->executeSpecialPage( '', null, 'en' );
		foreach( $matchers as $key => $matcher ) {
			$this->assertTag( $matcher, $output, "Failed to match html output with tag '{$key}'" );
		}

		// execute with one subpage value
		list( $output, ) = $this->executeSpecialPage( $id, null, 'en' );
		$matchers['id']['attributes'] = array(
				'type' => 'hidden',
				'name' => 'id',
				'value' => $id,
			);
		$matchers['language']['attributes'] = array(
				'type' => 'hidden',
				'name' => 'language',
				'value' => 'en',
			);
		$matchers['remove'] = array(
			'tag' => 'input',
			'attributes' => array(
				'type' => 'hidden',
				'name' => 'remove',
				'value' => 'remove',
			) );

		foreach( $matchers as $key => $matcher ) {
			$this->assertTag( $matcher, $output, "Failed to match html output with tag '{$key}' passing one subpage value" );
		}

		// execute with two subpage values
		list( $output, ) = $this->executeSpecialPage( $id . '/de', null, 'en' );
		$matchers['language']['attributes']['value'] = 'de';
		$matchers['value']['attributes']['value'] = 'foo';

		foreach( $matchers as $key => $matcher ) {
			$this->assertTag( $matcher, $output, "Failed to match html output with tag '{$key}' passing two subpage values" );
		}
	}

}
