<?php

namespace Wikibase\Repo\Tests\Specials;

use FauxRequest;
use SpecialPageTestBase;
use Wikibase\DataModel\Entity\Item;
use Wikibase\EntityContent;
use Wikibase\Repo\WikibaseRepo;

/**
 * Test case for modify term special pages
 *
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
abstract class SpecialModifyTermTestCase extends SpecialPageTestBase {

	/**
	 * Creates a new item and returns its id.
	 *
	 * @return string
	 */
	private function createNewItem() {
		$item = new Item();
		// add data and check if it is shown in the form
		$item->setLabel( 'de', 'foo' );
		$item->setDescription( 'de', 'foo' );
		$item->setAliases( 'de', [ 'foo' ] );

		// save the item
		$store = WikibaseRepo::getDefaultInstance()->getEntityStore();
		$store->saveEntity( $item, "testing", $GLOBALS['wgUser'], EDIT_NEW | EntityContent::EDIT_IGNORE_CONSTRAINTS );

		// return the id
		return $item->getId()->getSerialization();
	}

	public function testExecute() {
		$id = $this->createNewItem();

		$this->setMwGlobals( 'wgGroupPermissions', [ '*' => [ 'edit' => true, 'item-term' => true ] ] );

		$page = $this->newSpecialPage();

		$matchers['id'] = [
			'tag' => 'input',
			'attributes' => [
				'id' => 'wb-modifyentity-id',
				'class' => 'wb-input',
				'name' => 'id',
			] ];
		$matchers['language'] = [
			'tag' => 'input',
			'attributes' => [
				'id' => 'wb-modifyterm-language',
				'class' => 'wb-input',
				'name' => 'language',
				'value' => 'en',
			] ];
		$matchers['value'] = [
			'tag' => 'input',
			'attributes' => [
				'id' => 'wb-modifyterm-value',
				'class' => 'wb-input',
				'name' => 'value',
			] ];
		$matchers['submit'] = [
			'tag' => 'input',
			'attributes' => [
				'id' => 'wb-' . strtolower( $page->getName() ) . '-submit',
				'class' => 'wb-button',
				'type' => 'submit',
				'name' => 'wikibase-' . strtolower( $page->getName() ) . '-submit',
			] ];

		// execute with no subpage value
		list( $output, ) = $this->executeSpecialPage( '', null, 'en' );
		foreach ( $matchers as $key => $matcher ) {
			$this->assertTag( $matcher, $output, "Failed to match html output with tag '{$key}'" );
		}

		// execute with one subpage value
		list( $output, ) = $this->executeSpecialPage( $id, null, 'en' );
		$matchers['id']['attributes'] = [
				'type' => 'hidden',
				'name' => 'id',
				'value' => $id,
			];
		$matchers['language']['attributes'] = [
				'type' => 'hidden',
				'name' => 'language',
				'value' => 'en',
			];
		$matchers['remove'] = [
			'tag' => 'input',
			'attributes' => [
				'type' => 'hidden',
				'name' => 'remove',
				'value' => 'remove',
			] ];

		foreach ( $matchers as $key => $matcher ) {
			$this->assertTag( $matcher, $output, "Failed to match html output with tag '{$key}' passing one subpage value" );
		}

		// execute with two subpage values
		list( $output, ) = $this->executeSpecialPage( $id . '/de', null, 'en' );
		$matchers['language']['attributes']['value'] = 'de';
		$matchers['value']['attributes']['value'] = 'foo';

		foreach ( $matchers as $key => $matcher ) {
			$this->assertTag( $matcher, $output, "Failed to match html output with tag '{$key}' passing two subpage values" );
		}
	}

	public function testValuePreservesWhenNothingEntered() {
		$id = $this->createNewItem();

		$this->setMwGlobals( 'wgGroupPermissions', [ '*' => [ 'edit' => true, 'item-term' => true ] ] );

		$request = new FauxRequest( [ 'id' => $id, 'language' => 'de', 'value' => '' ], true );

		list( $output, ) = $this->executeSpecialPage( '', $request );

		$this->assertTag( [
			'tag' => 'input',
			'attributes' => [
				'id' => 'wb-modifyterm-value',
				'class' => 'wb-input',
				'name' => 'value',
				'value' => 'foo',
			]
		], $output, 'Value still preserves when no value was entered in the big form' );
	}

}
