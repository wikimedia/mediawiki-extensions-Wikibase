<?php

namespace Wikibase\Test;

use Wikibase\Repo\Specials\SpecialListDatatypes;

/**
 * @covers Wikibase\Repo\Specials\SpecialListDatatypes
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group SpecialPage
 * @group WikibaseSpecialPage
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 */
class SpecialListDataTypesTest extends SpecialPageTestBase {

	protected function newSpecialPage() {
		return new SpecialListDatatypes();
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
