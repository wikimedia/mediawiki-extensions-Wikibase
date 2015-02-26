<?php

namespace Wikibase\Test;

use Wikibase\Repo\Specials\SpecialMyLanguageFallbackChain;

/**
 * @covers Wikibase\Repo\Specials\SpecialMyLanguageFallbackChain
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group SpecialPage
 * @group WikibaseSpecialPage
 * @group Database
 *
 * @licence GNU GPL v2+
 */
class SpecialMyLanguageFallbackChainTest extends SpecialPageTestBase {

	protected function newSpecialPage() {
		return new SpecialMyLanguageFallbackChain();
	}

	public function testExecute() {
		global $wgLanguageCode;
		list( $output, ) = $this->executeSpecialPage( '' );

		$expectedString = $wgLanguageCode . ' - ';
		$this->assertInternalType( 'integer', stripos( $output, $expectedString ),
			"Cannot find '$expectedString' in the list of fallback languages in '$output'." );
	}

}
