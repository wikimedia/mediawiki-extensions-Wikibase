<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Integration\Changes;

use ArrayIterator;
use DataValues\DataValue;
use DataValues\StringValue;
use LinkBatch;
use MediaWiki\Cache\LinkBatchFactory;
use MediaWiki\Page\PageSelectQueryBuilder;
use MediaWiki\Page\PageStore;
use MediaWiki\Page\PageStoreRecord;
use MediaWikiIntegrationTestCase;
use Title;
use TitleFactory;
use Traversable;
use Wikibase\Client\Changes\AffectedPagesFinder;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\Client\Usage\PageEntityUsages;
use Wikibase\Client\Usage\UsageLookup;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\Lib\Changes\EntityChange;
use Wikibase\Lib\Tests\Changes\TestChanges;

/**
 * @covers \Wikibase\Client\Changes\AffectedPagesFinder
 *
 * @group Database
 * @group WikibaseClient
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Daniel Kinzler
 */
class AffectedPagesFinderTest extends MediaWikiIntegrationTestCase {

	/**
	 * Returns a TitleFactory that generates Title objects based on the assumption
	 * that a page's title is the same as the page's article ID (in decimal notation).
	 *
	 * @return TitleFactory
	 */
	private function getTitleFactory() {
		$titleFactory = $this->createPartialMock( TitleFactory::class, [ 'newFromText' ] );

		$titleFactory->method( 'newFromText' )
			->willReturnCallback( function( $text, $defaultNs = \NS_MAIN ) {
				$title = Title::newFromText( $text, $defaultNs );

				if ( !$title ) {
					return $title;
				}

				$title->resetArticleID( $text );
				return $title;
			} );

		return $titleFactory;
	}

	/**
	 * Returns a PageStore that generates PageRecords on fetch
	 */
	private function getPageStore(): PageStore {
		$pageStore = $this->createMock( PageStore::class );
		$pageSelectQueryBuilder = $this->getMockBuilder( PageSelectQueryBuilder::class )
			->setConstructorArgs(
				[ $this->db, $pageStore ]
			)
			->onlyMethods( [ 'fetchPageRecords' ] )
			->getMock();

		$pageSelectQueryBuilder->method( 'fetchPageRecords' )->willReturnCallback(
			static function () use ( $pageSelectQueryBuilder ) {
				[ 'conds' => $conds ] = $pageSelectQueryBuilder->getQueryInfo();
				$pageRecords = new ArrayIterator();
				if ( isset( $conds['page_id'] ) ) {
					foreach ( $conds['page_id'] as $pageId ) {
						$pageRecords->append(
							new PageStoreRecord(
								(object)[
									'page_namespace' => NS_MAIN,
									'page_title' => "$pageId",
									'page_id' => $pageId,
									'page_is_redirect' => false,
									'page_is_new' => false,
									'page_latest' => 0,
									'page_touched' => 0,
								],
								PageStoreRecord::LOCAL
							)
						);
					}
				}
				return $pageRecords;
			}
		);

		$pageStore->method( 'newSelectQueryBuilder' )
			->willReturn( $pageSelectQueryBuilder );

		return $pageStore;
	}

	private function getLinkBatchFactory(): LinkBatchFactory {
		$linkBatch = $this->createMock( LinkBatch::class );
		$linkBatch->method( 'execute' )
			->willReturn( null );

		$linkBatchFactory = $this->createMock( LinkBatchFactory::class );
		$linkBatchFactory->method( 'newLinkBatch' )
			->willReturn( $linkBatch );

		return $linkBatchFactory;
	}

	private function getAffectedPagesFinder( array $usage, array $expectedAspects ) {
		$usageLookup = $this->createMock( UsageLookup::class );

		$usageLookup->method( 'getPagesUsing' )
			->with( $this->anything(), $expectedAspects )
			->willReturn( new ArrayIterator( $usage ) );

		$affectedPagesFinder = new AffectedPagesFinder(
			$usageLookup,
			$this->getTitleFactory(),
			$this->getPageStore(),
			$this->getLinkBatchFactory(),
			'enwiki',
			null
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
			),
		];

		$cases['unlink item Q1'] = [
			[ EntityUsage::SITELINK_USAGE, EntityUsage::TITLE_USAGE ],
			$changeFactory->newFromUpdate(
				EntityChange::UPDATE,
				$this->getItemWithSiteLinks( $q1, [ 'enwiki' => '1' ] ),
				new Item( $q1 )
			),
		];

		$cases['link item Q2'] = [
			[ EntityUsage::SITELINK_USAGE, EntityUsage::TITLE_USAGE ],
			$changeFactory->newFromUpdate(
				EntityChange::UPDATE,
				new Item( $q2 ),
				$this->getItemWithSiteLinks( $q2, [ 'enwiki' => '2' ] )
			),
		];

		$cases['change link of Q1'] = [
			[ EntityUsage::SITELINK_USAGE, EntityUsage::TITLE_USAGE ],
			$changeFactory->newFromUpdate(
				EntityChange::UPDATE,
				$this->getItemWithSiteLinks( $q1, [ 'enwiki' => '1' ] ),
				$this->getItemWithSiteLinks( $q1, [ 'enwiki' => '2' ] )
			),
		];

		$cases['delete linked item Q2'] = [
			[ EntityUsage::SITELINK_USAGE, EntityUsage::TITLE_USAGE ],
			$changeFactory->newFromUpdate(
				EntityChange::REMOVE,
				$this->getItemWithSiteLinks( $q2, [ 'enwiki' => '2' ] ),
				null
			),
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
			),
		];

		$cases['alias change on Q1'] = [
			[ EntityUsage::OTHER_USAGE ],
			$changeFactory->newFromUpdate(
				EntityChange::UPDATE,
				new Item( $q1 ),
				$this->getItemWithAliases( $q1, 'de', [ 'EINS' ] )
			),
		];

		$cases['local label change on Q1 (used by Q2)'] = [
			[ EntityUsage::makeAspectKey( EntityUsage::LABEL_USAGE, 'en' ), EntityUsage::makeAspectKey( EntityUsage::LABEL_USAGE ) ],
			$changeFactory->newFromUpdate(
				EntityChange::UPDATE,
				new Item( $q1 ),
				$this->getItemWithLabel( $q1, 'en', 'ONE' )
			),
		];

		$badges = [ new ItemId( 'Q34' ) ];
		$cases['badge only change on Q1'] = [
			[ EntityUsage::SITELINK_USAGE ],
			$changeFactory->newFromUpdate(
				EntityChange::UPDATE,
				$this->getItemWithSiteLinks( $q1, [ 'enwiki' => '1' ] ),
				$this->getItemWithSiteLinks( $q1, [ 'enwiki' => '1' ], $badges ) ),
		];

		$cases['description only change on Q1'] = [
			[ EntityUsage::DESCRIPTION_USAGE, EntityUsage::DESCRIPTION_USAGE . '.en' ],
			$changeFactory->newFromUpdate(
				EntityChange::UPDATE,
				$this->getItemWithDescriptions( $q1, [ 'en' => 'Hello' ] ),
				$this->getItemWithDescriptions( $q1, [ 'en' => 'Hallo' ] ) ),
		];

		$cases['statement change on Q1'] = [
			[ EntityUsage::STATEMENT_USAGE, EntityUsage::STATEMENT_USAGE . '.P5' ],
			$changeFactory->newFromUpdate(
				EntityChange::UPDATE,
				new Item( $q1 ),
				$this->getItemWithStatement( $q1, new NumericPropertyId( 'P5' ), new StringValue( 'Hello' ) )
			),
		];

		return $cases;
	}

	/**
	 * @dataProvider getChangedAspectsProvider
	 */
	public function testGetChangedAspects( array $expected, EntityChange $change ) {
		$referencedPagesFinder = $this->getAffectedPagesFinder( [], [] );

		$actual = $referencedPagesFinder->getChangedAspects( $change );

		sort( $expected );
		sort( $actual );
		$this->assertEquals( $expected, $actual );
	}

	public function getAffectedUsagesByPageProvider() {
		$labelUsage = EntityUsage::LABEL_USAGE;
		$labelUsageDe = EntityUsage::LABEL_USAGE . '.de';
		$labelUsageEn = EntityUsage::LABEL_USAGE . '.en';

		$changeFactory = TestChanges::getEntityChangeFactory();

		$q1 = new ItemId( 'Q1' );
		$q2 = new ItemId( 'Q2' );

		$q1SitelinkUsage = new EntityUsage( $q1, EntityUsage::SITELINK_USAGE );
		$q2SitelinkUsage = new EntityUsage( $q2, EntityUsage::SITELINK_USAGE );
		$q2OtherUsage = new EntityUsage( $q2, EntityUsage::OTHER_USAGE );

		$q1LabelUsage_en = new EntityUsage( $q1, EntityUsage::LABEL_USAGE, 'en' );
		$q2LabelUsage = new EntityUsage( $q2, EntityUsage::LABEL_USAGE );
		$q2LabelUsage_en = new EntityUsage( $q2, EntityUsage::LABEL_USAGE, 'en' );
		$q2LabelUsage_de = new EntityUsage( $q2, EntityUsage::LABEL_USAGE, 'de' );

		$q1TitleUsage = new EntityUsage( $q1, EntityUsage::TITLE_USAGE );
		$q2TitleUsage = new EntityUsage( $q2, EntityUsage::TITLE_USAGE );

		$q2DescriptionUsage = new EntityUsage( $q2, EntityUsage::DESCRIPTION_USAGE );
		$q2DescriptionUsage_en = new EntityUsage( $q2, EntityUsage::DESCRIPTION_USAGE, 'en' );

		$q2StatementUsage_p1 = new EntityUsage( $q2, EntityUsage::STATEMENT_USAGE, 'P1' );
		$q2StatementUsage_p2 = new EntityUsage( $q2, EntityUsage::STATEMENT_USAGE, 'P2' );

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
			$q2StatementUsage_p1,
			$q2TitleUsage,
			$q2SitelinkUsage,
			$q2OtherUsage,
			$q2LabelUsage,
			$q2DescriptionUsage,
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
			),
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
			),
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
			),
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
			),
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
			),
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
				$this->getItemWithSiteLinks( $q1, [ 'enwiki' => '1' ], $badges ) ),
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
			),
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
			),
		];

		$cases['other language label change on Q1 (not used on any page)'] = [
			[],
			[ $labelUsageDe, $labelUsage ],
			[ $page1Q1Usages, $page2Q1Usages ],
			$changeFactory->newFromUpdate(
				EntityChange::UPDATE,
				new Item( $q1 ),
				$this->getItemWithLabel( $q1, 'de', 'EINS' )
			),
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
			),
		];

		$cases['other language label change on Q2 (used on page 1 and 2)'] = [
			[
				new PageEntityUsages( 1, [ $q2LabelUsage, $q2LabelUsage_de ] ),
				new PageEntityUsages( 2, [ $q2LabelUsage, $q2LabelUsage_de ] ),
			],
			[ $labelUsageDe, $labelUsage ],
			[ $page1Q2Usages, $page2Q2Usages ],
			$changeFactory->newFromUpdate(
				EntityChange::UPDATE,
				new Item( $q2 ),
				$this->getItemWithLabel( $q2, 'de', 'EINS' )
			),
		];

		$cases['local label change on Q1 (used by page 2)'] = [
			[
				new PageEntityUsages( 2, [ $q1LabelUsage_en ] ),
			],
			[ $labelUsageEn, $labelUsage ],
			[ $page1Q1Usages, $page2Q1Usages ],
			$changeFactory->newFromUpdate(
				EntityChange::UPDATE,
				new Item( $q1 ),
				$this->getItemWithLabel( $q1, 'en', 'ONE' )
			),
		];

		$cases['local label change on Q2 (used by page 1 and page 2)'] = [
			[
				new PageEntityUsages( 1, [ $q2LabelUsage, $q2LabelUsage_en ] ),
				new PageEntityUsages( 2, [ $q2LabelUsage, $q2LabelUsage_en ] ),
			],
			[ $labelUsageEn, $labelUsage ],
			[ $page1Q2Usages, $page2Q2Usages ],
			$changeFactory->newFromUpdate(
				EntityChange::UPDATE,
				new Item( $q2 ),
				$this->getItemWithLabel( $q2, 'en', 'TWO' )
			),
		];

		$cases['local description change on Q2 (used by page 2)'] = [
			[
				new PageEntityUsages( 2, [ $q2DescriptionUsage, $q2DescriptionUsage_en ] ),
			],
			[ EntityUsage::DESCRIPTION_USAGE . '.en', EntityUsage::DESCRIPTION_USAGE ],
			[ $page2Q2Usages ],
			$changeFactory->newFromUpdate(
				EntityChange::UPDATE,
				new Item( $q2 ),
				$this->getItemWithDescriptions( $q2, [ 'en' => 'Wow' ] )
			),
		];

		$cases['local statement change on Q2 (used by page 2)'] = [
			[
				new PageEntityUsages( 2, [ $q2StatementUsage_p1 ] ),
			],
			[ EntityUsage::STATEMENT_USAGE . '.P1', EntityUsage::STATEMENT_USAGE ],
			[ $page2Q2Usages ],
			$changeFactory->newFromUpdate(
				EntityChange::UPDATE,
				new Item( $q2 ),
				$this->getItemWithStatement( $q2, new NumericPropertyId( 'P1' ), new StringValue( 'Hello' ) )
			),
		];

		$cases['unrelated statement change on Q2 (used by page 2)'] = [
			[],
			[ EntityUsage::STATEMENT_USAGE . '.P2', EntityUsage::STATEMENT_USAGE ],
			[ $page2Q2Usages ],
			$changeFactory->newFromUpdate(
				EntityChange::UPDATE,
				new Item( $q2 ),
				$this->getItemWithStatement( $q2, new NumericPropertyId( 'P2' ), new StringValue( 'Hello' ) )
			),
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
			$this->getTitleFactory(),
			$this->getPageStore(),
			$this->getLinkBatchFactory(),
			'enwiki',
			null
		);

		$itemId = new ItemId( 'Q1' );

		$changeFactory = TestChanges::getEntityChangeFactory();

		$change = $changeFactory->newFromUpdate(
			EntityChange::UPDATE,
			$this->getItemWithSiteLinks( $itemId, [ 'enwiki' => $pageTitle ] ),
			new Item( $itemId )
		);

		$usages = $affectedPagesFinder->getAffectedUsagesByPage( $change );

		$this->assertSame( [], $usages );
	}

	private function getSiteLinkUsageLookup() {
		$pageEntityUsages = [ new PageEntityUsages( 1, [] ) ];
		$mock = $this->createMock( UsageLookup::class );

		$mock->method( 'getPagesUsing' )
			->willReturn( new ArrayIterator( $pageEntityUsages ) );

		return $mock;
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
	 * @param NumericPropertyId $pid
	 * @param DataValue $value
	 *
	 * @return Item
	 */
	private function getItemWithStatement( ItemId $qid, NumericPropertyId $pid, DataValue $value ) {
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
