<?php

namespace Wikibase\Test;

/**
 * Tests for the SpecialSetDescription class.
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
 * @author John Erling Blad < jeblad@gmail.com >
 */
class SpecialSetDescriptionTest extends SpecialPageTestBase {

	protected function newSpecialPage() {
		return new \Wikibase\Repo\Specials\SpecialSetDescription();
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
				'id' => 'wb-setentity-id',
				'class' => 'wb-input',
				'name' => 'id',
			) );
		$matchers['language'] = array(
			'tag' => 'input',
			'attributes' => array(
				'id' => 'wb-setentity-language',
				'class' => 'wb-input',
				'name' => 'language',
			) );
		$matchers['value'] = array(
			'tag' => 'input',
			'attributes' => array(
				'id' => 'wb-setentity-value',
				'class' => 'wb-input',
				'name' => 'value',
			) );
		$matchers['submit'] = array(
			'tag' => 'input',
			'attributes' => array(
				'id' => 'wb-setdescription-submit',
				'class' => 'wb-button',
				'type' => 'submit',
				'name' => 'wikibase-setdescription-submit',
			) );

		list( $output, ) = $this->executeSpecialPage( '' );
		foreach( $matchers as $key => $matcher ){
			$this->assertTag( $matcher, $output, "Failed to match html output with tag '{$key}''" );
		}
	}

}