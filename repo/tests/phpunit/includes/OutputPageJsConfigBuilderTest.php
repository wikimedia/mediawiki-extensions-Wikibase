<?php

namespace Wikibase\Repo\Tests;

use Language;
use MediaWikiIntegrationTestCase;
use OutputPage;
use Title;
use User;
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
	public function testBuild( bool $publish, bool $taintedRefs ) {
		$this->setMwGlobals( [ 'wgEditSubmitButtonLabelPublish' => $publish ] );
		$configBuilder = new OutputPageJsConfigBuilder();

		$url = 'https://creativecommons.org';
		$configVars = $configBuilder->build(
			$this->getOutputPage(),
			$url,
			'CC-0',
			[
				'Q12' => 'wb-badge-goodarticle',
				'Q42' => 'wb-badge-featuredarticle'
			],
			250,
			$taintedRefs
		);

		$expectedKey = $publish ? 'wikibase-publish' : 'wikibase-save';
		$expected = [
			'wbCopyright' => [
				'version' => 'wikibase-1',
				'messageHtml' =>
					"(wikibase-shortcopyrightwarning: ($expectedKey), " .
					wfMessage( 'copyrightpage' )->inContentLanguage()->text() .
					', <a rel="nofollow" class="external free" href="' . $url .
					'">' . $url . '</a>, CC-0)'
			],
			'wbBadgeItems' => [
				'Q12' => 'wb-badge-goodarticle',
				'Q42' => 'wb-badge-featuredarticle'
			],
			'wbMultiLingualStringLimit' => 250,
			'wbTaintedReferencesEnabled' => $taintedRefs,
		];

		$this->assertEquals( $expected, $configVars );
	}

	public function provideBooleans(): iterable {
		yield 'save, no tainted refs' => [ false, false ];
		yield 'save, tainted refs' => [ false, true ];
		yield 'publish, no tainted refs' => [ true, false ];
		yield 'publish, tainted refs' => [ true, true ];
	}

	/**
	 * @return User
	 */
	public function getUser() {
		return $this->createMock( User::class );
	}

	/**
	 * @return Title
	 */
	private function getTitle() {
		return $this->createMock( Title::class );
	}

	/**
	 * @return OutputPage
	 */
	private function getOutputPage() {
		$out = $this->getMockBuilder( OutputPage::class )
			->disableOriginalConstructor()
			->getMock();

		$user = $this->getUser();

		$out->method( 'getUser' )
			->willReturnCallback( function() use ( $user ) {
				return $user;
			} );

		$out->method( 'getLanguage' )
			->willReturnCallback( function() {
				return Language::factory( 'qqx' );
			} );

		$title = $this->getTitle();

		$out->method( 'getTitle' )
			->willReturnCallback( function() use( $title ) {
				return $title;
			} );

		return $out;
	}

}
