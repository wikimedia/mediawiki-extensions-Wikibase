<?php

namespace Wikibase\Client\Tests\DataAccess\Scribunto;

use PHPUnit_Framework_TestCase;
use Wikibase\Client\DataAccess\Scribunto\WikibaseLanguageDependentLuaBindings;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookup;
use Wikibase\DataModel\Term\TermFallback;

/**
 * @covers Wikibase\Client\DataAccess\Scribunto\WikibaseLanguageDependentLuaBindings
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseScribunto
 *
 * @license GPL-2.0+
 * @author Jens Ohlig < jens.ohlig@wikimedia.de >
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Marius Hoch < hoo@online.de >
 */
class WikibaseLanguageDependentLuaBindingsTest extends PHPUnit_Framework_TestCase {

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
		$labelDescriptionLookup = $this->getMockBuilder( LanguageFallbackLabelDescriptionLookup::class )
			->disableOriginalConstructor()
			->getMock();

		$labelDescriptionLookup->expects( $this->any() )
			->method( 'getLabel' )
			->will( $this->returnValue(
				new TermFallback( 'ar', 'LabelString', 'lang-code', null )
			) );

		$labelDescriptionLookup->expects( $this->any() )
			->method( 'getDescription' )
			->will( $this->returnValue(
				new TermFallback( 'ar', 'DescriptionString', 'lang-code', null )
			) );

		return $labelDescriptionLookup;
	}

	public function getLabelProvider() {
		return [
			[ [ 'LabelString', 'lang-code' ], 'Q123' ],
			[ [ null, null ], 'DoesntExist' ]
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
			[ [ null, null ], 'DoesntExist' ]
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
