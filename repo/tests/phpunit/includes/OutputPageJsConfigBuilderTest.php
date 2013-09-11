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
class OutputPageJsConfigBuilderTest extends \MediaWikiTestCase {

	/**
	 * @dataProvider buildProvider
	 */
	public function testBuild( $isBlocked, $canEdit ) {
		$entityTitleLookup = $this->getEntityTitleLookupMock( $canEdit );

		$configBuilder = new OutputPageJsConfigBuilder( $entityTitleLookup );

		$configVars = $configBuilder->build(
			$this->getUser( $isBlocked ),
			Language::factory( 'qqx' ),
			new ItemId( 'Q80' ),
			'https://creativecommons.org',
			'CC-0'
		);

		$expected = array(
			'wbUserIsBlocked' => $isBlocked,
			'wbUserCanEdit' => $canEdit,
			'wbCopyright' => array(
				'version' => 'wikibase-1',
				'messageHtml' => '(wikibase-shortcopyrightwarning: (wikibase-save), (copyrightpage)'
					. ', <a rel="nofollow" class="external text" href="https://creativecommons.org">CC-0</a>)'
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

	private function getUser( $isBlocked ) {
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

	/**
	 * @return EntityTitleLookup
	 */
	private function getEntityTitleLookupMock( $canEdit ) {
		$title = $this->getMockBuilder( 'Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'userCan' )
			->will( $this->returnCallback( function() use ( $canEdit ) {
				return $canEdit;
			} ) );

		$lookup = $this->getMockBuilder( 'Wikibase\EntityTitleLookup' )
			->disableOriginalConstructor()
			->getMock();

		$lookup->expects( $this->any() )
			->method( 'getTitleForId' )
			->will( $this->returnCallback( function() use ( $title ) {
				return $title;
			} ) );

		return $lookup;
	}

}
