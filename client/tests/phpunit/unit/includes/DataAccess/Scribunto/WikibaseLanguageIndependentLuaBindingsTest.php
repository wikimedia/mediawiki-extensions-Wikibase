<?php

namespace Wikibase\Client\Tests\Unit\DataAccess\Scribunto;

use Exception;
use MediaWiki\MediaWikiServices;
use PHPUnit\Framework\MockObject\MockObject;
use TitleFormatter;
use TitleParser;
use Wikibase\Client\DataAccess\Scribunto\WikibaseLanguageIndependentLuaBindings;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\Client\Usage\HashUsageAccumulator;
use Wikibase\Client\Usage\UsageAccumulator;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Services\Lookup\MaxReferencedEntityVisitsExhaustedException;
use Wikibase\DataModel\Services\Lookup\MaxReferenceDepthExhaustedException;
use Wikibase\DataModel\Services\Lookup\ReferencedEntityIdLookup;
use Wikibase\DataModel\Services\Lookup\TermLookup;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\StaticContentLanguages;
use Wikibase\Lib\Store\EntityIdLookup;
use Wikibase\Lib\Store\HashSiteLinkStore;
use Wikibase\Lib\Store\RevisionBasedEntityRedirectTargetLookup;
use Wikibase\Lib\Store\SiteLinkLookup;

/**
 * @covers \Wikibase\Client\DataAccess\Scribunto\WikibaseLanguageIndependentLuaBindings
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

	/**
	 * @var MockObject|SiteLinkLookup
	 */
	private $sitelinkLookup;

	/**
	 * @var MockObject|UsageAccumulator
	 */
	private $usageAccumulator;

	/**
	 * @var MockObject|ReferencedEntityIdLookup
	 */
	private $referencedEntityIdLookup;

	/**
	 * @var MockObject|RevisionBasedEntityRedirectTargetLookup
	 */
	private $redirectTargetLookup;

	/**
	 * @var MockObject|EntityIdLookup
	 */
	private $entityIdLookup;

	protected function setUp(): void {
		parent::setUp();

		$this->sitelinkLookup = $this->createStub( SiteLinkLookup::class );
		$this->entityIdLookup = $this->createStub( EntityIdLookup::class );
		$this->usageAccumulator = new HashUsageAccumulator();
		$this->referencedEntityIdLookup = $this->createStub( ReferencedEntityIdLookup::class );
		$this->redirectTargetLookup = $this->newMockRevisionBasedEntityRedirectTargetLookup();
	}

	public function testConstructor() {
		$wikibaseLuaBindings = $this->getWikibaseLanguageIndependentLuaBindings();

		$this->assertInstanceOf( WikibaseLanguageIndependentLuaBindings::class, $wikibaseLuaBindings );
	}

	private function getWikibaseLanguageIndependentLuaBindings() {
		$mediaWikiServices = MediaWikiServices::getInstance();

		return new WikibaseLanguageIndependentLuaBindings(
			$this->sitelinkLookup,
			$this->entityIdLookup,
			new SettingsArray(),
			$this->usageAccumulator,
			new BasicEntityIdParser,
			$this->createMock( TermLookup::class ),
			new StaticContentLanguages( [] ),
			$this->referencedEntityIdLookup,
			$mediaWikiServices->getTitleFormatter(),
			$mediaWikiServices->getTitleParser(),
			'enwiki',
			$this->redirectTargetLookup
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
			$this->createMock( SiteLinkLookup::class ),
			$this->createMock( EntityIdLookup::class ),
			$settings,
			new HashUsageAccumulator(),
			new BasicEntityIdParser,
			$this->createMock( TermLookup::class ),
			new StaticContentLanguages( [] ),
			$this->createMock( ReferencedEntityIdLookup::class ),
			$this->createMock( TitleFormatter::class ),
			$this->createMock( TitleParser::class ),
			'enwiki',
			$this->newMockRevisionBasedEntityRedirectTargetLookup()
		);

		$this->assertSame(
			'a-value',
			$bindings->getSetting( 'a-setting' )
		);
	}

	public function testGetEntityId() {
		$item = new Item( new ItemId( 'Q33' ) );
		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Rome' );

		$this->usageAccumulator = new HashUsageAccumulator();

		$this->sitelinkLookup = new HashSiteLinkStore();
		$this->sitelinkLookup->saveLinksOfItem( $item );

		$wikibaseLuaBindings = $this->getWikibaseLanguageIndependentLuaBindings();

		$id = $wikibaseLuaBindings->getEntityId( 'Rome', null );
		$this->assertSame( 'Q33', $id );

		$itemId = new ItemId( $id );
		$this->assertTrue(
			$this->hasUsage( $this->usageAccumulator->getUsages(), $itemId, EntityUsage::TITLE_USAGE ),
			'title usage'
		);
		$this->assertFalse(
			$this->hasUsage( $this->usageAccumulator->getUsages(), $itemId, EntityUsage::SITELINK_USAGE ),
			'sitelink usage'
		);

		$id = $wikibaseLuaBindings->getEntityId( 'Rome', 'enwiki' );
		$this->assertSame( 'Q33', $id );

		// Test non-normalized title
		$id = $wikibaseLuaBindings->getEntityId( ':Rome', 'enwiki' );
		$this->assertSame( 'Q33', $id );

		// Test invalid title
		$id = $wikibaseLuaBindings->getEntityId( '{A}', 'enwiki' );
		$this->assertNull( $id );

		$id = $wikibaseLuaBindings->getEntityId( 'Barcelona', null );
		$this->assertNull( $id );

		$id = $wikibaseLuaBindings->getEntityId( 'Rome', 'frwiki' );
		$this->assertNull( $id );
	}

	public function testGetEntityForNonItem() {
		$expectedId = 'M123';
		$entityId = $this->createStub( EntityId::class ); // e.g. a MediaInfo ID
		$entityId->method( 'getSerialization' )->willReturn( $expectedId );

		$this->entityIdLookup->expects( $this->once() )
			->method( 'getEntityIdForTitle' )
			->willReturn( $entityId );

		$this->assertSame(
			$expectedId,
			$this->getWikibaseLanguageIndependentLuaBindings()->getEntityId( 'some page', 'enwiki' )
		);
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
				true,
			],
			'Item id valid, but label does not exist' => [
				[ 'Q2#L.de' ],
				null,
				'Q2',
				'de',
				$q2,
				false,
				true,
			],
			'Invalid Item Id' => [
				[],
				null,
				'dsfa',
				'de',
				null,
				true,
				true,
			],
			'Invalid lang' => [
				[],
				null,
				'Q2',
				'de',
				$q2,
				true,
				false,
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
		?EntityId $entityId,
		$hasLabel,
		$hasLang
	) {
		$usages = new HashUsageAccumulator();

		$termLookup = $this->createMock( TermLookup::class );
		$termLookup->expects( $this->exactly( $hasLang && $entityId ? 1 : 0 ) )
			->method( 'getLabel' )
			->with( $entityId )
			->willReturn( $hasLabel ? "$prefixedEntityId-$languageCode" : null );

		$bindings = new WikibaseLanguageIndependentLuaBindings(
			$this->createMock( SiteLinkLookup::class ),
			$this->createMock( EntityIdLookup::class ),
			new SettingsArray(),
			$usages,
			new BasicEntityIdParser,
			$termLookup,
			new StaticContentLanguages( $hasLang ? [ $languageCode ] : [] ),
			$this->createMock( ReferencedEntityIdLookup::class ),
			$this->createMock( TitleFormatter::class ),
			$this->createMock( TitleParser::class ),
			'enwiki',
			$this->newMockRevisionBasedEntityRedirectTargetLookup()
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

		$this->sitelinkLookup = new HashSiteLinkStore();
		$this->sitelinkLookup->saveLinksOfItem( $item );

		$this->assertSame(
			$expected,
			$this->getWikibaseLanguageIndependentLuaBindings()->getSiteLinkPageName( $itemId, $globalSiteId )
		);
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

		$this->sitelinkLookup = new HashSiteLinkStore();
		$this->sitelinkLookup->saveLinksOfItem( $item );

		$itemId = $item->getId();
		$this->getWikibaseLanguageIndependentLuaBindings()->getSiteLinkPageName( $itemId->getSerialization(), $globalSiteId );

		$this->assertSame( $expectedUsages, array_keys( $this->usageAccumulator->getUsages() ) );
	}

	public function testGivenRedirectItemId_getSiteLinkPageNameResolvesRedirect() {
		$siteId = 'somewiki';
		$expectedPageName = 'Potato';
		$itemId = new ItemId( 'Q123' );
		$redirectTargetId = new ItemId( 'Q321' );

		$this->redirectTargetLookup = $this->createMock( RevisionBasedEntityRedirectTargetLookup::class );
		$this->redirectTargetLookup->expects( $this->once() )
			->method( 'getRedirectForEntityId' )
			->with( $itemId )
			->willReturn( $redirectTargetId );

		$this->sitelinkLookup = $this->createMock( SiteLinkLookup::class );
		$this->sitelinkLookup->expects( $this->once() )
			->method( 'getLinks' )
			->with( [ $redirectTargetId->getNumericId() ], [ $siteId ] )
			->willReturn( [ [ $siteId, $expectedPageName ] ] );

		$this->assertSame(
			$expectedPageName,
			$this->getWikibaseLanguageIndependentLuaBindings()
				->getSiteLinkPageName( $itemId->getSerialization(), $siteId )
		);
	}

	public function testGivenRedirectItemId_usageIsTrackedForRedirectAndRedirectTarget() {
		$item = $this->getItem();

		$this->sitelinkLookup = new HashSiteLinkStore();
		$this->sitelinkLookup->saveLinksOfItem( $item );

		$itemId = $item->getId();
		$redirectTargetId = new ItemId( 'Q321' );

		$this->redirectTargetLookup = $this->createMock( RevisionBasedEntityRedirectTargetLookup::class );
		$this->redirectTargetLookup->expects( $this->once() )
			->method( 'getRedirectForEntityId' )
			->with( $itemId )
			->willReturn( $redirectTargetId );

		$this->getWikibaseLanguageIndependentLuaBindings()
			->getSiteLinkPageName( $itemId->getSerialization(), 'somewiki' );

		$usages = array_keys( $this->usageAccumulator->getUsages() );
		$this->assertContains(
			( new EntityUsage( $itemId, EntityUsage::ALL_USAGE ) )->getIdentityString(),
			$usages
		);
		$this->assertContains(
			( new EntityUsage( $redirectTargetId, EntityUsage::SITELINK_USAGE ) )->getIdentityString(),
			$usages
		);
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
		$referencedEntityIdLookup = $this->createMock( ReferencedEntityIdLookup::class );
		$getReferencedEntityIdMocker = $referencedEntityIdLookup
			->expects( $this->once() )
			->method( 'getReferencedEntityId' )
			->with( new ItemId( 'Q1453477' ), new NumericPropertyId( 'P1366' ), [ new ItemId( 'Q2013' ) ] );

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
				$this->newReferencedEntityIdLookupMock( null ),
			],
			'Q2013 found' => [
				'Q2013',
				$this->newReferencedEntityIdLookupMock( new ItemId( 'Q2013' ) ),
			],
			'MaxReferenceDepthExhaustedException' => [
				false,
				$this->newReferencedEntityIdLookupMock(
					new MaxReferenceDepthExhaustedException(
						new ItemId( 'Q2013' ),
						new NumericPropertyId( 'P1366' ),
						[ new ItemId( 'Q2013' ) ],
						42
					)
				),
			],
			'MaxReferencedEntityVisitsExhaustedException' => [
				false,
				$this->newReferencedEntityIdLookupMock(
					new MaxReferencedEntityVisitsExhaustedException(
						new ItemId( 'Q2013' ),
						new NumericPropertyId( 'P1366' ),
						[ new ItemId( 'Q2013' ) ],
						42
					)
				),
			],
		];
	}

	/**
	 * @dataProvider provideGetReferencedEntityId
	 */
	public function testGetReferencedEntityId( $expected, ReferencedEntityIdLookup $referencedEntityIdLookup ) {
		$this->referencedEntityIdLookup = $referencedEntityIdLookup;
		$this->assertSame(
			$expected,
			$this->getWikibaseLanguageIndependentLuaBindings()
				->getReferencedEntityId( new ItemId( 'Q1453477' ), new NumericPropertyId( 'P1366' ), [ new ItemId( 'Q2013' ) ] )
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

	private function newMockRevisionBasedEntityRedirectTargetLookup(): RevisionBasedEntityRedirectTargetLookup {
		return $this->createStub( RevisionBasedEntityRedirectTargetLookup::class );
	}

}
