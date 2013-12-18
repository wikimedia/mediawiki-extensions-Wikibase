<?php

namespace Wikibase\Test;

use Wikibase\Repo\Specials\SpecialSetAliases;

/**
 * @covers Wikibase\Repo\Specials\SpecialSetAliases
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
 */
class SpecialSetAliasesTest extends SpecialPageTestBase {

	protected function newSpecialPage() {
		return new SpecialSetAliases();
	}

	public function testExecute() {
		//TODO: Actually verify that the output is correct.
		//      Currently this just tests that there is no fatal error,
		//      and that the restriction handling is working and doesn't
		//      block. That is, the default should let the user execute
		//      the page.

		//TODO: Verify that item creation works via a faux post request

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
				'id' => 'wb-setaliases-submit',
				'class' => 'wb-button',
				'type' => 'submit',
				'name' => 'wikibase-setaliases-submit',
			) );

		list( $output, ) = $this->executeSpecialPage( '' );
		foreach( $matchers as $key => $matcher ){
			$this->assertTag( $matcher, $output, "Failed to match html output with tag '{$key}''" );
		}
	}

}
