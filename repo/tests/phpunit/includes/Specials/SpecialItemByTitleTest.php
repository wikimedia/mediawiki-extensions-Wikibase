<?php

namespace Wikibase\Repo\Tests\Specials;

use HashSiteStore;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;
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
 * @covers \Wikibase\Repo\Specials\SpecialItemByTitle
 * @covers \Wikibase\Repo\Specials\SpecialWikibasePage
 *
 * @group Wikibase
 * @group SpecialPage
 * @group WikibaseSpecialPage
 *
 * @group Database
 *        ^---- needed because we rely on Title objects internally
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class SpecialItemByTitleTest extends SpecialPageTestBase {

	use HtmlAssertionHelpers;

	private const EXISTING_WIKI = 'dewiki';
	private const EXISTING_PAGE = 'Gefunden';
	private const EXISTING_ITEM_ID = 'Q123';

	/**
	 * @return EntityTitleLookup|MockObject
	 */
	private function getMockTitleLookup() {
		$mock = $this->createMock( EntityTitleLookup::class );

		$mock->method( 'getTitleForId' )
			->willReturnCallback( function( EntityId $id ) {
				return Title::makeTitle( NS_MAIN, $id->getSerialization() );
			} );

		return $mock;
	}

	/**
	 * @return LanguageNameLookup|MockObject
	 */
	private function getMockLanguageNameLookup() {
		$mock = $this->createMock( LanguageNameLookup::class );

		$mock->method( 'getName' )
			->willReturn( '<LANG>' );

		return $mock;
	}

	/**
	 * @return SiteLinkLookup
	 */
	private function getMockSiteLinkLookup() {
		$siteLinkLookup = $this->createMock( SiteLinkLookup::class );

		$siteLinkLookup->method( 'getItemIdForLink' )
			->willReturnCallback( function ( $wiki, $page ) {
				if ( $wiki === self::EXISTING_WIKI && $page === self::EXISTING_PAGE ) {
					return new ItemId( self::EXISTING_ITEM_ID );
				} else {
					return null;
				}
			} );

		return $siteLinkLookup;
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
			new NullLogger(),
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

		/** @var WebResponse $response */
		list( , $response ) = $this->executeSpecialPage( $subPage );

		$itemUrl = Title::newFromTextThrow( $itemId )->getFullURL();
		$expectedUrl = wfExpandUrl( $itemUrl, PROTO_CURRENT );
		$this->assertEquals( $expectedUrl, $response->getHeader( 'Location' ), 'Redirect' );
	}

}
