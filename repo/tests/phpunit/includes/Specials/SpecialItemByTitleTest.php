<?php

namespace Wikibase\Repo\Tests\Specials;

use HashSiteStore;
use Prophecy\Argument;
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

	use HtmlAssertionHelpers;

	const EXISTING_WIKI = 'dewiki';
	const EXISTING_PAGE = 'Gefunden';
	const EXISTING_ITEM_ID = 'Q123';

	/**
	 * @return EntityTitleLookup|\PHPUnit_Framework_MockObject_MockObject
	 */
	private function getMockTitleLookup() {
		$mock = $this->getMock( EntityTitleLookup::class );

		$mock->method( 'getTitleForId' )
			->will( $this->returnCallback( function( EntityId $id ) {
				return Title::makeTitle( NS_MAIN, $id->getSerialization() );
			} ) );

		return $mock;
	}

	/**
	 * @return LanguageNameLookup|\PHPUnit_Framework_MockObject_MockObject
	 */
	private function getMockLanguageNameLookup() {
		$mock = $this->getMock( LanguageNameLookup::class );

		$mock->method( 'getName' )
			->will( $this->returnValue( '<LANG>' ) );

		return $mock;
	}

	/**
	 * @return SiteLinkLookup
	 */
	private function getMockSiteLinkLookup() {
		$siteLinkLookup = $this->prophesize( SiteLinkLookup::class );

		$siteLinkLookup->getItemIdForLink( self::EXISTING_WIKI, self::EXISTING_PAGE )
			->willReturn( new ItemId( self::EXISTING_ITEM_ID ) );

		$siteLinkLookup->getItemIdForLink( Argument::any(), Argument::any() )->willReturn( null );

		return $siteLinkLookup->reveal();
	}

	/**
	 * @return SiteLookup
	 */
	private function getMockSiteLookup() {
		$dewiki = new Site();
		$dewiki->setGlobalId( self::EXISTING_WIKI );
		$dewiki->setLinkPath( 'http://any-domain.com/$1' );

		return new HashSiteStore( [ $dewiki ] );
	}

	/**
	 * @return SpecialItemByTitle
	 */
	protected function newSpecialPage() {

		$siteLookup = $this->getMockSiteLookup();

		$siteLinkTargetProvider = new SiteLinkTargetProvider( $siteLookup, [] );

		$page = new SpecialItemByTitle(
			$this->getMockTitleLookup(),
			$this->getMockLanguageNameLookup(),
			$siteLookup,
			$this->getMockSiteLinkLookup(),
			$siteLinkTargetProvider,
			[ 'wikipedia' ]
		);

		return $page;
	}

	public function testAllNeededFieldsArePresent_WhenRendered() {

		list( $output ) = $this->executeSpecialPage();

		$this->assertHtmlContainsInputWithName( $output, 'site' );
		$this->assertHtmlContainsInputWithName( $output, 'page' );
		$this->assertHtmlContainsSubmitControl( $output );
	}

	public function testSiteAndPageFieldsAreFilledIn_WhenRenderedWithSubpageReferingToNonexistentTitle() {
		$wiki = 'non_existent_wiki';
		$page = 'AnyPage';
		list( $output ) = $this->executeSpecialPage( $wiki . '/' . $page );

		$this->assertHtmlContainsInputWithNameAndValue( $output, 'site', $wiki );
		$this->assertHtmlContainsInputWithNameAndValue( $output, 'page', $page );
	}

	public function testRedirectsToCorrespondingItem_WhenGivenSubPageReferencesExistingPage() {

		$itemId = self::EXISTING_ITEM_ID;
		$subPage = self::EXISTING_WIKI . '/' . self::EXISTING_PAGE;

		/* @var WebResponse $response */
		list( , $response ) = $this->executeSpecialPage( $subPage );

		$itemUrl = Title::newFromText( $itemId )->getFullURL();
		$expectedUrl = wfExpandUrl( $itemUrl, PROTO_CURRENT );
		$this->assertEquals( $expectedUrl, $response->getHeader( 'Location' ), 'Redirect' );
	}

}
