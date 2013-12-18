<?php

namespace Wikibase\Test;

use Wikibase\Item;
use Wikibase\DataModel\Entity\EntityId;

/**
 * Test case for modify term special pages
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
abstract class SpecialModifyTermTestCase extends SpecialPageTestBase {

	private function createNewItem() {
		$item = Item::newEmpty();
		$item->setId( EntityId::newFromPrefixedId( 'Q42' ) );
		// add data and check if it is shown in the form
		$item->setLabel( 'de', 'foo' );
		$item->setDescription( 'de', 'foo' );
		$item->setAliases( 'de', array( 'foo' ) );
		// save the item
		ItemContent::newFromItem( $item )->save( "testing", null, EDIT_NEW );
	}

	public function testExecute() {
		//TODO: Actually verify that the output is correct.
		//      Currently this just tests that there is no fatal error,
		//      and that the restriction handling is working and doesn't
		//      block. That is, the default should let the user execute
		//      the page.

		$this->createNewItem();

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
		list( $output, ) = $this->executeSpecialPage( '' );
		foreach( $matchers as $key => $matcher ) {
			$this->assertTag( $matcher, $output, "Failed to match html output with tag '{$key}'" );
		}

		// execute with one subpage value
		list( $output, ) = $this->executeSpecialPage( 'Q42' );
		$matchers['id']['attributes'] = array(
				'type' => 'hidden',
				'name' => 'id',
				'value' => 'Q42',
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
		list( $output, ) = $this->executeSpecialPage( 'Q42/de' );
		$matchers['language']['attributes']['value'] = 'de';
		$matchers['value']['attributes']['value'] = 'foo';

		foreach( $matchers as $key => $matcher ) {
			$this->assertTag( $matcher, $output, "Failed to match html output with tag '{$key}' passing two subpage values" );
		}
	}

}
