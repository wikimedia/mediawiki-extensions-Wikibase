<?php

namespace Wikibase\Client\Tests\Unit\DataAccess\Scribunto;

use Wikibase\Client\DataAccess\Scribunto\WikibaseLanguageDependentLuaBindings;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Term\TermFallback;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookup;

/**
 * @covers \Wikibase\Client\DataAccess\Scribunto\WikibaseLanguageDependentLuaBindings
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseScribunto
 *
 * @license GPL-2.0-or-later
 * @author Jens Ohlig < jens.ohlig@wikimedia.de >
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Marius Hoch < hoo@online.de >
 */
class WikibaseLanguageDependentLuaBindingsTest extends \PHPUnit\Framework\TestCase {

	public function testConstructor() {
		$wikibaseLuaBindings = $this->getWikibaseLanguageDependentLuaBindings(
			$this->getLabelDescriptionLookup()
		);

		$this->assertInstanceOf( WikibaseLanguageDependentLuaBindings::class, $wikibaseLuaBindings );
	}

	/**
	 * @param LanguageFallbackLabelDescriptionLookup $labelDescriptionLookup
	 *
	 * @return WikibaseLanguageDependentLuaBindings
	 */
	private function getWikibaseLanguageDependentLuaBindings(
		LanguageFallbackLabelDescriptionLookup $labelDescriptionLookup
	) {
		return new WikibaseLanguageDependentLuaBindings(
			new ItemIdParser(),
			$labelDescriptionLookup
		);
	}

	/**
	 * @return LanguageFallbackLabelDescriptionLookup
	 */
	private function getLabelDescriptionLookup() {
		$labelDescriptionLookup = $this->createMock( LanguageFallbackLabelDescriptionLookup::class );

		$labelDescriptionLookup->method( 'getLabel' )
			->willReturn(
				new TermFallback( 'ar', 'LabelString', 'lang-code', null )
			);

		$labelDescriptionLookup->method( 'getDescription' )
			->willReturn(
				new TermFallback( 'ar', 'DescriptionString', 'lang-code', null )
			);

		return $labelDescriptionLookup;
	}

	public function getLabelProvider() {
		return [
			[ [ 'LabelString', 'lang-code' ], 'Q123' ],
			[ [ null, null ], 'DoesntExist' ],
		];
	}

	/**
	 * @dataProvider getLabelProvider
	 *
	 * @param string $expected
	 * @param string $itemId
	 */
	public function testGetLabel( $expected, $itemId ) {
		$wikibaseLuaBindings = $this->getWikibaseLanguageDependentLuaBindings(
			$this->getLabelDescriptionLookup()
		);

		$this->assertSame( $expected, $wikibaseLuaBindings->getLabel( $itemId ) );
	}

	public function getDescriptionProvider() {
		return [
			[ [ 'DescriptionString', 'lang-code' ], 'Q123' ],
			[ [ null, null ], 'DoesntExist' ],
		];
	}

	/**
	 * @dataProvider getDescriptionProvider
	 *
	 * @param string $expected
	 * @param string $itemId
	 */
	public function testGetDescription( $expected, $itemId ) {
		$wikibaseLuaBindings = $this->getWikibaseLanguageDependentLuaBindings(
			$this->getLabelDescriptionLookup()
		);

		$this->assertSame( $expected, $wikibaseLuaBindings->getDescription( $itemId ) );
	}

}
