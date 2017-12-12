<?php

namespace Wikibase\Client\Tests\Changes;

use ArrayIterator;
use DataValues\DataValue;
use DataValues\StringValue;
use Title;
use Traversable;
use Wikibase\Client\Changes\AffectedPagesFinder;
use Wikibase\Client\Store\TitleFactory;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\Client\Usage\PageEntityUsages;
use Wikibase\Client\Usage\SiteLinkUsageLookup;
use Wikibase\Client\Usage\UsageLookup;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\EntityChange;
use Wikibase\Lib\Store\SiteLinkLookup;
use Wikibase\Lib\Store\StorageException;
use Wikibase\Lib\Tests\Changes\TestChanges;

/**
 * @covers Wikibase\Client\Changes\AffectedPagesFinder
 *
 * @group Database
 * @group WikibaseClient
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Daniel Kinzler
 */
class AffectedPagesFinderTest extends \MediaWikiTestCase {

	/**
	 * Returns a TitleFactory that generates Title objects based on the assumption
	 * that a page's title is the same as the page's article ID (in decimal notation).
	 *
	 * @return TitleFactory
	 */
	private function getTitleFactory() {
		$titleFactory = $this->getMock( TitleFactory::class );

		$titleFactory->expects( $this->any() )
			->method( 'newFromID' )
			->will( $this->returnCallback( function( $id ) {
				$title = Title::makeTitle( NS_MAIN, "$id" );
				$title->resetArticleID( $id );
				return $title;
			} ) );

		$titleFactory->expects( $this->any() )
			->method( 'newFromText' )
			->will( $this->returnCallback( function( $text, $defaultNs = NS_MAIN ) {
				$title = Title::newFromText( $text, $defaultNs );

				if ( !$title ) {
					throw new StorageException( 'Bad title text: ' . $text );
				}

				$title->resetArticleID( $text );
				return $title;
			} ) );

		return $titleFactory;
	}

	private function getAffectedPagesFinder( array $usage, array $expectedAspects, $trackUsagesInAllLanguages = false ) {
		$usageLookup = $this->getMock( UsageLookup::class );

		$usageLookup->expects( $this->any() )
			->method( 'getPagesUsing' )
			->with( $this->anything(), $expectedAspects )
			->will( $this->returnValue( new ArrayIterator( $usage ) ) );

		$affectedPagesFinder = new AffectedPagesFinder(
			$usageLookup,
			$this->getTitleFactory(),
			'enwiki',
			'en',
			$trackUsagesInAllLanguages,
			false
		);

		return $affectedPagesFinder;
	}

	public function getChangedAspectsProvider() {
		$changeFactory = TestChanges::getEntityChangeFactory();
		$cases = [];

		$q1 = new ItemId( 'Q1' );
		$q2 = new ItemId( 'Q2' );

		$cases['create linked item Q1'] = [
			[ EntityUsage::SITELINK_USAGE, EntityUsage::TITLE_USAGE ],
			$changeFactory->newFromUpdate(
				EntityChange::ADD,
				null,
				$this->getItemWithSiteLinks( $q1, [ 'enwiki' => '1' ] )
			)
		];

		$cases['unlink item Q1'] = [
			[ EntityUsage::SITELINK_USAGE, EntityUsage::TITLE_USAGE ],
			$changeFactory->newFromUpdate(
				EntityChange::UPDATE,
				$this->getItemWithSiteLinks( $q1, [ 'enwiki' => '1' ] ),
				new Item( $q1 )
			)
		];

		$cases['link item Q2'] = [
			[ EntityUsage::SITELINK_USAGE, EntityUsage::TITLE_USAGE ],
			$changeFactory->newFromUpdate(
				EntityChange::UPDATE,
				new Item( $q2 ),
				$this->getItemWithSiteLinks( $q2, [ 'enwiki' => '2' ] )
			)
		];

		$cases['change link of Q1'] = [
			[ EntityUsage::SITELINK_USAGE, EntityUsage::TITLE_USAGE ],
			$changeFactory->newFromUpdate(
				EntityChange::UPDATE,
				$this->getItemWithSiteLinks( $q1, [ 'enwiki' => '1' ] ),
				$this->getItemWithSiteLinks( $q1, [ 'enwiki' => '2' ] )
			)
		];

		$cases['delete linked item Q2'] = [
			[ EntityUsage::SITELINK_USAGE, EntityUsage::TITLE_USAGE ],
			$changeFactory->newFromUpdate(
				EntityChange::REMOVE,
				$this->getItemWithSiteLinks( $q2, [ 'enwiki' => '2' ] ),
				null
			)
		];

		$cases['add another sitelink to Q2'] = [
			[ EntityUsage::SITELINK_USAGE ],
			$changeFactory->newFromUpdate(
				EntityChange::UPDATE,
				$this->getItemWithSiteLinks( $q2, [ 'enwiki' => '2' ] ),
				$this->getItemWithSiteLinks( $q2, [
					'enwiki' => '2',
					'itwiki' => 'DUE',
				] )
			)
		];

		$cases['alias change on Q1'] = [
			[ EntityUsage::OTHER_USAGE ],
			$changeFactory->newFromUpdate(
				EntityChange::UPDATE,
				new Item( $q1 ),
				$this->getItemWithAliases( $q1, 'de', [ 'EINS' ] )
			)
		];

		$cases['local label change on Q1 (used by Q2); mono-lingual wiki'] = [
			[ EntityUsage::makeAspectKey( EntityUsage::LABEL_USAGE, 'en' ) ],
			$changeFactory->newFromUpdate(
				EntityChange::UPDATE,
				new Item( $q1 ),
				$this->getItemWithLabel( $q1, 'en', 'ONE' )
			)
		];

		$cases['local label change on Q1 (used by Q2); multi-lingual wiki'] = [
			[ EntityUsage::makeAspectKey( EntityUsage::LABEL_USAGE, 'en' ), EntityUsage::makeAspectKey( EntityUsage::LABEL_USAGE ) ],
			$changeFactory->newFromUpdate(
				EntityChange::UPDATE,
				new Item( $q1 ),
				$this->getItemWithLabel( $q1, 'en', 'ONE' )
			),
			true
		];

		$badges = [ new ItemId( 'Q34' ) ];
		$cases['badge only change on Q1'] = [
			[ EntityUsage::SITELINK_USAGE ],
			$changeFactory->newFromUpdate(
				EntityChange::UPDATE,
				$this->getItemWithSiteLinks( $q1, [ 'enwiki' => '1' ] ),
				$this->getItemWithSiteLinks( $q1, [ 'enwiki' => '1' ], $badges ) )
		];

		$cases['description only change on Q1'] = [
			[ EntityUsage::DESCRIPTION_USAGE . '.en' ],
			$changeFactory->newFromUpdate(
				EntityChange::UPDATE,
				$this->getItemWithDescriptions( $q1, [ 'en' => 'Hello' ] ),
				$this->getItemWithDescriptions( $q1, [ 'en' => 'Hallo' ] ) )
		];

		$cases['statement change on Q1'] = [
			[ EntityUsage::STATEMENT_USAGE . '.P5' ],
			$changeFactory->newFromUpdate(
				EntityChange::UPDATE,
				new Item( $q1 ),
				$this->getItemWithStatement( $q1, new PropertyId( 'P5' ), new StringValue( 'Hello' ) )
			)
		];

		return $cases;
	}

	/**
	 * @dataProvider getChangedAspectsProvider
	 */
	public function testGetChangedAspects( array $expected, EntityChange $change, $trackUsagesInAllLanguages = false ) {
		$referencedPagesFinder = $this->getAffectedPagesFinder( [], [], $trackUsagesInAllLanguages );

		$actual = $referencedPagesFinder->getChangedAspects( $change );

		sort( $expected );
		sort( $actual );
		$this->assertEquals( $expected, $actual );
	}

	public function getAffectedUsagesByPageProvider() {
		$labelUsageDe = EntityUsage::LABEL_USAGE . '.de';
		$labelUsageEn = EntityUsage::LABEL_USAGE . '.en';

		$changeFactory = TestChanges::getEntityChangeFactory();

		$q1 = new ItemId( 'Q1' );
		$q2 = new ItemId( 'Q2' );

		$q1SitelinkUsage = new EntityUsage( $q1, EntityUsage::SITELINK_USAGE );
		$q2SitelinkUsage = new EntityUsage( $q2, EntityUsage::SITELINK_USAGE );
		$q2AllUsage = new EntityUsage( $q2, EntityUsage::ALL_USAGE );
		$q2OtherUsage = new EntityUsage( $q2, EntityUsage::OTHER_USAGE );

		$q1LabelUsage_en = new EntityUsage( $q1, EntityUsage::LABEL_USAGE, 'en' );
		$q2LabelUsage = new EntityUsage( $q2, EntityUsage::LABEL_USAGE );
		$q2LabelUsage_en = new EntityUsage( $q2, EntityUsage::LABEL_USAGE, 'en' );
		$q2LabelUsage_de = new EntityUsage( $q2, EntityUsage::LABEL_USAGE, 'de' );

		$q1TitleUsage = new EntityUsage( $q1, EntityUsage::TITLE_USAGE );
		$q2TitleUsage = new EntityUsage( $q2, EntityUsage::TITLE_USAGE );

		$q2DescriptionUsage_en = new EntityUsage( $q2, EntityUsage::DESCRIPTION_USAGE, 'en' );

		// Page 1 is linked to Q1
		$page1Q1Usages = new PageEntityUsages( 1, [
			$q1SitelinkUsage,
		] );

		// Page 2 uses label and title to link to Q1
		$page2Q1Usages = new PageEntityUsages( 2, [
			$q1LabelUsage_en,
			$q1TitleUsage,
			$q2DescriptionUsage_en,
		] );

		// Page 1 uses label and title to link to Q2, and shows the German label too.
		$page1Q2Usages = new PageEntityUsages( 1, [
			$q2LabelUsage, // "all languages" usage
			$q2TitleUsage,
		] );

		// Page 2 uses Q2 to render an infobox
		$page2Q2Usages = new PageEntityUsages( 2, [
			$q2AllUsage,
		] );

		// Cases
		// item with link created
		// item with link deleted
		// link added
		// removed added
		// link changed
		// direct aspect match
		// no aspect match
		// all matches any
		// any matches all

		$cases = [];

		$cases['create linked item Q1'] = [
			[
				new PageEntityUsages( 1, [ $q1SitelinkUsage ] ),
			],
			[ EntityUsage::SITELINK_USAGE, EntityUsage::TITLE_USAGE ],
			[], // No usages recorded yet
			$changeFactory->newFromUpdate(
				EntityChange::ADD,
				null,
				$this->getItemWithSiteLinks( $q1, [ 'enwiki' => '1' ] )
			)
		];

		$cases['unlink item Q1'] = [
			[
				new PageEntityUsages( 1, [ $q1SitelinkUsage ] ),
				new PageEntityUsages( 2, [ $q1TitleUsage ] ),
			],
			[ EntityUsage::SITELINK_USAGE, EntityUsage::TITLE_USAGE ],
			[ $page1Q1Usages, $page2Q1Usages ], // "1" was recorded to be linked to Q1 and the local title used on page "2"
			$changeFactory->newFromUpdate(
				EntityChange::UPDATE,
				$this->getItemWithSiteLinks( $q1, [ 'enwiki' => '1' ] ),
				new Item( $q1 )
			)
		];

		$cases['link item Q2'] = [
			[
				new PageEntityUsages( 1, [ $q2TitleUsage ] ),
				new PageEntityUsages( 2, [ $q2TitleUsage, $q2SitelinkUsage ] ),
			],
			[ EntityUsage::SITELINK_USAGE, EntityUsage::TITLE_USAGE ],
			[ $page1Q2Usages, $page2Q2Usages ],
			$changeFactory->newFromUpdate(
				EntityChange::UPDATE,
				new Item( $q2 ),
				$this->getItemWithSiteLinks( $q2, [ 'enwiki' => '2' ] )
			)
		];

		$cases['change link of Q1, with NO prior record'] = [
			[
				new PageEntityUsages( 1, [ $q1SitelinkUsage ] ),
				new PageEntityUsages( 2, [ $q1SitelinkUsage ] ),
			],
			[ EntityUsage::SITELINK_USAGE, EntityUsage::TITLE_USAGE ],
			[],
			$changeFactory->newFromUpdate(
				EntityChange::UPDATE,
				$this->getItemWithSiteLinks( $q1, [ 'enwiki' => '1' ] ),
				$this->getItemWithSiteLinks( $q1, [ 'enwiki' => '2' ] )
			)
		];

		$cases['change link of Q1, with prior record'] = [
			[
				new PageEntityUsages( 1, [ $q1SitelinkUsage ] ),
				new PageEntityUsages( 2, [ $q1SitelinkUsage, $q1TitleUsage ] ),
			],
			[ EntityUsage::SITELINK_USAGE, EntityUsage::TITLE_USAGE ],
			[ $page1Q1Usages, $page2Q1Usages ],
			$changeFactory->newFromUpdate(
				EntityChange::UPDATE,
				$this->getItemWithSiteLinks( $q1, [ 'enwiki' => '1' ] ),
				$this->getItemWithSiteLinks( $q1, [ 'enwiki' => '2' ] )
			)
		];

		$badges = [ new ItemId( 'Q34' ) ];
		$cases['badge only change on Q1'] = [
			[
				new PageEntityUsages( 1, [ $q1SitelinkUsage ] ),
			],
			[ EntityUsage::SITELINK_USAGE ],
			[ $page1Q1Usages, $page2Q1Usages ],
			$changeFactory->newFromUpdate(
				EntityChange::UPDATE,
				$this->getItemWithSiteLinks( $q1, [ 'enwiki' => '1' ] ),
				$this->getItemWithSiteLinks( $q1, [ 'enwiki' => '1' ], $badges ) )
		];

		$cases['delete linked item Q2'] = [
			[
				new PageEntityUsages( 1, [ $q2TitleUsage ] ),
				new PageEntityUsages( 2, [ $q2TitleUsage, $q2SitelinkUsage ] ),
			],
			[ EntityUsage::SITELINK_USAGE, EntityUsage::TITLE_USAGE ],
			[ $page1Q2Usages, $page2Q2Usages ],
			$changeFactory->newFromUpdate(
				EntityChange::REMOVE,
				$this->getItemWithSiteLinks( $q2, [ 'enwiki' => '2' ] ),
				null
			)
		];

		$cases['add another sitelink to Q2'] = [
			[
				new PageEntityUsages( 2, [ $q2SitelinkUsage ] ),
			],
			[ EntityUsage::SITELINK_USAGE ],
			[ $page2Q2Usages ],
			$changeFactory->newFromUpdate(
				EntityChange::UPDATE,
				$this->getItemWithSiteLinks( $q2, [ 'enwiki' => '2' ] ),
				$this->getItemWithSiteLinks( $q2, [
					'enwiki' => '2',
					'itwiki' => 'DUE',
				] )
			)
		];

		$cases['other language label change on Q1 (not used on any page)'] = [
			[],
			[ $labelUsageDe ],
			[ $page1Q1Usages, $page2Q1Usages ],
			$changeFactory->newFromUpdate(
				EntityChange::UPDATE,
				new Item( $q1 ),
				$this->getItemWithLabel( $q1, 'de', 'EINS' )
			)
		];

		$cases['other change on Q2 (used on page 2)'] = [
			[
				new PageEntityUsages( 2, [ $q2OtherUsage ] ),
			],
			[ EntityUsage::OTHER_USAGE ],
			[ $page1Q2Usages, $page2Q2Usages ],
			$changeFactory->newFromUpdate(
				EntityChange::UPDATE,
				new Item( $q2 ),
				$this->getItemWithAliases( $q2, 'fr', [ 'X', 'Y' ] )
			)
		];

		$cases['other language label change on Q2 (used on page 1 and 2)'] = [
			[
				new PageEntityUsages( 1, [ $q2LabelUsage_de ] ),
				new PageEntityUsages( 2, [ $q2LabelUsage_de ] ),
			],
			[ $labelUsageDe ],
			[ $page1Q2Usages, $page2Q2Usages ],
			$changeFactory->newFromUpdate(
				EntityChange::UPDATE,
				new Item( $q2 ),
				$this->getItemWithLabel( $q2, 'de', 'EINS' )
			)
		];

		$cases['local label change on Q1 (used by page 2)'] = [
			[
				new PageEntityUsages( 2, [ $q1LabelUsage_en ] ),
			],
			[ $labelUsageEn ],
			[ $page1Q1Usages, $page2Q1Usages ],
			$changeFactory->newFromUpdate(
				EntityChange::UPDATE,
				new Item( $q1 ),
				$this->getItemWithLabel( $q1, 'en', 'ONE' )
			)
		];

		$cases['local label change on Q2 (used by page 1 and page 2)'] = [
			[
				new PageEntityUsages( 1, [ $q2LabelUsage_en ] ),
				new PageEntityUsages( 2, [ $q2LabelUsage_en ] ),
			],
			[ $labelUsageEn ],
			[ $page1Q2Usages, $page2Q2Usages ],
			$changeFactory->newFromUpdate(
				EntityChange::UPDATE,
				new Item( $q2 ),
				$this->getItemWithLabel( $q2, 'en', 'TWO' )
			)
		];

		$cases['local description change on Q2 (used by page 2)'] = [
			[
				new PageEntityUsages( 2, [ $q2DescriptionUsage_en ] ),
			],
			[ EntityUsage::DESCRIPTION_USAGE . '.en' ],
			[ $page2Q2Usages ],
			$changeFactory->newFromUpdate(
				EntityChange::UPDATE,
				new Item( $q2 ),
				$this->getItemWithDescriptions( $q2, [ 'en' => 'Wow' ] )
			)
		];

		return $cases;
	}

	/**
	 * @dataProvider getAffectedUsagesByPageProvider
	 */
	public function testGetAffectedUsagesByPage( array $expected, array $expectedAspects, array $usage, EntityChange $change ) {
		// Everything will affect pages with ALL_USAGE.
		$expectedAspects = array_merge( $expectedAspects, [ EntityUsage::ALL_USAGE ] );

		$referencedPagesFinder = $this->getAffectedPagesFinder( $usage, $expectedAspects );

		$actual = $referencedPagesFinder->getAffectedUsagesByPage( $change );

		$this->assertPageEntityUsages( $expected, $actual );
	}

	public function testGetAffectedUsagesByPage_withDeletedPage() {
		$pageTitle = 'RandomKitten-2x5jsg8j3bvmpm4!5';

		$affectedPagesFinder = new AffectedPagesFinder(
			$this->getSiteLinkUsageLookup( $pageTitle ),
			new TitleFactory(),
			'enwiki',
			'en',
			false,
			false
		);

		$itemId = new ItemId( 'Q1' );

		$changeFactory = TestChanges::getEntityChangeFactory();

		$change = $changeFactory->newFromUpdate(
			EntityChange::UPDATE,
			$this->getItemWithSiteLinks( $itemId, [ 'enwiki' => $pageTitle ] ),
			new Item( $itemId )
		);

		$usages = $affectedPagesFinder->getAffectedUsagesByPage( $change );

		$this->assertCount( 0, $usages );
	}

	private function getSiteLinkUsageLookup( $pageTitle ) {
		$siteLinkLookup = $this->getMock( SiteLinkLookup::class );

		$siteLinkLookup->expects( $this->any() )
			->method( 'getItemIdForLink' )
			->will( $this->returnValue( new ItemId( 'Q1' ) ) );

		$siteLinkLookup->expects( $this->any() )
			->method( 'getLinks' )
			->will( $this->returnValue( [
				[ 'enwiki', $pageTitle, 1 ]
			] ) );

		$titleFactory = new TitleFactory();

		return new SiteLinkUsageLookup( 'enwiki', $siteLinkLookup, $titleFactory );
	}

	/**
	 * @param ItemId $id
	 * @param string[] $links
	 * @param ItemId[] $badges
	 *
	 * @return Item
	 */
	private function getItemWithSiteLinks( ItemId $id, array $links, array $badges = [] ) {
		$item = new Item( $id );

		foreach ( $links as $siteId => $page ) {
			$item->getSiteLinkList()->addNewSiteLink( $siteId, $page, $badges );
		}

		return $item;
	}

	/**
	 * @param ItemId $id
	 * @param string $languageCode
	 * @param string $label
	 *
	 * @return Item
	 */
	private function getItemWithLabel( ItemId $id, $languageCode, $label ) {
		$item = new Item( $id );
		$item->setLabel( $languageCode, $label );

		return $item;
	}

	/**
	 * @param ItemId $id
	 * @param string[] $descriptions
	 *
	 * @return Item
	 */
	private function getItemWithDescriptions( ItemId $id, $descriptions ) {
		$item = new Item( $id );
		foreach ( $descriptions as $language => $value ) {
			$item->setDescription( $language, $value );
		}

		return $item;
	}

	/**
	 * @param ItemId $id
	 * @param string $languageCode
	 * @param string[] $aliases
	 *
	 * @return Item
	 */
	private function getItemWithAliases( ItemId $id, $languageCode, array $aliases ) {
		$item = new Item( $id );
		$item->setAliases( $languageCode, $aliases );

		return $item;
	}

	/**
	 * @param ItemId $qid
	 * @param PropertyId $pid
	 * @param DataValue $value
	 *
	 * @return Item
	 */
	private function getItemWithStatement( ItemId $qid, PropertyId $pid, DataValue $value ) {
		$snak = new PropertyValueSnak( $pid, $value );

		$item = new Item( $qid );
		$item->getStatements()->addNewStatement( $snak );

		return $item;
	}

	/**
	 * @param PageEntityUsages[]|Traversable $usagesPerPage An array or traversable of
	 *  PageEntityUsages.
	 *
	 * @return PageEntityUsages[]
	 */
	private function getPageEntityUsageStrings( $usagesPerPage ) {
		$strings = [];

		foreach ( $usagesPerPage as $pageUsages ) {
			$strings[] = "$pageUsages";
		}

		sort( $strings );
		return $strings;
	}

	private function assertPageEntityUsages( $expected, $actual, $message = '' ) {
		$this->assertEquals(
			$this->getPageEntityUsageStrings( $expected ),
			$this->getPageEntityUsageStrings( $actual ),
			$message
		);
	}

}
