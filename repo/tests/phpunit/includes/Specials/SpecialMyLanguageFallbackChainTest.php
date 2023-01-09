<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Specials;

use SpecialPageTestBase;
use Wikibase\Repo\Specials\SpecialMyLanguageFallbackChain;
use Wikibase\Repo\WikibaseRepo;

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

	protected function newSpecialPage(): SpecialMyLanguageFallbackChain {
		$services = $this->getServiceContainer();
		return new SpecialMyLanguageFallbackChain(
			$services->getLanguageFactory(),
			$services->getLanguageNameUtils(),
			WikibaseRepo::getLanguageFallbackChainFactory( $services )
		);
	}

	public function testExecute() {
		global $wgLanguageCode;

		list( $output, ) = $this->executeSpecialPage( '', null, $wgLanguageCode );

		$expectedString = $wgLanguageCode . ' - ';
		$this->assertIsInt( stripos( $output, $expectedString ),
			"Cannot find '$expectedString' in the list of fallback languages in '$output'." );
	}

}
