<?php

namespace Wikibase\Repo\Tests\Specials;

use HashSiteStore;
use Site;
use SiteLookup;
use SpecialPageTestBase;
use Title;
use WebResponse;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Store\SiteLinkLookup;
use Wikibase\Repo\SiteLinkTargetProvider;
use Wikibase\Repo\Specials\SpecialItemByTitle;

/**
 * @covers Wikibase\Repo\Specials\SpecialItemByTitle
 * @covers Wikibase\Repo\Specials\SpecialWikibasePage
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group SpecialPage
 * @group WikibaseSpecialPage
 *
 * @group Database
 *        ^---- needed because we rely on Title objects internally
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class SpecialItemByTitleTest extends SpecialPageTestBase {

	/**
	 * @return EntityTitleLookup
	 */
	private function getMockTitleLookup() {
		$mock = $this->getMock( EntityTitleLookup::class );
		$mock->expects( $this->any() )
			->method( 'getTitleForId' )
			->will( $this->returnCallback( function( EntityId $id ) {
				return Title::makeTitle( NS_MAIN, $id->getSerialization() );
			} ) );

		return $mock;
	}

	/**
	 * @return LanguageNameLookup
	 */
	private function getMockLanguageNameLookup() {
		$mock = $this->getMock( LanguageNameLookup::class );
		$mock->expects( $this->any() )
			->method( 'getName' )
			->will( $this->returnValue( '<LANG>' ) );

		return $mock;
	}

	/**
	 * @return SiteLinkLookup
	 */
	private function getMockSiteLinkLookup() {
		$itemId = new ItemId( 'Q123' );

		$mock = $this->getMock( SiteLinkLookup::class );

		$mock->expects( $this->any() )
			->method( 'getItemIdForLink' )
			->will( $this->returnCallback( function( $siteId, $pageName ) use ( $itemId ) {
				return $siteId === 'dewiki' ? $itemId : null;
			} ) );

		return $mock;
	}

	/**
	 * @return SiteLookup
	 */
	private function getMockSiteLookup() {
		$dewiki = new Site();
		$dewiki->setGlobalId( 'dewiki' );
		$dewiki->setLinkPath( 'http://dewiki.com/$1' );

		return new HashSiteStore( [ $dewiki ] );
	}

	/**
	 * @return SpecialItemByTitle
	 */
	protected function newSpecialPage() {
		$page = new SpecialItemByTitle();

		$page->initSettings(
			array( 'wikipedia' )
		);

		$siteLookup = $this->getMockSiteLookup();

		$siteLinkTargetProvider = new SiteLinkTargetProvider( $siteLookup, array() );

		$page->initServices(
			$this->getMockTitleLookup(),
			$this->getMockLanguageNameLookup(),
			$siteLookup,
			$this->getMockSiteLinkLookup(),
			$siteLinkTargetProvider
		);

		return $page;
	}

	public function requestProvider() {
		$cases = array();
		$matchers = array();

		$matchers['site'] = array(
			'tag' => 'div',
			'attributes' => array(
				'id' => 'wb-itembytitle-sitename',
			),
			'child' => array(
				'tag' => 'input',
				'attributes' => array(
					'name' => 'site',
				)
			) );
		$matchers['page'] = array(
			'tag' => 'div',
			'attributes' => array(
				'id' => 'pagename',
			),
			'child' => array(
				'tag' => 'input',
				'attributes' => array(
					'name' => 'page',
				)
			) );
		$matchers['submit'] = array(
			'tag' => 'div',
			'attributes' => array(
				'id' => 'wb-itembytitle-submit',
			),
			'child' => array(
				'tag' => 'button',
				'attributes' => array(
					'type' => 'submit',
					'name' => '',
				)
			) );

		$cases['empty'] = array( '', null, $matchers );

		// enwiki/NotFound  (mock returns null for everything but dewiki)
		$matchers['site']['child'][0]['attributes']['value'] = 'enwiki';
		$matchers['page']['child'][0]['attributes']['value'] = 'NotFound';

		$cases['enwiki/NotFound'] = array( 'enwiki/NotFound', null, $matchers );

		// dewiki/Gefunden (mock returns Q123 for dewiki)
		$matchers = array();

		$cases['dewiki/Gefunden'] = array( 'dewiki/Gefunden', 'Q123', $matchers );

		return $cases;
	}

	/**
	 * @dataProvider requestProvider
	 */
	public function testExecute( $sub, $target, array $matchers ) {
		/* @var WebResponse $response */
		list( $output, $response ) = $this->executeSpecialPage( $sub );

		if ( $target !== null ) {
			$target = Title::newFromText( $target )->getFullURL();
			$expected = wfExpandUrl( $target, PROTO_CURRENT );
			$this->assertEquals( $expected, $response->getHeader( 'Location' ), 'Redirect' );
		}

		foreach ( $matchers as $key => $matcher ) {
			$this->assertTag( $matcher, $output, "Failed to match html output with tag '{$key}''" );
		}
	}

}
