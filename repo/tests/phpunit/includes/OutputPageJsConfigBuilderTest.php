<?php

namespace Wikibase\Test;

use Language;
use Title;
use User;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\OutputPageJsConfigBuilder;

/**
 * @covers Wikibase\OutputPageJsConfigBuilder
 *
 * @since 0.5
 *
 * @group WikibaseRepo
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class OutputPageJsConfigBuilderTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider buildProvider
	 */
	public function testBuild( $isBlocked, $canEdit ) {
		$configBuilder = new OutputPageJsConfigBuilder();

		$configVars = $configBuilder->build(
			$this->getOutputPage( $isBlocked, $canEdit ),
			'https://creativecommons.org',
			'CC-0',
			true
		);

		$expected = array(
			'wbUserIsBlocked' => $isBlocked,
			'wbUserCanEdit' => $canEdit,
			'wbCopyright' => array(
				'version' => 'wikibase-1',
				'messageHtml' => '(wikibase-shortcopyrightwarning: (wikibase-save), (copyrightpage)'
					. ', <a rel="nofollow" class="external text" href="https://creativecommons.org">CC-0</a>)'
			),
			'wbExperimentalFeatures' => true
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

	public function getUser( $isBlocked ) {
		$user = $this->getMockBuilder( 'User' )
			->disableOriginalConstructor()
			->getMock();

		$user->expects( $this->any() )
			->method( 'isBlockedFrom' )
			->will( $this->returnCallback( function() use ( $isBlocked ) {
				return $isBlocked;
			} ) );

		return $user;
	}

	private function getTitle( $canEdit ) {
		$title = $this->getMockBuilder( 'Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'userCan' )
			->will( $this->returnCallback( function() use ( $canEdit ) {
				return $canEdit;
			} ) );

		return $title;
	}

	private function getOutputPage( $isBlocked, $canEdit ) {
		$out = $this->getMockBuilder( 'OutputPage' )
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
