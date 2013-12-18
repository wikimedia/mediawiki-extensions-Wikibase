<?php

namespace Wikibase\Test;

use Wikibase\Repo\Specials\SpecialMyLanguageFallbackChain;

/**
 * @covers Wikibase\Repo\Specials\SpecialMyLanguageFallbackChain
 *
 * @since 0.4
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group SpecialPage
 * @group WikibaseSpecialPage
 *
 * @licence GNU GPL v2+
 */
class SpecialMyLanguageFallbackChainTest extends SpecialPageTestBase {

	protected function newSpecialPage() {
		return new SpecialMyLanguageFallbackChain();
	}

	public function testExecute() {
		list( $output, ) = $this->executeSpecialPage( '' );
		$this->assertTrue( strpos( $output, 'en - ' ) !== false );
	}

}
