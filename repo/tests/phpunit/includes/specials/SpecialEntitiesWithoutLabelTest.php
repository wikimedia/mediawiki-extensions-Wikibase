<?php

namespace Wikibase\Test;

use Wikibase\Repo\Specials\SpecialEntitiesWithoutLabel;

/**
 * @covers Wikibase\Repo\Specials\SpecialEntitiesWithoutLabel
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group SpecialPage
 * @group WikibaseSpecialPage
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 */
class SpecialEntitiesWithoutLabelTest extends SpecialPageTestBase {

	protected function newSpecialPage() {
		return new SpecialEntitiesWithoutLabel();
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
	}

}
