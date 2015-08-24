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

	protected function setUp() {

		parent::setUp();

		$this->setMwGlobals( 'wgGroupPermissions', [ '*' => [ 'edit' => true, 'item-term' => true ] ] );
	}

	/**
	 * Creates a new item and returns its id.
	 *
	 * @param string $language
	 * @param string $termValue
	 * @return string
	 */
	private function createNewItemWithTerms( $language, $termValue ) {

		$item = new Item();
		// add data and check if it is shown in the form
		$item->setLabel( $language, $termValue );
		$item->setDescription( $language, $termValue );
		$item->setAliases( $language, [ $termValue ] );

		// save the item
		$store = WikibaseRepo::getDefaultInstance()->getEntityStore();
		$store->saveEntity( $item, "testing", $GLOBALS['wgUser'], EDIT_NEW | EntityContent::EDIT_IGNORE_CONSTRAINTS );

		// return the id
		return $item->getId()->getSerialization();
	}

	public function testRenderWithoutSubPage_AllInputFieldsPresent() {
		$page = $this->newSpecialPage();

		$matchers['id'] = [
			'tag' => 'div',
			'attributes' => [
				'id' => 'wb-modifyentity-id',
				'class' => 'wb-input',
			],
			'child' => [
				'tag' => 'input',
				'attributes' => [
					'name' => 'id',
				]
			] ];
		$matchers['language'] = [
			'tag' => 'div',
			'attributes' => [
				'id' => 'wb-modifyterm-language',
				'class' => 'wb-input',
			],
			'child' => [
				'tag' => 'input',
				'attributes' => [
					'name' => 'language',
					'value' => self::USER_LANGUAGE,
				]
			] ];
		$matchers['value'] = [
			'tag' => 'div',
			'attributes' => [
				'id' => 'wb-modifyterm-value',
				'class' => 'wb-input',
			],
			'child' => [
				'tag' => 'input',
				'attributes' => [
					'name' => 'value',
				]
			] ];
		$matchers['submit'] = [
			'tag' => 'div',
			'attributes' => [
				'id' => 'wb-' . strtolower( $page->getName() ) . '-submit',
			],
			'child' => [
				'tag' => 'button',
				'attributes' => [
					'type' => 'submit',
					'name' => 'wikibase-' . strtolower( $page->getName() ) . '-submit',
				]
			] ];

		// execute with no subpage value
		list( $output, ) = $this->executeSpecialPage( '', null, self::USER_LANGUAGE );
		foreach ( $matchers as $key => $matcher ) {
			$this->assertTag( $matcher, $output, "Failed to match html output with tag '{$key}'" );
		}
	}

	public function testRenderWithOneSubpageValue_TreatsValueAsItemIdAndShowsOnlyTermInputField() {
		$notUserLanguage = 'de';
		$id = $this->createNewItemWithTerms( $notUserLanguage, 'some-term-value' );

		$page = $this->newSpecialPage();

		$matchers['id'] = [
			'tag' => 'input',
			'attributes' => [
				'type' => 'hidden',
				'name' => 'id',
				'value' => $id,
			] ];
		$matchers['language'] = [
			'tag' => 'input',
			'attributes' => [
				'name' => 'language',
				'value' => self::USER_LANGUAGE,
				'type' => 'hidden',
			] ];
		$matchers['value'] = [
			'tag' => 'div',
			'attributes' => [
				'id' => 'wb-modifyterm-value',
				'class' => 'wb-input',
			],
			'child' => [
				'tag' => 'input',
				'attributes' => [
					'name' => 'value',
				]
			] ];
		$matchers['submit'] = [
			'tag' => 'div',
			'attributes' => [
				'id' => 'wb-' . strtolower( $page->getName() ) . '-submit',
			],
			'child' => [
				'tag' => 'button',
				'attributes' => [
					'type' => 'submit',
					'name' => 'wikibase-' . strtolower( $page->getName() ) . '-submit',
				]
			] ];
		$matchers['remove'] = [
			'tag' => 'input',
			'attributes' => [
				'type' => 'hidden',
				'name' => 'remove',
				'value' => 'remove',
			] ];

		// execute with one subpage value
		list( $output, ) = $this->executeSpecialPage( $id, null, self::USER_LANGUAGE );

		foreach ( $matchers as $key => $matcher ) {
			$this->assertTag( $matcher, $output, "Failed to match html output with tag '{$key}' passing one subpage value" );
		}
	}

	public function testRenderWithTwoSubpageValues_TreatsSecondValueAsLanguageAndShowsOnlyTermInputField() {
		$id = $this->createNewItemWithTerms( $itemTermLanguage = 'de', $termValue = 'foo' );

		$page = $this->newSpecialPage();

		$matchers['id'] = [
			'tag' => 'input',
			'attributes' => [
				'type' => 'hidden',
				'name' => 'id',
				'value' => $id,
			] ];
		$matchers['language'] = [
			'tag' => 'input',
			'attributes' => [
				'name' => 'language',
				'value' => $itemTermLanguage,
				'type' => 'hidden',
			] ];
		$matchers['value'] = [
			'tag' => 'div',
			'attributes' => [
				'id' => 'wb-modifyterm-value',
				'class' => 'wb-input',
			],
			'child' => [
				'tag' => 'input',
				'attributes' => [
					'name' => 'value',
					'value' => $termValue
				]
			] ];
		$matchers['submit'] = [
			'tag' => 'div',
			'attributes' => [
				'id' => 'wb-' . strtolower( $page->getName() ) . '-submit',
			],
			'child' => [
				'tag' => 'button',
				'attributes' => [
					'type' => 'submit',
					'name' => 'wikibase-' . strtolower( $page->getName() ) . '-submit',
				]
			] ];
		$matchers['remove'] = [
			'tag' => 'input',
			'attributes' => [
				'type' => 'hidden',
				'name' => 'remove',
				'value' => 'remove',
			] ];

		// execute with two subpage values
		list( $output, ) = $this->executeSpecialPage( $id . '/' . $itemTermLanguage, null, self::USER_LANGUAGE );

		foreach ( $matchers as $key => $matcher ) {
			$this->assertTag( $matcher, $output, "Failed to match html output with tag '{$key}' passing two subpage values" . PHP_EOL . $output );
		}
	}

	public function testValuePreservesWhenNothingEntered() {
		$id = $this->createNewItemWithTerms( $language = 'de', $termValue = 'foo' );

		$request = new FauxRequest( [ 'id' => $id, 'language' => $language, 'value' => '' ], true );

		list( $output, ) = $this->executeSpecialPage( '', $request );

		$this->assertTag( [
			'tag' => 'div',
			'attributes' => [
				'id' => 'wb-modifyterm-value',
				'class' => 'wb-input',
			],
			'child' => [
				'tag' => 'input',
				'attributes' => [
					'name' => 'value',
					'value' => $termValue,
				]
			]
		], $output, 'Value still preserves when no value was entered in the big form' );
	}

}
