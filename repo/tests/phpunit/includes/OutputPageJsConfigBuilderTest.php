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

	public function testBuild() {
		$this->setMwGlobals( [ 'wgEditSubmitButtonLabelPublish' => false ] );
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
			false
		);

		$expected = [
			'wbCopyright' => [
				'version' => 'wikibase-1',
				'messageHtml' =>
					'(wikibase-shortcopyrightwarning: (wikibase-save), ' .
					wfMessage( 'copyrightpage' )->inContentLanguage()->text() .
					', <a rel="nofollow" class="external free" href="' . $url .
					'">' . $url . '</a>, CC-0)'
			],
			'wbBadgeItems' => [
				'Q12' => 'wb-badge-goodarticle',
				'Q42' => 'wb-badge-featuredarticle'
			],
			'wbMultiLingualStringLimit' => 250,
			'wbTaintedReferencesEnabled' => false,
		];

		$this->assertEquals( $expected, $configVars );
	}

	public function testBuildWikibasePublish() {
		$this->setMwGlobals( [ 'wgEditSubmitButtonLabelPublish' => true ] );
		$configBuilder = new OutputPageJsConfigBuilder();

		$url = 'https://creativecommons.org';
		$configVars = $configBuilder->build(
			$this->getOutputPage(),
			$url,
			'CC-0',
			[],
			0,
			true
		);

		$expected = [
			'wbCopyright' => [
				'version' => 'wikibase-1',
				'messageHtml' =>
					'(wikibase-shortcopyrightwarning: (wikibase-publish), ' .
					wfMessage( 'copyrightpage' )->inContentLanguage()->text() .
					', <a rel="nofollow" class="external free" href="' . $url .
					'">' . $url . '</a>, CC-0)'
			],
			'wbBadgeItems' => [],
			'wbMultiLingualStringLimit' => 0,
			'wbTaintedReferencesEnabled' => true,
		];

		$this->assertEquals( $expected, $configVars );
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
