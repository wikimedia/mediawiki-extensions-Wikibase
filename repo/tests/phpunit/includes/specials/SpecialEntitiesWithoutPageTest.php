<?php

namespace Wikibase\Test;

use Wikibase\Repo\Specials\SpecialEntitiesWithoutPage;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Term;

/**
 * @covers Wikibase\Repo\Specials\SpecialEntitiesWithoutPage
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
class SpecialEntitiesWithoutPageTest extends SpecialPageTestBase {

	protected function newSpecialPage() {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		return new SpecialEntitiesWithoutPage(
			'EntitiesWithoutLabel',
			Term::TYPE_LABEL,
			'wikibase-entitieswithoutlabel-legend',
			$wikibaseRepo->getStore()->newEntityPerPage(),
			$wikibaseRepo->getEntityFactory()
		);
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

		list( $output, ) = $this->executeSpecialPage( 'en' );
		$this->assertContains( 'value="en"', $output );
	}

}
