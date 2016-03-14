<?php

namespace Wikibase\Test;

use Language;
use OutputPage;
use Title;
use User;
use Wikibase\OutputPageJsConfigBuilder;

/**
 * @covers Wikibase\OutputPageJsConfigBuilder
 *
 * @since 0.5
 *
 * @group WikibaseRepo
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class OutputPageJsConfigBuilderTest extends \MediaWikiTestCase {

	/**
	 * @dataProvider buildProvider
	 */
	public function testBuild( $isBlocked, $canEdit ) {
		$configBuilder = new OutputPageJsConfigBuilder();

		$configVars = $configBuilder->build(
			$this->getOutputPage( $isBlocked, $canEdit ),
			'https://creativecommons.org',
			'CC-0',
			array(
				'Q12' => 'wb-badge-goodarticle',
				'Q42' => 'wb-badge-featuredarticle'
			)
		);

		$expected = array(
			'wbUserIsBlocked' => $isBlocked,
			'wbUserCanEdit' => $canEdit,
			'wbCopyright' => array(
				'version' => 'wikibase-1',
				'messageHtml' =>
					'(wikibase-shortcopyrightwarning: (wikibase-save), ' .
					wfMessage( 'copyrightpage' )->inContentLanguage()->text() .
					', <a rel="nofollow" class="external text" href="https://creativecommons.org">CC-0</a>)'
			),
			'wbBadgeItems' => array(
				'Q12' => 'wb-badge-goodarticle',
				'Q42' => 'wb-badge-featuredarticle'
			)
		);

		$this->assertEquals( $expected, $configVars );
	}

	public function buildProvider() {
		return array(
			array( true, true ),
			array( true, false ),
			array( false, false ),
			array( false, true )
		);
	}

	/**
	 * @param bool $isBlocked
	 *
	 * @return User
	 */
	public function getUser( $isBlocked ) {
		$user = $this->getMockBuilder( User::class )
			->disableOriginalConstructor()
			->getMock();

		$user->expects( $this->any() )
			->method( 'isBlockedFrom' )
			->will( $this->returnCallback( function() use ( $isBlocked ) {
				return $isBlocked;
			} ) );

		return $user;
	}

	/**
	 * @param bool $canEdit
	 *
	 * @return Title
	 */
	private function getTitle( $canEdit ) {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'userCan' )
			->will( $this->returnCallback( function() use ( $canEdit ) {
				return $canEdit;
			} ) );

		return $title;
	}

	/**
	 * @param bool $isBlocked
	 * @param bool $canEdit
	 *
	 * @return OutputPage
	 */
	private function getOutputPage( $isBlocked, $canEdit ) {
		$out = $this->getMockBuilder( OutputPage::class )
			->disableOriginalConstructor()
			->getMock();

		$user = $this->getUser( $isBlocked );

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

		$title = $this->getTitle( $canEdit );

		$out->expects( $this->any() )
			->method( 'getTitle' )
			->will( $this->returnCallback( function() use( $title ) {
				return $title;
			} ) );

		return $out;
	}

}
