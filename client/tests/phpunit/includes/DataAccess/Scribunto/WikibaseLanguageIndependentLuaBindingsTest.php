<?php

namespace Wikibase\Client\Tests\DataAccess\Scribunto;

use Exception;
use PHPUnit4And6Compat;
use Wikibase\Client\DataAccess\Scribunto\WikibaseLanguageIndependentLuaBindings;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\Client\Usage\HashUsageAccumulator;
use Wikibase\Client\Usage\UsageAccumulator;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Lookup\ReferencedEntityIdLookup;
use Wikibase\DataModel\Services\Lookup\TermLookup;
use Wikibase\DataModel\Services\Lookup\MaxReferencedEntityVisitsExhaustedException;
use Wikibase\DataModel\Services\Lookup\MaxReferenceDepthExhaustedException;
use Wikibase\Lib\StaticContentLanguages;
use Wikibase\Lib\Store\HashSiteLinkStore;
use Wikibase\Lib\Store\SiteLinkLookup;
use Wikibase\SettingsArray;

/**
 * @covers Wikibase\Client\DataAccess\Scribunto\WikibaseLanguageIndependentLuaBindings
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
class WikibaseLanguageIndependentLuaBindingsTest extends \PHPUnit\Framework\TestCase {
	use PHPUnit4And6Compat;

	public function testConstructor() {
		$wikibaseLuaBindings = $this->getWikibaseLanguageIndependentLuaBindings();

		$this->assertInstanceOf( WikibaseLanguageIndependentLuaBindings::class, $wikibaseLuaBindings );
	}

	/**
	 * @param SiteLinkLookup|null $siteLinkLookup
	 * @param UsageAccumulator|null $usageAccumulator
	 * @param ReferencedEntityIdLookup|null $referencedEntityIdLookup
	 *
	 * @return WikibaseLanguageIndependentLuaBindings
	 */
	private function getWikibaseLanguageIndependentLuaBindings(
		SiteLinkLookup $siteLinkLookup = null,
		UsageAccumulator $usageAccumulator = null,
		ReferencedEntityIdLookup $referencedEntityIdLookup = null
	) {
		return new WikibaseLanguageIndependentLuaBindings(
			$siteLinkLookup ?: $this->getMock( SiteLinkLookup::class ),
			new SettingsArray(),
			$usageAccumulator ?: new HashUsageAccumulator(),
			new BasicEntityIdParser,
			$this->getMock( TermLookup::class ),
			new StaticContentLanguages( [] ),
			$referencedEntityIdLookup ?: $this->getMock( ReferencedEntityIdLookup::class ),
			'enwiki'
		);
	}

	private function hasUsage( array $actualUsages, EntityId $entityId, $aspect ) {
		$usage = new EntityUsage( $entityId, $aspect );
		$key = $usage->getIdentityString();
		return isset( $actualUsages[$key] );
	}

	public function testGetSettings() {
		$settings = new SettingsArray();
		$settings->setSetting( 'a-setting', 'a-value' );

		$bindings = new WikibaseLanguageIndependentLuaBindings(
			$this->getMock( SiteLinkLookup::class ),
			$settings,
			new HashUsageAccumulator(),
			new BasicEntityIdParser,
			$this->getMock( TermLookup::class ),
			new StaticContentLanguages( [] ),
			$this->getMock( ReferencedEntityIdLookup::class ),
			'enwiki'
		);

		$this->assertSame(
			'a-value',
			$bindings->getSetting( 'a-setting' )
		);
	}

	public function testGetEntityId() {
		$item = new Item( new ItemId( 'Q33' ) );
		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Rome' );

		$usages = new HashUsageAccumulator();

		$siteLinkStore = new HashSiteLinkStore();
		$siteLinkStore->saveLinksOfItem( $item );

		$wikibaseLuaBindings = $this->getWikibaseLanguageIndependentLuaBindings(
			$siteLinkStore,
			$usages
		);

		$id = $wikibaseLuaBindings->getEntityId( 'Rome', null );
		$this->assertSame( 'Q33', $id );

		$itemId = new ItemId( $id );
		$this->assertTrue(
			$this->hasUsage( $usages->getUsages(), $itemId, EntityUsage::TITLE_USAGE ),
			'title usage'
		);
		$this->assertFalse(
			$this->hasUsage( $usages->getUsages(), $itemId, EntityUsage::SITELINK_USAGE ),
			'sitelink usage'
		);

		$id = $wikibaseLuaBindings->getEntityId( 'Rome', 'enwiki' );
		$this->assertSame( 'Q33', $id );

		$id = $wikibaseLuaBindings->getEntityId( 'Barcelona', null );
		$this->assertNull( $id );

		$id = $wikibaseLuaBindings->getEntityId( 'Rome', 'frwiki' );
		$this->assertNull( $id );
	}

	public function getLabelByLanguageProvider() {
		$q2 = new ItemId( 'Q2' );

		return [
			'Item and label exist' => [
				[ 'Q2#L.de' ],
				'Q2-de',
				'Q2',
				'de',
				$q2,
				true,
				true
			],
			'Item id valid, but label does not exist' => [
				[ 'Q2#L.de' ],
				null,
				'Q2',
				'de',
				$q2,
				false,
				true
			],
			'Invalid Item Id' => [
				[],
				null,
				'dsfa',
				'de',
				null,
				true,
				true
			],
			'Invalid lang' => [
				[],
				null,
				'Q2',
				'de',
				$q2,
				true,
				false
			],
		];
	}

	/**
	 * @dataProvider getLabelByLanguageProvider
	 */
	public function testGetLabelByLanguage(
		array $expectedUsages,
		$expected,
		$prefixedEntityId,
		$languageCode,
		EntityId $entityId = null,
		$hasLabel,
		$hasLang
	) {
		$usages = new HashUsageAccumulator();

		$termLookup = $this->getMock( TermLookup::class );
		$termLookup->expects( $this->exactly( $hasLang && $entityId ? 1 : 0 ) )
			->method( 'getLabel' )
			->with( $entityId )
			->will( $this->returnValue( $hasLabel ? "$prefixedEntityId-$languageCode" : null ) );

		$bindings = new WikibaseLanguageIndependentLuaBindings(
			$this->getMock( SiteLinkLookup::class ),
			new SettingsArray(),
			$usages,
			new BasicEntityIdParser,
			$termLookup,
			new StaticContentLanguages( $hasLang ? [ $languageCode ] : [] ),
			$this->getMock( ReferencedEntityIdLookup::class ),
			'enwiki'
		);

		$this->assertSame( $expected, $bindings->getLabelByLanguage( $prefixedEntityId, $languageCode ) );
		$this->assertSame( $expectedUsages, array_keys( $usages->getUsages() ) );
	}

	public function getSiteLinkPageNameProvider() {
		return [
			[ 'Beer', 'Q666', null ],
			[ 'Bier', 'Q666', 'dewiki' ],
			[ null, 'DoesntExist', null ],
			[ null, 'DoesntExist', 'foobar' ],
		];
	}

	/**
	 * @dataProvider getSiteLinkPageNameProvider
	 */
	public function testGetSiteLinkPageName( $expected, $itemId, $globalSiteId ) {
		$item = $this->getItem();

		$siteLinkStore = new HashSiteLinkStore();
		$siteLinkStore->saveLinksOfItem( $item );

		$wikibaseLuaBindings = $this->getWikibaseLanguageIndependentLuaBindings( $siteLinkStore );
		$this->assertSame( $expected, $wikibaseLuaBindings->getSiteLinkPageName( $itemId, $globalSiteId ) );
	}

	public function provideGlobalSiteId() {
		return [
			[ [ 'Q666#T' ], null ],
			[ [ 'Q666#T' ], 'enwiki' ],
			[ [ 'Q666#S' ], 'dewiki' ],
			[ [ 'Q666#S' ], 'whateverwiki' ],
		];
	}

	/**
	 * @dataProvider provideGlobalSiteId
	 */
	public function testGetSiteLinkPageName_usage( array $expectedUsages, $globalSiteId ) {
		$item = $this->getItem();

		$siteLinkStore = new HashSiteLinkStore();
		$siteLinkStore->saveLinksOfItem( $item );

		$usages = new HashUsageAccumulator();

		$wikibaseLuaBindings = $this->getWikibaseLanguageIndependentLuaBindings(
			$siteLinkStore,
			$usages
		);

		$itemId = $item->getId();
		$wikibaseLuaBindings->getSiteLinkPageName( $itemId->getSerialization(), $globalSiteId );

		$this->assertSame( $expectedUsages, array_keys( $usages->getUsages() ) );
	}

	public function provideIsValidEntityId() {
		return [
			[ true, 'Q12' ],
			[ true, 'P12' ],
			[ false, 'Q0' ],
			[ false, '[[Q2]]' ],
		];
	}

	/**
	 * @dataProvider provideIsValidEntityId
	 */
	public function testIsValidEntityId( $expected, $entityIdSerialization ) {
		$wikibaseLuaBindings = $this->getWikibaseLanguageIndependentLuaBindings();

		$this->assertSame( $expected, $wikibaseLuaBindings->isValidEntityId( $entityIdSerialization ) );
	}

	/**
	 * @param Exception|mixed $result Will be thrown if this is an Exception, will be returned
	 *		otherwise.
	 * @return ReferencedEntityIdLookup
	 */
	private function newReferencedEntityIdLookupMock( $result ) {
		$referencedEntityIdLookup = $this->getMock( ReferencedEntityIdLookup::class );
		$getReferencedEntityIdMocker = $referencedEntityIdLookup
			->expects( $this->once() )
			->method( 'getReferencedEntityId' )
			->with( new ItemId( 'Q1453477' ), new PropertyId( 'P1366' ), [ new ItemId( 'Q2013' ) ] );

		if ( $result instanceof Exception ) {
			$getReferencedEntityIdMocker->willThrowException( $result );
		} else {
			$getReferencedEntityIdMocker->willReturn( $result );
		}

		return $referencedEntityIdLookup;
	}

	public function provideGetReferencedEntityId() {
		return [
			'Nothing found' => [
				null,
				$this->newReferencedEntityIdLookupMock( null )
			],
			'Q2013 found' => [
				'Q2013',
				$this->newReferencedEntityIdLookupMock( new ItemId( 'Q2013' ) )
			],
			'MaxReferenceDepthExhaustedException' => [
				false,
				$this->newReferencedEntityIdLookupMock(
					new MaxReferenceDepthExhaustedException(
						new ItemId( 'Q2013' ),
						new PropertyId( 'P1366' ),
						[ new ItemId( 'Q2013' ) ],
						42
					)
				)
			],
			'MaxReferencedEntityVisitsExhaustedException' => [
				false,
				$this->newReferencedEntityIdLookupMock(
					new MaxReferencedEntityVisitsExhaustedException(
						new ItemId( 'Q2013' ),
						new PropertyId( 'P1366' ),
						[ new ItemId( 'Q2013' ) ],
						42
					)
				)
			],
		];
	}

	/**
	 * @dataProvider provideGetReferencedEntityId
	 */
	public function testGetReferencedEntityId( $expected, ReferencedEntityIdLookup $referencedEntityIdLookup ) {
		$wikibaseLuaBindings = $this->getWikibaseLanguageIndependentLuaBindings( null, null, $referencedEntityIdLookup );

		$this->assertSame(
			$expected,
			$wikibaseLuaBindings->getReferencedEntityId( new ItemId( 'Q1453477' ), new PropertyId( 'P1366' ), [ new ItemId( 'Q2013' ) ] )
		);
	}

	/**
	 * @return Item
	 */
	private function getItem() {
		$item = new Item( new ItemId( 'Q666' ) );
		$item->setLabel( 'en', 'Beer' );
		$item->setDescription( 'en', 'yummy beverage' );
		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Beer' );
		$item->getSiteLinkList()->addNewSiteLink( 'dewiki', 'Bier' );

		return $item;
	}

}
