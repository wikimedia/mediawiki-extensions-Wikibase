<?php

namespace Wikibase\Test;

use Wikibase\Repo\Specials\SpecialNewProperty;

/**
 * @covers Wikibase\Repo\Specials\SpecialNewProperty
 *
 * @since 0.1
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
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Adam Shorland
 */
class SpecialNewPropertyTest extends SpecialPageTestBase {

	protected function newSpecialPage() {
		return new SpecialNewProperty();
	}

	public function testExecute() {
		//TODO: Verify that more of the output is correct.
		//TODO: Verify that item creation works via a faux post request

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
		foreach( $matchers as $key => $matcher ){
			$this->assertTag( $matcher, $output, "Failed to match html output with tag '{$key}''" );
		}

		list( $output, ) = $this->executeSpecialPage( 'LabelText/DescriptionText' );
		foreach( $matchers as $key => $matcher ){
			if( $key === 'label' ){
				$matcher['attributes']['value'] = 'LabelText';
			}
			if( $key === 'description' ){
				$matcher['attributes']['value'] = 'DescriptionText';
			}
			$this->assertTag( $matcher, $output, "Failed to match html output with tag '{$key}''" );
		}
	}

}
