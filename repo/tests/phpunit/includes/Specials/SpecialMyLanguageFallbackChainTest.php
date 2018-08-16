<?php

namespace Wikibase\Repo\Tests\Specials;

use SpecialPageTestBase;
use Wikibase\Repo\Specials\SpecialMyLanguageFallbackChain;
use Wikibase\Repo\Tests\WikibaseRepoAccess;

/**
 * @covers \Wikibase\Repo\Specials\SpecialMyLanguageFallbackChain
 *
 * @group Wikibase
 * @group SpecialPage
 * @group WikibaseSpecialPage
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Liangent < liangent@gmail.com >
 */
class SpecialMyLanguageFallbackChainTest extends SpecialPageTestBase {

	use WikibaseRepoAccess;

	protected function newSpecialPage() {
		return new SpecialMyLanguageFallbackChain(
			$this->getWikibaseRepo()->getLanguageFallbackChainFactory()
		);
	}

	public function testExecute() {
		global $wgLanguageCode;

		list( $output, ) = $this->executeSpecialPage( '' );

		$expectedString = $wgLanguageCode . ' - ';
		$this->assertInternalType( 'integer', stripos( $output, $expectedString ),
			"Cannot find '$expectedString' in the list of fallback languages in '$output'." );
	}

}
