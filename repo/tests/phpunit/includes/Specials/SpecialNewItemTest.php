<?php

namespace Wikibase\Repo\Tests\Specials;

use SpecialPageTestBase;
use Wikibase\Repo\Specials\SpecialNewItem;

/**
 * @covers Wikibase\Repo\Specials\SpecialNewItem
 * @covers Wikibase\Repo\Specials\SpecialNewEntity
 * @covers Wikibase\Repo\Specials\SpecialWikibaseRepoPage
 * @covers Wikibase\Repo\Specials\SpecialWikibasePage
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group SpecialPage
 * @group WikibaseSpecialPage
 *
 * @group Database
 *        ^---- needed because we rely on Title objects internally
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 * @author Addshore
 */
class SpecialNewItemTest extends SpecialPageTestBase {

	protected function newSpecialPage() {
		return new SpecialNewItem();
	}

	public function testExecute() {
		//TODO: Verify that more of the output is correct.
		//TODO: Verify that item creation works via a faux post request

		$this->setMwGlobals( 'wgGroupPermissions', array( '*' => array( 'createpage' => true ) ) );

		$matchers['label'] = array(
			'tag' => 'div',
			'attributes' => array(
				'id' => 'wb-newentity-label',
			),
			'child' => array(
				'tag' => 'input',
				'attributes' => array(
					'name' => 'label',
				)
			) );
		$matchers['description'] = array(
			'tag' => 'div',
			'attributes' => array(
				'id' => 'wb-newentity-description',
			),
			'child' => array(
				'tag' => 'input',
				'attributes' => array(
					'name' => 'description',
				)
			) );
		$matchers['submit'] = array(
			'tag' => 'div',
			'attributes' => array(
				'id' => 'wb-newentity-submit',
			),
			'child' => array(
				'tag' => 'button',
				'attributes' => array(
					'type' => 'submit',
					'name' => 'submit',
				)
			) );

		list( $output, ) = $this->executeSpecialPage( '' );
		foreach ( $matchers as $key => $matcher ) {
			$this->assertTag( $matcher, $output, "Failed to match html output with tag '{$key}''" );
		}

		list( $output, ) = $this->executeSpecialPage( 'LabelText/DescriptionText' );
		$matchers['label']['child'][0]['attributes']['value'] = 'LabelText';
		$matchers['description']['child'][0]['attributes']['value'] = 'DescriptionText';

		foreach ( $matchers as $key => $matcher ) {
			$this->assertTag( $matcher, $output, "Failed to match html output with tag '{$key}''" );
		}

	}

}
