<?php

namespace Wikibase\Test;

/**
 * Tests for the SpecialItemByTitle class.
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
class SpecialItemByTitleTest extends SpecialPageTestBase {

	protected function newSpecialPage() {
		return new \Wikibase\Repo\Specials\SpecialItemByTitle();
	}

	public function testExecute() {
		//TODO: Verify that more of the output is correct.

		$matchers['site'] = array(
			'tag' => 'input',
			'attributes' => array(
				'id' => 'wb-itembytitle-sitename',
				'name' => 'site',
		) );
		$matchers['page'] = array(
			'tag' => 'input',
			'attributes' => array(
				'id' => 'pagename',
				'class' => 'wb-input-text',
				'name' => 'page',
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

		list( $output, ) = $this->executeSpecialPage( 'SiteText/PageText' );
		foreach( $matchers as $key => $matcher ){
			if( $key === 'site' ){
				$matcher['attributes']['value'] = 'SiteText';
			}
			if( $key === 'page' ){
				$matcher['attributes']['value'] = 'PageText';
			}
			$this->assertTag( $matcher, $output, "Failed to match html output with tag '{$key}''" );
		}
	}

}