<?php

namespace Wikibase\Test;

use Wikibase\Repo\Specials\SpecialEntitiesWithoutDescription;

/**
 * @covers Wikibase\Repo\Specials\SpecialEntitiesWithoutDescription
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
 * @author Bene* < benestar.wikimedia@googlemail.com >
 * @author Adam Shorland
 */
class SpecialEntitiesWithoutDescriptionTest extends SpecialPageTestBase {

	protected function newSpecialPage() {
		return new SpecialEntitiesWithoutDescription();
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
		foreach( $matchers as $key => $matcher ) {
			$this->assertTag( $matcher, $output, "Failed to match html output with tag '{$key}''" );
		}

		$this->executeSpecialPage( 'en' );
		$this->assertTrue( true, 'Calling execute with a subpage value' ); //TODO: assert output
	}

}
