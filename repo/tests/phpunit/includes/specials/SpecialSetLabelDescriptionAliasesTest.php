<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\Item;
use Wikibase\EntityContent;
use Wikibase\Repo\Specials\SpecialSetLabelDescriptionAliases;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers Wikibase\Repo\Specials\SpecialSetLabelDescriptionAliases
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group SpecialPage
 * @group WikibaseSpecialPage
 * @group Database
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 * @author H. Snater < mediawiki@snater.com >
 */
class SpecialSetLabelDescriptionAliasesTest extends SpecialPageTestBase {

	/**
	 * @see SpecialPageTestBase::newSpecialPage()
	 *
	 * @return SpecialSetLabelDescriptionAliases
	 */
	protected function newSpecialPage() {
		return new SpecialSetLabelDescriptionAliases();
	}

	/**
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
		$store->saveEntity( $item, "testing", $GLOBALS['wgUser'], EDIT_NEW | EntityContent::EDIT_IGNORE_CONSTRAINTS );

		// return the id
		return $item->getId()->getSerialization();
	}

	public function testExecute() {
		$id = $this->createNewItem();

		$this->newSpecialPage();

		$matchers['id'] = array(
			'tag' => 'input',
			'attributes' => array(
				'id' => 'wb-modifyentity-id',
				'class' => 'wb-input',
				'name' => 'id',
			),
		);
		$matchers['language'] = array(
			'tag' => 'input',
			'attributes' => array(
				'id' => 'wikibase-setlabeldescriptionaliases-language',
				'class' => 'wb-input',
				'name' => 'language',
				'value' => 'en',
			),
		);
		$matchers['label'] = array(
			'tag' => 'input',
			'attributes' => array(
				'id' => 'wikibase-setlabeldescriptionaliases-label',
				'class' => 'wb-input',
				'name' => 'label',
			),
		);
		$matchers['description'] = array(
			'tag' => 'input',
			'attributes' => array(
				'id' => 'wikibase-setlabeldescriptionaliases-description',
				'class' => 'wb-input',
				'name' => 'description',
			),
		);
		$matchers['aliases'] = array(
			'tag' => 'input',
			'attributes' => array(
				'id' => 'wikibase-setlabeldescriptionaliases-aliases',
				'class' => 'wb-input',
				'name' => 'aliases',
			),
		);
		$matchers['submit'] = array(
			'tag' => 'input',
			'attributes' => array(
				'id' => 'wb-setlabeldescriptionaliases-submit',
				'class' => 'wb-button',
				'type' => 'submit',
				'name' => 'wikibase-setlabeldescriptionaliases-submit',
			),
		);

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
