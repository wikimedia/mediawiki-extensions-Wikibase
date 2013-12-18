<?php

namespace Wikibase\Test;

use Wikibase\Repo\Specials\SpecialItemByTitle;

/**
 * @covers Wikibase\Repo\Specials\SpecialItemByTitle
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
class SpecialItemByTitleTest extends SpecialPageTestBase {

	protected function newSpecialPage() {
		return new SpecialItemByTitle();
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
		foreach( $matchers as $key => $matcher ) {
			$this->assertTag( $matcher, $output, "Failed to match html output with tag '{$key}''" );
		}

		list( $output, ) = $this->executeSpecialPage( 'SiteText/PageText' );
		$matchers['site']['attributes']['value'] = 'SiteText';
		$matchers['page']['attributes']['value'] = 'PageText';

		foreach( $matchers as $key => $matcher ) {
			$this->assertTag( $matcher, $output, "Failed to match html output with tag '{$key}''" );
		}
	}

}
