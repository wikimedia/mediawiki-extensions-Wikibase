<?php

namespace Wikibase\Test;

/**
 * Tests for the SpecialEntitiesWithoutDescription class.
 *
 * @file
 * @since 0.4
 *
 * @ingroup WikibaseRepoTest
 * @ingroup Test
 *
 * @group Wikibase
 * @group SpecialPage
 * @group WikibaseSpecialPage
 *
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@googlemail.com >
 * @author Adam Shorland
 */
class SpecialEntitiesWithoutDescriptionTest extends SpecialPageTestBase {

	protected function newSpecialPage() {
		return new \Wikibase\Repo\Specials\SpecialEntitiesWithoutDescription();
	}

	public function testExecute() {

		$matchers['language'] = array(
			'tag' => 'input',
			'attributes' => array(
				'id' => 'wb-entitieswithoutpage-language',
				'name' => 'language',
			) );
		$matchers['submit'] = array(
			'tag' => 'input',
			'attributes' => array(
				'id' => 'wikibase-entitieswithoutpage-submit',
				'class' => 'wb-input-button',
				'type' => 'submit',
				'name' => 'submit',
			) );


		list( $output, ) = $this->executeSpecialPage( '' );
		foreach( $matchers as $key => $matcher ){
			$this->assertTag( $matcher, $output, "Failed to match html output with tag '{$key}''" );
		}

		list( $output, ) = $this->executeSpecialPage( 'en' );
		$this->assertTrue( true, 'Calling execute with a subpage value' ); //TODO: assert output
	}

}