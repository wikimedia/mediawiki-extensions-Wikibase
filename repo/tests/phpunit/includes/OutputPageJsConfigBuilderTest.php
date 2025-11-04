<?php

namespace Wikibase\Repo\Tests;

use MediaWiki\MainConfigNames;
use MediaWiki\Output\OutputPage;
use MediaWiki\Title\Title;
use MediaWiki\User\User;
use MediaWikiIntegrationTestCase;
use Wikibase\Repo\OutputPageJsConfigBuilder;

/**
 * @covers \Wikibase\Repo\OutputPageJsConfigBuilder
 *
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class OutputPageJsConfigBuilderTest extends MediaWikiIntegrationTestCase {

	/** @dataProvider provideBooleans */
	public function testBuild( bool $publish, bool $taintedRefs, bool $federationEnabled ) {
		$this->overrideConfigValue( MainConfigNames::EditSubmitButtonLabelPublish, $publish );
		$configBuilder = new OutputPageJsConfigBuilder();

		$url = 'https://creativecommons.org';
		$configVars = $configBuilder->build(
			$this->getOutputPage(),
			$url,
			'CC-0',
			[
				'Q12' => 'wb-badge-goodarticle',
				'Q42' => 'wb-badge-featuredarticle',
			],
			250,
			$taintedRefs,
			$federationEnabled
		);

		$expectedKey = $publish ? 'wikibase-publish' : 'wikibase-save';
		$expected = [
			'wbCopyright' => [
				'version' => 'wikibase-1',
				'messageHtml' =>
					"(wikibase-shortcopyrightwarning: ($expectedKey), " .
					wfMessage( 'copyrightpage' )->inContentLanguage()->text() .
					', <a rel="nofollow" class="external free" href="' . $url .
					'">' . $url . '</a>, CC-0)',
			],
			'wbBadgeItems' => [
				'Q12' => 'wb-badge-goodarticle',
				'Q42' => 'wb-badge-featuredarticle',
			],
			'wbMultiLingualStringLimit' => 250,
			'wbTaintedReferencesEnabled' => $taintedRefs,
			'wbFederatedValuesEnabled' => $federationEnabled
		];

		$this->assertEquals( $expected, $configVars );
	}

	public static function provideBooleans(): iterable {
		yield 'save, no tainted, federation off' => [ false, false, false ];
		yield 'save, tainted, federation on'     => [ false, true,  true  ];
		yield 'publish, no tainted, federation on' => [ true, false, true ];
		yield 'publish, tainted, federation off'   => [ true, true,  false ];
	}

	/**
	 * @return OutputPage
	 */
	private function getOutputPage() {
		$out = $this->createMock( OutputPage::class );
		$out->method( 'getUser' )->willReturn( $this->createMock( User::class ) );
		$out->method( 'getLanguage' )->willReturn( $this->getServiceContainer()->getLanguageFactory()->getLanguage( 'qqx' ) );
		$out->method( 'getTitle' )->willReturn( $this->createMock( Title::class ) );
		return $out;
	}

}
