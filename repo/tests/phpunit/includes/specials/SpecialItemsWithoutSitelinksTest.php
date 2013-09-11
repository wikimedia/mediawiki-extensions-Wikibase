<?php

namespace Wikibase\Test;

/**
 * Tests for the SpecialItemsWithoutSitelinks class.
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
 * @author Adam Shorland
 */
class SpecialItemsWithoutSitelinksTest extends SpecialPageTestBase {

	protected function newSpecialPage() {
		return new \Wikibase\Repo\Specials\SpecialItemsWithoutSitelinks();
	}

	public function testExecute() {
		//TODO: Actually verify that the output is correct.
		//      Currently this just tests that there is no fatal error,
		//      and that the restriction handling is working and doesn't
		//      block. That is, the default should let the user execute
		//      the page.

		list( $output, ) = $this->executeSpecialPage( '' );
		$this->assertTrue( true, 'Calling execute without any subpage value' );
	}

}