<?php

namespace Wikibase\Client\Tests\DataAccess\Scribunto;

use PHPUnit_Framework_TestCase;
use Wikibase\Client\DataAccess\Scribunto\WikibaseLanguageDependentLuaBindings;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\Client\Usage\HashUsageAccumulator;
use Wikibase\Client\Usage\UsageAccumulator;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\Term\Term;
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
	 * @param LabelDescriptionLookup $labelDescriptionLookup
	 * @param UsageAccumulator|null $usageAccumulator
	 * @return WikibaseLuaBindings
	 */
	private function getWikibaseLanguageDependentLuaBindings(
		LabelDescriptionLookup $labelDescriptionLookup,
		UsageAccumulator $usageAccumulator = null
	) {
		return new WikibaseLanguageDependentLuaBindings(
			new BasicEntityIdParser(),
			$labelDescriptionLookup,
			$usageAccumulator ?: new HashUsageAccumulator()
		);
	}

	/**
	 * @return LabelDescriptionLookup
	 */
	private function getLabelDescriptionLookup() {
		$labelDescriptionLookup = $this->getMock( LabelDescriptionLookup::class );
		$labelDescriptionLookup->expects( $this->any() )
			->method( 'getLabel' )
			->will( $this->returnValue( new Term( 'lang-code', 'LabelString' ) ) );

		$labelDescriptionLookup->expects( $this->any() )
			->method( 'getDescription' )
			->will( $this->returnValue( new Term( 'lang-code', 'DescriptionString' ) ) );

		return $labelDescriptionLookup;
	}

	/**
	 * @return LabelDescriptionLookup
	 */
	private function getLabelDescriptionLookupWithFallback() {
		$labelDescriptionLookup = $this->getMock( LabelDescriptionLookup::class );

		$labelDescriptionLookup->expects( $this->any() )
			->method( 'getLabel' )
			->will( $this->returnValue(
				new TermFallback( 'ar', 'en-label-fallback', 'en', null )
			) );

		$labelDescriptionLookup->expects( $this->any() )
			->method( 'getDescription' )
			->will( $this->returnValue(
				new TermFallback( 'ar', 'en-desc-fallback', 'en', null )
			) );

		return $labelDescriptionLookup;
	}

	private function hasUsage( $actualUsages, EntityId $entityId, $aspect ) {
		$usage = new EntityUsage( $entityId, $aspect );
		$key = $usage->getIdentityString();
		return isset( $actualUsages[$key] );
	}

	public function getLabelProvider() {
		return array(
			array( array( 'LabelString', 'lang-code' ), 'Q123' ),
			array( array( null, null ), 'DoesntExist' )
		);
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

	public function testGetLabelWithFallback() {
		$wikibaseLuaBindings = $this->getWikibaseLanguageDependentLuaBindings(
			$this->getLabelDescriptionLookupWithFallback()
		);

		$this->assertSame(
			[ 'en-label-fallback', 'en' ],
			$wikibaseLuaBindings->getLabel( 'Q1234' )
		);
	}

	public function testGetLabel_usage() {
		$usages = new HashUsageAccumulator();

		$wikibaseLuaBindings = $this->getWikibaseLanguageDependentLuaBindings(
			$this->getLabelDescriptionLookup(),
			$usages
		);

		$itemId = new ItemId( 'Q7' );
		$wikibaseLuaBindings->getLabel( $itemId->getSerialization() );

		//NOTE: label usage is not tracked directly, this is done via the LabelDescriptionLookup
		$this->assertFalse(
			$this->hasUsage( $usages->getUsages(), $itemId, EntityUsage::TITLE_USAGE ),
			'title usage'
		);

		$this->assertFalse(
			$this->hasUsage( $usages->getUsages(), $itemId, EntityUsage::ALL_USAGE ),
			'all usage'
		);
	}

	public function getDescriptionProvider() {
		return array(
			array( array( 'DescriptionString', 'lang-code' ), 'Q123' ),
			array( array( null, null ), 'DoesntExist' )
		);
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

	public function testGetDescriptionWithFallback() {
		$wikibaseLuaBindings = $this->getWikibaseLanguageDependentLuaBindings(
			$this->getLabelDescriptionLookupWithFallback()
		);

		$this->assertSame(
			[ 'en-desc-fallback', 'en' ],
			$wikibaseLuaBindings->getDescription( 'Q1234' )
		);
	}

	public function testGetDescription_usage() {
		$usages = new HashUsageAccumulator();

		$wikibaseLuaBindings = $this->getWikibaseLanguageDependentLuaBindings(
			$this->getLabelDescriptionLookup(),
			$usages
		);

		$itemId = new ItemId( 'Q7' );
		$wikibaseLuaBindings->getDescription( $itemId->getSerialization() );

		$this->assertTrue(
			$this->hasUsage( $usages->getUsages(), $itemId, EntityUsage::OTHER_USAGE ),
			'other usage'
		);

		$this->assertFalse(
			$this->hasUsage( $usages->getUsages(), $itemId, EntityUsage::LABEL_USAGE ),
			'label usage'
		);

		$this->assertFalse(
			$this->hasUsage( $usages->getUsages(), $itemId, EntityUsage::TITLE_USAGE ),
			'title usage'
		);

		$this->assertFalse(
			$this->hasUsage( $usages->getUsages(), $itemId, EntityUsage::ALL_USAGE ),
			'all usage'
		);
	}

}
