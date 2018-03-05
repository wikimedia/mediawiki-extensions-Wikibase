<?php

namespace Wikibase\Repo\Tests;

use Language;
use OutputPage;
use Title;
use User;
use Wikibase\OutputPageJsConfigBuilder;

/**
 * @covers Wikibase\OutputPageJsConfigBuilder
 *
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class OutputPageJsConfigBuilderTest extends \MediaWikiTestCase {

	public function testBuild() {
		$configBuilder = new OutputPageJsConfigBuilder();

		$configVars = $configBuilder->build(
			$this->getOutputPage(),
			'https://creativecommons.org',
			'CC-0',
			[
				'Q12' => 'wb-badge-goodarticle',
				'Q42' => 'wb-badge-featuredarticle'
			]
		);

		$expected = [
			'wbCopyright' => [
				'version' => 'wikibase-1',
				'messageHtml' =>
					'(wikibase-shortcopyrightwarning: (wikibase-save), ' .
					wfMessage( 'copyrightpage' )->inContentLanguage()->text() .
					', <a rel="nofollow" class="external text" href="https://creativecommons.org">CC-0</a>)'
			],
			'wbBadgeItems' => [
				'Q12' => 'wb-badge-goodarticle',
				'Q42' => 'wb-badge-featuredarticle'
			]
		];

		$this->assertEquals( $expected, $configVars );
	}

	public function testBuildWikibasePublish() {
		$this->setMwGlobals( [ 'wgEditSubmitButtonLabelPublish' => true ] );
		$configBuilder = new OutputPageJsConfigBuilder();

		$configVars = $configBuilder->build(
			$this->getOutputPage(),
			'https://creativecommons.org',
			'CC-0',
			[]
		);

		$expected = [
			'wbCopyright' => [
				'version' => 'wikibase-1',
				'messageHtml' =>
					'(wikibase-shortcopyrightwarning: (wikibase-publish), ' .
					wfMessage( 'copyrightpage' )->inContentLanguage()->text() .
					', <a rel="nofollow" class="external text" href="https://creativecommons.org">CC-0</a>)'
			],
			'wbBadgeItems' => []
		];

		$this->assertEquals( $expected, $configVars );
	}

	/**
	 * @return User
	 */
	public function getUser() {
		$user = $this->getMockBuilder( User::class )
			->disableOriginalConstructor()
			->getMock();

		return $user;
	}

	/**
	 * @return Title
	 */
	private function getTitle() {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		return $title;
	}

	/**
	 * @param bool $isBlocked
	 * @param bool $canEdit
	 *
	 * @return OutputPage
	 */
	private function getOutputPage() {
		$out = $this->getMockBuilder( OutputPage::class )
			->disableOriginalConstructor()
			->getMock();

		$user = $this->getUser();

		$out->expects( $this->any() )
			->method( 'getUser' )
			->will( $this->returnCallback( function() use ( $user ) {
				return $user;
			} ) );

		$out->expects( $this->any() )
			->method( 'getLanguage' )
			->will( $this->returnCallback( function() {
				return Language::factory( 'qqx' );
			} ) );

		$title = $this->getTitle();

		$out->expects( $this->any() )
			->method( 'getTitle' )
			->will( $this->returnCallback( function() use( $title ) {
				return $title;
			} ) );

		return $out;
	}

}
