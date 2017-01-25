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
 * @license GPL-2.0+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
abstract class SpecialModifyTermTestCase extends SpecialPageTestBase {

	const USER_LANGUAGE = 'en';

	/**
	 * Creates a new item and returns its id.
	 *
	 * @return string
	 */
	private function createNewItem( $language, $labelAndDescriptionAndAlias ) {
		$item = new Item();
		// add data and check if it is shown in the form
		$item->setLabel( $language, $labelAndDescriptionAndAlias );
		$item->setDescription( $language, $labelAndDescriptionAndAlias );
		$item->setAliases( $language, array( $labelAndDescriptionAndAlias ) );

		// save the item
		$store = WikibaseRepo::getDefaultInstance()->getEntityStore();
		$store->saveEntity( $item, "testing", $GLOBALS['wgUser'], EDIT_NEW | EntityContent::EDIT_IGNORE_CONSTRAINTS );

		// return the id
		return $item->getId()->getSerialization();
	}


	public function testRenderWithoutSubPage_AllInputFieldsPresent() {
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
				'value' => self::USER_LANGUAGE,
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
		list( $output, ) = $this->executeSpecialPage( '', null, self::USER_LANGUAGE );
		foreach ( $matchers as $key => $matcher ) {
			$this->assertTag( $matcher, $output, "Failed to match html output with tag '{$key}'" );
		}
	}

	public function testRenderWithOneSubpageValue_TreatsValueAsItemIdAndShowsOnlyTermInputField() {
		$id = $this->createNewItem( $language = 'de', $termValue = 'foo' );

		$this->setMwGlobals( 'wgGroupPermissions', array( '*' => array( 'edit' => true, 'item-term' => true ) ) );

		$page = $this->newSpecialPage();

		$matchers['id'] = array(
			'tag' => 'input',
			'attributes' => array(
				'type' => 'hidden',
				'name' => 'id',
				'value' => $id,
			) );
		$matchers['language'] = array(
			'tag' => 'input',
			'attributes' => array(
				'name' => 'language',
				'value' => self::USER_LANGUAGE,
				'type' => 'hidden',
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
		$matchers['remove'] = array(
			'tag' => 'input',
			'attributes' => array(
				'type' => 'hidden',
				'name' => 'remove',
				'value' => 'remove',
			) );

		// execute with one subpage value
		list( $output, ) = $this->executeSpecialPage( $id, null, self::USER_LANGUAGE );

		foreach ( $matchers as $key => $matcher ) {
			$this->assertTag( $matcher, $output, "Failed to match html output with tag '{$key}' passing one subpage value");
		}
	}

	public function testRenderWithTwoSubpageValues_TreatsSecondValueAsLanguageAndShowsOnlyTermInputField() {
		$id = $this->createNewItem( $itemTermLanguage = 'de', $termValue = 'foo' );

		$page = $this->newSpecialPage();

		$matchers['id'] = array(
			'tag' => 'input',
			'attributes' => array(
				'type' => 'hidden',
				'name' => 'id',
				'value' => $id,
			) );
		$matchers['language'] = array(
			'tag' => 'input',
			'attributes' => array(
				'name' => 'language',
				'value' => $itemTermLanguage,
				'type' => 'hidden',
			) );
		$matchers['value'] = array(
			'tag' => 'input',
			'attributes' => array(
				'id' => 'wb-modifyterm-value',
				'class' => 'wb-input',
				'name' => 'value',
				'value' => $termValue
			) );
		$matchers['submit'] = array(
			'tag' => 'input',
			'attributes' => array(
				'id' => 'wb-' . strtolower( $page->getName() ) . '-submit',
				'class' => 'wb-button',
				'type' => 'submit',
				'name' => 'wikibase-' . strtolower( $page->getName() ) . '-submit',
			) );
		$matchers['remove'] = array(
			'tag' => 'input',
			'attributes' => array(
				'type' => 'hidden',
				'name' => 'remove',
				'value' => 'remove',
			) );

		// execute with two subpage values
		list( $output, ) = $this->executeSpecialPage( $id . '/' . $itemTermLanguage, null, self::USER_LANGUAGE );

		foreach ( $matchers as $key => $matcher ) {
			$this->assertTag( $matcher, $output, "Failed to match html output with tag '{$key}' passing two subpage values" . PHP_EOL . $output );
		}
	}

	public function testValuePreservesWhenNothingEntered() {
		$id = $this->createNewItem( $language = 'de', $termValue = 'foo' );

		$this->setMwGlobals( 'wgGroupPermissions', array( '*' => array( 'edit' => true, 'item-term' => true ) ) );

		$request = new FauxRequest( array( 'id' => $id, 'language' => $language, 'value' => '' ), true );

		list( $output, ) = $this->executeSpecialPage( '', $request );

		$request = new FauxRequest( array( 'id' => $id, 'language' => 'de', 'value' => '' ), true );

		list( $output, ) = $this->executeSpecialPage( '', $request );

		$this->assertTag( array(
			'tag' => 'input',
			'attributes' => array(
				'id' => 'wb-modifyterm-value',
				'class' => 'wb-input',
				'name' => 'value',
				'value' => 'foo',
			)
		), $output, 'Value still preserves when no value was entered in the big form' );
	}

}
