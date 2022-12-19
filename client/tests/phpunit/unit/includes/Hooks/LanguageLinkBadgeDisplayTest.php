<?php

namespace Wikibase\Client\Tests\Unit\Hooks;

use MediaWiki\MediaWikiServices;
use OutputPage;
use ParserOutput;
use RequestContext;
use Title;
use Wikibase\Client\Hooks\LanguageLinkBadgeDisplay;
use Wikibase\Client\Hooks\SidebarLinkBadgeDisplay;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\Term\Term;

/**
 * @covers \Wikibase\Client\Hooks\LanguageLinkBadgeDisplay
 *
 * @group WikibaseClient
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class LanguageLinkBadgeDisplayTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @return LanguageLinkBadgeDisplay
	 */
	private function getLanguageLinkBadgeDisplay() {
		$labelLookup = $this->createMock( LabelDescriptionLookup::class );

		$labelLookup->method( 'getLabel' )
			->willReturnCallback( function( EntityId $entityId ) {
				switch ( $entityId->getSerialization() ) {
					case 'Q3':
						return new Term( 'de', 'Lesenswerter Artikel' );
					case 'Q4':
						return new Term( 'de', 'Exzellenter Artikel' );
					default:
						return null;
				}
			} );

		$badgeClassNames = [ 'Q4' => 'foo', 'Q3' => 'bar' ];

		$sidebarLinkBadgeDisplay = new SidebarLinkBadgeDisplay( $labelLookup,
			$badgeClassNames,
			MediaWikiServices::getInstance()->getLanguageFactory()->getLanguage( 'de' )
		);
		return new LanguageLinkBadgeDisplay( $sidebarLinkBadgeDisplay );
	}

	/**
	 * @dataProvider attachBadgesToOutputProvider
	 */
	public function testAttachBadgesToOutput( array $expected, array $languageLinks ) {
		$languageLinkBadgeDisplay = $this->getLanguageLinkBadgeDisplay();
		$parserOutput = new ParserOutput();

		$languageLinkBadgeDisplay->attachBadgesToOutput( $languageLinks, $parserOutput );

		$this->assertEquals( $expected, $parserOutput->getExtensionData( 'wikibase_badges' ) );
	}

	public function attachBadgesToOutputProvider() {
		$q2 = new ItemId( 'Q2' );
		$q3 = new ItemId( 'Q3' );
		$q4 = new ItemId( 'Q4' );

		$link0 = new SiteLink( 'jawiki', 'Bah' );
		$link1 = new SiteLink( 'dewiki', 'Foo', [ $q3, $q2 ] );
		$link2 = new SiteLink( 'enwiki', 'Bar', [ $q3, $q4 ] );

		$badge1 = [
			'class' => 'badge-Q3 bar badge-Q2',
			'label' => 'Lesenswerter Artikel',
		];
		$badge2 = [
			'class' => 'badge-Q3 bar badge-Q4 foo',
			'label' => 'Lesenswerter Artikel, Exzellenter Artikel',
		];

		return [
			'empty' => [ [], [] ],
			'no badges' => [ [], [ $link0 ] ],
			'some badges' => [
				[ 'dewiki' => $badge1, 'enwiki' => $badge2 ],
				[ 'jawiki' => $link0, 'dewiki' => $link1, 'enwiki' => $link2 ],
			],
		];
	}

	public function testApplyBadges() {
		$badges = [
			'en' => [
				'class' => 'badge-Q3',
				'label' => 'Lesenswerter Artikel',
			],
		];

		$link = [
			'href' => 'http://acme.com',
			'class' => 'foo',
		];

		$expected = [
			'href' => 'http://acme.com',
			'class' => 'foo badge-Q3',
			'itemtitle' => 'Lesenswerter Artikel',
		];

		$languageLinkTitle = Title::makeTitle( NS_MAIN, 'Test', '', 'en' );

		$context = new RequestContext();
		$output = new OutputPage( $context );
		$output->setProperty( 'wikibase_badges', $badges );

		$languageLinkBadgeDisplay = $this->getLanguageLinkBadgeDisplay();
		$languageLinkBadgeDisplay->applyBadges( $link, $languageLinkTitle, $output );

		$this->assertEquals( $expected, $link );
	}

}
