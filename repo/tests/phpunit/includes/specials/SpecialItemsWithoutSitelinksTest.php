<?php

namespace Wikibase\Test;

use Wikibase\Repo\Specials\SpecialItemsWithoutSitelinks;

/**
 * @covers Wikibase\Repo\Specials\SpecialItemsWithoutSitelinks
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
 * @author Adam Shorland
 */
class SpecialItemsWithoutSitelinksTest extends SpecialPageTestBase {

	protected function newSpecialPage() {
		return new SpecialItemsWithoutSitelinks();
	}

	public function testExecute() {
		//TODO: Actually verify that the output is correct.
		//      Currently this just tests that there is no fatal error,
		//      and that the restriction handling is working and doesn't
		//      block. That is, the default should let the user execute
		//      the page.

		$this->executeSpecialPage( '' );
		$this->assertTrue( true, 'Calling execute without any subpage value' );
	}

}
