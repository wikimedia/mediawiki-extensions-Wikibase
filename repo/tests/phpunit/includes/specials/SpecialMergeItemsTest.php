<?php

namespace Wikibase\Test;

use Wikibase\Repo\Specials\SpecialMergeItems;

/**
 * @covers \Wikibase\Repo\Specials\SpecialMergeItems
 *
 * @group Wikibase
 * @group SpecialPage
 * @group WikibaseSpecialPage
 *
 * @group Database
 *        ^---- needed because we rely on Title objects internally
 *
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class SpecialMergeItemsTest extends SpecialPageTestBase {

	protected function newSpecialPage() {
		return new SpecialMergeItems();
	}

	public function testExecute() {
		$matchers['fromid'] = array(
			'tag' => 'input',
			'attributes' => array(
				'id' => 'wb-mergeitems-fromid',
				'class' => 'wb-input',
				'name' => 'fromid',
			) );
		$matchers['toid'] = array(
			'tag' => 'input',
			'attributes' => array(
				'id' => 'wb-mergeitems-toid',
				'class' => 'wb-input',
				'name' => 'toid',
			) );
		$matchers['submit'] = array(
			'tag' => 'input',
			'attributes' => array(
				'id' => 'wb-mergeitems-submit',
				'class' => 'wb-button',
				'type' => 'submit',
				'name' => 'wikibase-mergeitems-submit',
			) );

		list( $output, ) = $this->executeSpecialPage( '' );
		foreach( $matchers as $key => $matcher ){
			$this->assertTag( $matcher, $output, "Failed to match html output with tag '{$key}''" );
		}
	}

}