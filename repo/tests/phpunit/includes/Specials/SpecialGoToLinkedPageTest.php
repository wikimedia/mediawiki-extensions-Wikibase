<?php

namespace Wikibase\Repo\Tests\Specials;

use FauxResponse;
use HashSiteStore;
use InvalidArgumentException;
use Site;
use SiteLookup;
use SpecialPageTestBase;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\EntityRedirectTargetLookup;
use Wikibase\Lib\Store\SiteLinkLookup;
use Wikibase\Repo\Specials\SpecialGoToLinkedPage;

/**
 * @covers \Wikibase\Repo\Specials\SpecialGoToLinkedPage
 * @covers \Wikibase\Repo\Specials\SpecialWikibasePage
 *
 * @group Wikibase
 * @group SpecialPage
 * @group WikibaseSpecialPage
 *
 * @license GPL-2.0-or-later
 * @author Jan Zerebecki
 */
class SpecialGoToLinkedPageTest extends SpecialPageTestBase {

	use HtmlAssertionHelpers;

	/** @see \LanguageQqx */
	private const DUMMY_LANGUAGE = 'qqx';

	/**
	 * @return SiteLinkLookup
	 */
	private function getMockSiteLinkLookup() {
		$mock = $this->createMock( SiteLinkLookup::class );

		$mock->method( 'getLinks' )
			->willReturnCallback( function( $itemIds, $siteIds ) {
				$result = [ [ '', 'TestPageName' ] ];
				if ( $siteIds === [ 'dewiki' ] && $itemIds === [ 23 ] ) {
					return $result;
				} else {
					return [];
				}
			} );

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

	private function getEntityRedirectTargetLookup(): EntityRedirectTargetLookup {
		$mock = $this->createMock( EntityRedirectTargetLookup::class );
		$mock->method( 'getRedirectForEntityId' )
			->willReturnCallback( function( ItemId $id ) {
				if ( $id->getSerialization() === 'Q24' ) {
					return new ItemId( 'Q23' );
				} else {
					return null;
				}
			} );

		return $mock;
	}

	/**
	 * @return EntityIdParser
	 */
	private function getEntityIdParser() {
		$mock = $this->createMock( EntityIdParser::class );
		$mock->method( 'parse' )
			->willReturnCallback( function( $itemString ) {
				try {
					return new ItemId( $itemString );
				} catch ( InvalidArgumentException $ex ) {
					throw new EntityIdParsingException();
				}
			} );

		return $mock;
	}

	/**
	 * @return EntityLookup
	 */
	private function getEntityLookup() {
		$mock = $this->createMock( EntityLookup::class );
		$mock->method( 'hasEntity' )
			->willReturnCallback( function( ItemId $itemId ) {
				$id = $itemId->getSerialization();
				return $id === 'Q23' || $id === 'Q24';
			} );

		return $mock;
	}

	/**
	 * @return SpecialGoToLinkedPage
	 */
	protected function newSpecialPage() {
		return new SpecialGoToLinkedPage(
			$this->getMockSiteLookup(),
			$this->getMockSiteLinkLookup(),
			$this->getEntityRedirectTargetLookup(),
			$this->getEntityIdParser(),
			$this->getEntityLookup()
		);
	}

	public function requestWithoutRedirectProvider() {
		return [
			'empty' => [ '', null, '', '', '' ],
			'invalidItemID' => [
				'enwiki/invalid', null, 'enwiki', 'invalid',
				'(wikibase-gotolinkedpage-error-item-id-invalid)',
			],
			'notFound' => [
				'enwiki/Q42', null, 'enwiki', 'Q42',
				'(wikibase-gotolinkedpage-error-item-not-found)',
			],
			'notFound2' => [
				'XXwiki/Q23', null, 'XXwiki', 'Q23',
				'(wikibase-gotolinkedpage-error-page-not-found)',
			],
			'notFound3' => [
				'XXwiki,enwiki,zhwiki/Q23', null, 'XXwiki,enwiki,zhwiki', 'Q23',
				'(wikibase-gotolinkedpage-error-page-not-found)',
			],
		];
	}

	/**
	 * @dataProvider requestWithoutRedirectProvider
	 */
	public function testExecuteWithoutRedirect( $sub, $target, $site, $item, $error ) {
		/** @var FauxResponse $response */
		list( $output, $response ) = $this->executeSpecialPage( $sub, null, self::DUMMY_LANGUAGE );

		$this->assertEquals( $target, $response->getHeader( 'Location' ), 'Redirect' );

		$this->assertHtmlContainsInputWithNameAndValue( $output, 'site', $site );
		$this->assertHtmlContainsInputWithNameAndValue( $output, 'itemid', $item );
		$this->assertHtmlContainsSubmitControl( $output );

		if ( !empty( $error ) ) {
			$this->assertStringContainsString( '<p class="error">' . $error . '</p>', $output,
				'Failed to match error: ' . $error );
		}
	}

	public function requestWithRedirectProvider() {
		return [
			'found' => [ 'dewiki/Q23', 'http://dewiki.com/TestPageName' ],
			'foundEntityRedirect' => [ 'dewiki/Q24', 'http://dewiki.com/TestPageName' ],
			'foundWithSiteIdHack' => [ 'de/Q23', 'http://dewiki.com/TestPageName' ],
			'foundInFallbackChain' => [ 'enwiki, dewiki,fawiki/Q23', 'http://dewiki.com/TestPageName' ],
		];
	}

	/**
	 * @dataProvider requestWithRedirectProvider
	 */
	public function testExecuteWithRedirect( $sub, $target ) {
		/** @var FauxResponse $response */
		list( $output, $response ) = $this->executeSpecialPage( $sub );

		$this->assertEquals( $target, $response->getHeader( 'Location' ), 'Redirect' );
		$this->assertSame( '', $output );
	}

}
