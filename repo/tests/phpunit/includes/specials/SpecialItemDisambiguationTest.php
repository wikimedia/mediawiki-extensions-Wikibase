<?php

namespace Wikibase\Test;

/**
 * Tests for the SpecialItemDisambiguation class.
 *
 * @file
 * @since 0.1
 *
 * @ingroup WikibaseRepoTest
 * @ingroup Test
 *
 * @group Wikibase
 * @group SpecialPage
 * @group WikibaseSpecialPage
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 * @author Adam Shorland
 */
class SpecialItemDisambiguationTest extends SpecialPageTestBase {

	protected function newSpecialPage() {
		return new \Wikibase\Repo\Specials\SpecialItemDisambiguation();
	}

	public function testExecute() {
		//TODO: Verify that more of the output is correct.

		$matchers['language'] = array(
			'tag' => 'input',
			'attributes' => array(
				'id' => 'wb-itemdisambiguation-languagename',
				'class' => 'wb-input-text',
				'name' => 'language',
			) );
		$matchers['label'] = array(
			'tag' => 'input',
			'attributes' => array(
				'id' => 'labelname',
				'class' => 'wb-input-text',
				'name' => 'label',
			) );
		$matchers['submit'] = array(
			'tag' => 'input',
			'attributes' => array(
				'id' => 'wb-itembytitle-submit',
				'class' => 'wb-input-button',
				'type' => 'submit',
				'name' => 'submit',
			) );

		list( $output, ) = $this->executeSpecialPage( '' );
		foreach( $matchers as $key => $matcher ){
			$this->assertTag( $matcher, $output, "Failed to match html output with tag '{$key}''" );
		}

		list( $output, ) = $this->executeSpecialPage( 'LangText/LabelText' );
		foreach( $matchers as $key => $matcher ){
			if( $key === 'language' ){
				$matcher['attributes']['value'] = 'LangText';
			}
			if( $key === 'label' ){
				$matcher['attributes']['value'] = 'LabelText';
			}
			$this->assertTag( $matcher, $output, "Failed to match html output with tag '{$key}''" );
		}

	}

}
