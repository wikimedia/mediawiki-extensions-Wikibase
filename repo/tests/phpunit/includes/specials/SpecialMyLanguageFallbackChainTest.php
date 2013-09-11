<?php

namespace Wikibase\Test;

/**
 * Tests for the SpecialMyLanguageFallbackChain class.
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
 */
class SpecialMyLanguageFallbackChainTest extends SpecialPageTestBase {

	protected function newSpecialPage() {
		return new \Wikibase\Repo\Specials\SpecialMyLanguageFallbackChain();
	}

	public function testExecute() {
		list( $output, ) = $this->executeSpecialPage( '' );
		$this->assertTrue( strpos( $output, 'en - ' ) !== false );
	}

}
