<?php

namespace Wikibase\Test;

use Wikibase\Repo\Specials\SpecialNewItem;

/**
 * @covers Wikibase\Repo\Specials\SpecialNewItem
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
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 * @author Adam Shorland
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
			'tag' => 'input',
			'attributes' => array(
				'id' => 'wb-newentity-label',
				'class' => 'wb-input',
				'name' => 'label',
			) );
		$matchers['description'] = array(
			'tag' => 'input',
			'attributes' => array(
				'id' => 'wb-newentity-description',
				'class' => 'wb-input',
				'name' => 'description',
			) );
		$matchers['submit'] = array(
			'tag' => 'input',
			'attributes' => array(
				'id' => 'wb-newentity-submit',
				'class' => 'wb-button',
				'type' => 'submit',
				'name' => 'submit',
			) );

		list( $output, ) = $this->executeSpecialPage( '' );
		foreach ( $matchers as $key => $matcher ) {
			$this->assertTag( $matcher, $output, "Failed to match html output with tag '{$key}''" );
		}

		list( $output, ) = $this->executeSpecialPage( 'LabelText/DescriptionText' );
		$matchers['label']['attributes']['value'] = 'LabelText';
		$matchers['description']['attributes']['value'] = 'DescriptionText';

		foreach ( $matchers as $key => $matcher ) {
			$this->assertTag( $matcher, $output, "Failed to match html output with tag '{$key}''" );
		}

	}

}
