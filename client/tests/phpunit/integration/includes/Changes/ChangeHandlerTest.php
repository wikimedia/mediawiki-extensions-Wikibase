<?php

declare( strict_types = 1 );
namespace Wikibase\Client\Tests\Integration\Changes;

use ArrayIterator;
use InvalidArgumentException;
use MediaWiki\MediaWikiServices;
use MediaWiki\Page\PageSelectQueryBuilder;
use MediaWiki\Page\PageStore;
use MediaWiki\Page\PageStoreRecord;
use MediaWikiIntegrationTestCase;
use Psr\Log\NullLogger;
use Title;
use TitleFactory;
use Wikibase\Client\Changes\AffectedPagesFinder;
use Wikibase\Client\Changes\ChangeHandler;
use Wikibase\Client\Changes\ChangeRunCoalescer;
use Wikibase\Client\Changes\PageUpdater;
use Wikibase\Client\Hooks\WikibaseClientHookRunner;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\Client\Usage\PageEntityUsages;
use Wikibase\Client\Usage\UsageLookup;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Changes\Change;
use Wikibase\Lib\Changes\EntityChange;
use Wikibase\Lib\Store\SiteLinkLookup;
use Wikibase\Lib\Tests\Changes\TestChanges;
use Wikibase\Lib\Tests\MockRepository;

/**
 * @covers \Wikibase\Client\Changes\ChangeHandler
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseChange
 *
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ChangeHandlerTest extends MediaWikiIntegrationTestCase {

	private function getAffectedPagesFinder( UsageLookup $usageLookup, TitleFactory $titleFactory, PageStore $pageStore ) {
		// @todo: mock the finder directly
		return new AffectedPagesFinder(
			$usageLookup,
			$titleFactory,
			$pageStore,
			MediaWikiServices::getInstance()->getLinkBatchFactory(),
			'enwiki',
			null
		);
	}

	/**
	 * @return ChangeRunCoalescer
	 */
	private function getChangeRunCoalescer() {
		$transformer = $this->createMock( ChangeRunCoalescer::class );

		$transformer->method( 'transformChangeList' )
			->willReturnArgument( 0 );

		return $transformer;
	}

	private function getChangeHandler(
		array $pageNamesPerItemId = [],
		PageUpdater $updater = null,
		array $hooks = []
	) {
		$siteLinkLookup = $this->getSiteLinkLookup( $pageNamesPerItemId );
		$usageLookup = $this->getUsageLookup( $siteLinkLookup );
		$titleFactory = $this->getTitleFactory( $pageNamesPerItemId );
		$pageStore = $this->getPageStore( $pageNamesPerItemId );
		$affectedPagesFinder = $this->getAffectedPagesFinder( $usageLookup, $titleFactory, $pageStore );

		$handler = new ChangeHandler(
			$affectedPagesFinder,
			$titleFactory,
			$pageStore,
			$updater ?: new MockPageUpdater(),
			$this->getChangeRunCoalescer(),
			new NullLogger(),
			new WikibaseClientHookRunner( $this->createHookContainer( $hooks ) ),
			true
		);

		return $handler;
	}

	/**
	 * @param array $pageNamesPerItemId
	 *
	 * @return SiteLinkLookup
	 */
	private function getSiteLinkLookup( array $pageNamesPerItemId ) {
		$repo = new MockRepository();

		// entity 1, revision 11
		$entity1 = new Item( new ItemId( 'Q1' ) );
		$entity1->setLabel( 'en', 'one' );
		$repo->putEntity( $entity1, 11 );

		// entity 1, revision 12
		$entity1->setLabel( 'de', 'eins' );
		$repo->putEntity( $entity1, 12 );

		// entity 1, revision 13
		$entity1->setLabel( 'it', 'uno' );
		$repo->putEntity( $entity1, 13 );

		// entity 1, revision 1111
		$entity1->setDescription( 'en', 'the first' );
		$repo->putEntity( $entity1, 1111 );

		// entity 2, revision 21
		$entity1 = new Item( new ItemId( 'Q2' ) );
		$entity1->setLabel( 'en', 'two' );
		$repo->putEntity( $entity1, 21 );

		// entity 2, revision 22
		$entity1->setLabel( 'de', 'zwei' );
		$repo->putEntity( $entity1, 22 );

		// entity 2, revision 23
		$entity1->setLabel( 'it', 'due' );
		$repo->putEntity( $entity1, 23 );

		// entity 2, revision 1211
		$entity1->setDescription( 'en', 'the second' );
		$repo->putEntity( $entity1, 1211 );

		$this->updateMockRepository( $repo, $pageNamesPerItemId );

		return $repo;
	}

	public function provideHandleChanges() {
		$empty = new Item( new ItemId( 'Q55668877' ) );

		$changeFactory = TestChanges::getEntityChangeFactory();
		$itemCreation = $changeFactory->newFromUpdate( EntityChange::ADD, null, $empty );
		$itemDeletion = $changeFactory->newFromUpdate( EntityChange::REMOVE, $empty, null );

		$itemCreation->setField( 'time', '20130101010101' );
		$itemDeletion->setField( 'time', '20130102020202' );

		return [
			[],
			[ $itemCreation ],
			[ $itemDeletion ],
			[ $itemCreation, $itemDeletion ],
		];
	}

	/**
	 * @dataProvider provideHandleChanges
	 */
	public function testHandleChanges( ...$changes ) {
		$spy = (object)[
			'handleChangeCallCount' => 0,
			'handleChangesCallCount' => 0,
		];

		$testHooks = [
			'WikibaseHandleChange' => [ function( Change $change ) use ( $spy ) {
				$spy->handleChangeCallCount++;
				return true;
			} ],
			'WikibaseHandleChanges' => [ function( array $changes ) use ( $spy ) {
				$spy->handleChangesCallCount++;
				return true;
			} ],
		];

		$changeHandler = $this->getChangeHandler( [], null, $testHooks );
		$changeHandler->handleChanges( $changes );

		$this->assertSame( count( $changes ), $spy->handleChangeCallCount );
		$this->assertSame( 1, $spy->handleChangesCallCount );
	}

	/**
	 * Returns a map of fake local page IDs to the corresponding local page names.
	 * The fake page IDs are the IDs of the items that have a sitelink to the
	 * respective page on the local wiki:
	 *
	 * Example: If Q100 has a link enwiki => 'Emmy',
	 * then 100 => 'Emmy' will be in the map returned by this method.
	 *
	 * @param array[] $pageNamesPerItemId Assoc array mapping entity IDs to lists of sitelinks.
	 *
	 * @return string[]
	 */
	private function getFakePageIdMap( array $pageNamesPerItemId ) {
		$titlesByPageId = [];
		$siteId = 'enwiki';

		foreach ( $pageNamesPerItemId as $idString => $pageNames ) {
			$itemId = new ItemId( $idString );

			// If $links[0] is set, it's considered a link to the local wiki.
			// The index 0 is effectively an alias for $siteId;
			if ( isset( $pageNames[0] ) ) {
				$pageNames[$siteId] = $pageNames[0];
			}

			if ( isset( $pageNames[$siteId] ) ) {
				$pageId = $itemId->getNumericId();
				$titlesByPageId[$pageId] = $pageNames[$siteId];
			}
		}

		return $titlesByPageId;
	}

	/**
	 * Title factory, using spoofed local page ids that correspond to the ids of items linked to
	 * the respective page (see getUsageLookup).
	 *
	 * @param array[] $pageNamesPerItemId Assoc array mapping entity IDs to lists of sitelinks.
	 *
	 * @return TitleFactory
	 */
	private function getTitleFactory( array $pageNamesPerItemId ) {
		$titlesById = $this->getFakePageIdMap( $pageNamesPerItemId );
		$pageIdsByTitle = array_flip( $titlesById );

		$titleFactory = $this->createPartialMock( TitleFactory::class, [ 'newFromText' ] );

		$titleFactory->method( 'newFromText' )
			->willReturnCallback( function( $text, $defaultNs = \NS_MAIN ) use ( $pageIdsByTitle ) {
				$title = Title::newFromText( $text, $defaultNs );

				if ( !$title ) {
					return $title;
				}

				if ( isset( $pageIdsByTitle[$text] ) ) {
					$title->resetArticleID( $pageIdsByTitle[$text] );
				} else {
					throw new InvalidArgumentException( 'Unknown title text: ' . $text );
				}

				return $title;
			} );

		return $titleFactory;
	}

	/**
	 * Page store, using spoofed local page ids that correspond to the ids of items linked to
	 * the respective page (see getUsageLookup).
	 *
	 * @param array[] $pageNamesPerItemId Assoc array mapping entity IDs to lists of sitelinks.
	 */
	private function getPageStore( array $pageNamesPerItemId ): PageStore {
		$titlesById = $this->getFakePageIdMap( $pageNamesPerItemId );

		$pageStore = $this->createMock( PageStore::class );
		$pageSelectQueryBuilder = $this->getMockBuilder( PageSelectQueryBuilder::class )
			->setConstructorArgs(
				[ $this->db, $pageStore ]
			)
			->onlyMethods( [ 'fetchPageRecords' ] )
			->getMock();

		$pageSelectQueryBuilder->method( 'fetchPageRecords' )->willReturnCallback(
			function () use ( $pageSelectQueryBuilder, $titlesById ) {
				[ 'conds' => $conds ] = $pageSelectQueryBuilder->getQueryInfo();
				$pageRecords = new ArrayIterator();
				if ( isset( $conds['page_id'] ) ) {
					foreach ( $conds['page_id'] as $pageId ) {
						if ( isset( $titlesById[$pageId] ) ) {
							$pageRecords->append(
								$this->newMockPageRecord( $titlesById[$pageId], $pageId )
							);
						} else {
							throw new InvalidArgumentException( 'Unknown ID: ' . $pageId );
						}
					}
				}
				return $pageRecords;
			}
		);

		$pageStore->method( 'newSelectQueryBuilder' )
			->willReturn( $pageSelectQueryBuilder );

		return $pageStore;
	}

	private function newMockPageRecord( string $pageTitle, int $pageId ) {
		return new PageStoreRecord(
			(object)[
				'page_namespace' => NS_MAIN,
				'page_title' => $pageTitle,
				'page_id' => $pageId,
				'page_is_redirect' => false,
				'page_is_new' => false,
				'page_latest' => 0,
				'page_touched' => 0,
			],
			PageStoreRecord::LOCAL
		);
	}

	/**
	 * Returns a usage lookup based on $siteLinklookup.
	 * Local page IDs are spoofed using the numeric item ID as the local page ID.
	 *
	 * @param SiteLinkLookup $siteLinkLookup
	 *
	 * @return UsageLookup
	 */
	private function getUsageLookup( SiteLinkLookup $siteLinkLookup ) {
		$usageLookup = $this->createMock( UsageLookup::class );
		$usageLookup->method( 'getPagesUsing' )
			->willReturnCallback(
				function( $ids, $aspects ) use ( $siteLinkLookup ) {
					$pages = [];

					foreach ( $ids as $id ) {
						if ( !( $id instanceof ItemId ) ) {
							continue;
						}

						$links = $siteLinkLookup->getSiteLinksForItem( $id );
						foreach ( $links as $link ) {
							if ( $link->getSiteId() === 'enwiki' ) {
								// we use the numeric item id as the fake page id of the local page!
								$usedAspects = array_intersect(
									[ EntityUsage::SITELINK_USAGE, EntityUsage::LABEL_USAGE . '.en' ],
									$aspects
								);
								if ( !$usedAspects ) {
									continue;
								}
								$usages = [];
								foreach ( $usedAspects as $aspect ) {
									$usages[] = new EntityUsage(
										$id,
										EntityUsage::splitAspectKey( $aspect )[0],
										EntityUsage::splitAspectKey( $aspect )[1]
									);
								}
								$pages[] = new PageEntityUsages(
									$id->getNumericId(),
									$usages
								);
							}
						}
					}

					return new ArrayIterator( $pages );
				} );

		return $usageLookup;
	}

	/**
	 * @param MockRepository $mockRepository
	 * @param array $pageNamesPerItemId Associative array of item id string => either Item object
	 * or array of site id => page name.
	 */
	private function updateMockRepository( MockRepository $mockRepository, array $pageNamesPerItemId ) {
		foreach ( $pageNamesPerItemId as $idString => $pageNames ) {
			if ( is_array( $pageNames ) ) {
				$item = new Item( new ItemId( $idString ) );

				foreach ( $pageNames as $siteId => $pageName ) {
					if ( !is_string( $siteId ) ) {
						$siteId = 'enwiki';
					}

					$item->getSiteLinkList()->addNewSiteLink( $siteId, $pageName );
				}
			} else {
				$item = $pageNames;
			}

			$mockRepository->putEntity( $item );
		}
	}

	public function provideHandleChange() {
		$changes = TestChanges::getChanges();
		$userEmmy2 = Title::makeTitle( NS_USER, 'Emmy2' )->getPrefixedText();

		$empty = [
			'scheduleRefreshLinks' => [],
			'purgeWebCache' => [],
			'injectRCRecord' => [],
		];

		$emmy2PurgeParser = [
			'scheduleRefreshLinks' => [ 'Emmy2' => true ],
			'purgeWebCache' => [ 'Emmy2' => true ],
			'injectRCRecord' => [ 'Emmy2' => true ],
		];

		$userEmmy2PurgeParser = [
			'scheduleRefreshLinks' => [ $userEmmy2 => true ],
			'purgeWebCache' => [ $userEmmy2 => true ],
			'injectRCRecord' => [ $userEmmy2 => true ],
		];

		$emmyUpdateLinks = [
			'scheduleRefreshLinks' => [ 'Emmy' => true ],
			'purgeWebCache' => [ 'Emmy' => true ],
			'injectRCRecord' => [ 'Emmy' => true ],
		];

		$emmy2UpdateLinks = [
			'scheduleRefreshLinks' => [ 'Emmy2' => true ],
			'purgeWebCache' => [ 'Emmy2' => true ],
			'injectRCRecord' => [ 'Emmy2' => true ],
		];

		$emmy2UpdateAll = [
			'scheduleRefreshLinks' => [ 'Emmy2' => true ],
			'purgeWebCache' => [ 'Emmy2' => true ],
			'injectRCRecord' => [ 'Emmy2' => true ],
		];

		return [
			[ // #0
				$changes['property-creation'],
				[ 'Q100' => [] ],
				$empty,
			],
			[ // #1
				$changes['property-deletion'],
				[ 'Q100' => [] ],
				$empty,
			],
			[ // #2
				$changes['property-set-label'],
				[ 'Q100' => [] ],
				$empty,
			],

			[ // #3
				$changes['item-creation'],
				[ 'Q100' => [] ],
				$empty,
			],
			[ // #4
				$changes['item-deletion'],
				[ 'Q100' => [] ],
				$empty,
			],
			[ // #5
				$changes['item-deletion-linked'],
				[ 'Q100' => [ 'enwiki' => 'Emmy2' ] ],
				$emmy2UpdateAll,
			],

			[ // #6
				$changes['set-de-label'],
				[ 'Q100' => [ 'enwiki' => 'Emmy2' ] ],
				$empty, // For the dummy page, only label and sitelink usage is defined.
			],
			[ // #7
				$changes['set-en-label'],
				[ 'Q100' => [ 'enwiki' => 'Emmy2' ] ],
				$emmy2PurgeParser,
			],
			[ // #8
				$changes['set-en-label'],
				[ 'Q100' => [ 'enwiki' => $userEmmy2 ] ], // user namespace
				$userEmmy2PurgeParser,
			],
			[ // #9
				$changes['set-en-aliases'],
				[ 'Q100' => [ 'enwiki' => 'Emmy2' ] ],
				$empty, // For the dummy page, only label and sitelink usage is defined.
			],

			[ // #10
				$changes['add-claim'],
				[ 'Q100' => [ 'enwiki' => 'Emmy2' ] ],
				$empty, // statements are ignored
			],
			[ // #11
				$changes['remove-claim'],
				[ 'Q100' => [ 'enwiki' => 'Emmy2' ] ],
				$empty, // statements are ignored
			],

			[ // #12
				$changes['set-dewiki-sitelink'],
				[ 'Q100' => [] ],
				$empty, // not yet linked
			],
			[ // #13
				$changes['set-enwiki-sitelink'],
				[ 'Q100' => [ 'enwiki' => 'Emmy' ] ],
				$emmyUpdateLinks,
			],

			[ // #14
				$changes['change-dewiki-sitelink'],
				[ 'Q100' => [ 'enwiki' => 'Emmy' ] ],
				$emmyUpdateLinks,
			],
			[ // #15
				$changes['change-enwiki-sitelink'],
				[ 'Q100' => [ 'enwiki' => 'Emmy' ], 'Q200' => [ 'enwiki' => 'Emmy2' ] ],
				[
					'scheduleRefreshLinks' => [ 'Emmy' => true, 'Emmy2' => true ],
					'purgeWebCache' => [ 'Emmy' => true, 'Emmy2' => true ],
					'injectRCRecord' => [ 'Emmy' => true, 'Emmy2' => true ],
				],
			],
			[ // #16
				$changes['change-enwiki-sitelink-badges'],
				[ 'Q100' => [ 'enwiki' => 'Emmy2' ] ],
				$emmy2UpdateLinks,
			],

			[ // #17
				$changes['remove-dewiki-sitelink'],
				[ 'Q100' => [ 'enwiki' => 'Emmy2' ] ],
				$emmy2UpdateLinks,
			],
			[ // #18
				$changes['remove-enwiki-sitelink'],
				[ 'Q100' => [ 'enwiki' => 'Emmy2' ] ],
				$emmy2UpdateLinks,
			],
		];
	}

	/**
	 * @dataProvider provideHandleChange
	 */
	public function testHandleChange( EntityChange $change, array $pageNamesPerItemId, array $expected ) {
		$updater = new MockPageUpdater();
		$handler = $this->getChangeHandler( $pageNamesPerItemId, $updater );

		$handler->handleChange( $change );
		$updates = $updater->getUpdates();

		$this->assertSameSize( $expected, $updates );

		foreach ( $expected as $k => $exp ) {
			$up = $updates[$k];
			$this->assertSame( array_keys( $exp ), array_keys( $up ), $k );
		}
	}

	/**
	 * @param int|null $id
	 * @param string $type
	 * @param string $objectId
	 * @param array $info
	 *
	 * @return EntityChange
	 */
	private function newChange( $id, $type, $objectId, $info = [] ) {
		$fields = [
			'id' => $id,
			'time' => '20121212121212',
			'type' => $type,
			'objectid' => $objectId,
			'info' => $info,
		];

		return new EntityChange( $fields );
	}

	public function provideHandleChange_rootJobParams() {
		$ids = [ 18, 19, 17 ]; // note: provide these out of order, to check canonical sorting!
		$regularChange = $this->newChange( 17, 'x~y', 'Q100', [] );
		$coalescedChange = $this->newChange( 0, 'x~y', 'Q100', [ 'change-ids' => $ids ] );
		$strangeChange = $this->newChange( 0, 'x~y', 'Q100', [ 'kittens' => 13 ] );

		$q100 = new ItemId( 'Q100' );
		$usages = [ // note: provide these out of order, to check canonical sorting!
			102 => new PageEntityUsages( 102, [ new EntityUsage( $q100, 'X' ) ] ),
			101 => new PageEntityUsages( 101, [ new EntityUsage( $q100, 'X' ) ] ),
		];

		$titleBatchHash = 'f0b873699a63c858667e54cd071f7d9209faeda1';
		$strangeHash = 'cadbb4899603593164f06a4754f597fdcb2c07b4';

		$regularRootJobParams = [
			'purgeWebCache' => [ 'rootJobSignature' => "title-batch:$titleBatchHash" ],
			'scheduleRefreshLinks' => [ 'rootJobSignature' => "title-batch:$titleBatchHash" ],
			'injectRCRecord' => [ 'rootJobSignature' => "title-batch:$titleBatchHash&change-id:17" ],
		];

		$coalescedRootJobParams = [
			'purgeWebCache' => [ 'rootJobSignature' => "title-batch:$titleBatchHash" ],
			'scheduleRefreshLinks' => [ 'rootJobSignature' => "title-batch:$titleBatchHash" ],
			'injectRCRecord' => [ 'rootJobSignature' => "title-batch:$titleBatchHash&change-batch:17,18,19" ],
		];

		$strangeRootJobParams = [
			'purgeWebCache' => [ 'rootJobSignature' => "title-batch:$titleBatchHash" ],
			'scheduleRefreshLinks' => [ 'rootJobSignature' => "title-batch:$titleBatchHash" ],
			'injectRCRecord' => [ 'rootJobSignature' => "title-batch:$titleBatchHash&change-hash:$strangeHash" ],
		];

		return [
			[ $regularChange, $usages, $regularRootJobParams ],
			[ $coalescedChange, $usages, $coalescedRootJobParams ],
			[ $strangeChange, $usages, $strangeRootJobParams ],
		];
	}

	/**
	 * @dataProvider provideHandleChange_rootJobParams
	 */
	public function testHandleChange_rootJobParams(
		EntityChange $change,
		array $usages,
		array $expectedRootJobParams
	) {
		$updater = new MockPageUpdater();

		$affectedPagesFinder = $this->createMock( AffectedPagesFinder::class );
		$affectedPagesFinder->method( 'getAffectedUsagesByPage' )
			->willReturn( $usages );

		$titleFactory = $this->getMockBuilder( TitleFactory::class )
			->setMethodsExcept( [ 'castFromPageIdentity' ] )
			->getMock();

		$pageStore = $this->createMock( PageStore::class );
		$pageSelectQueryBuilder = $this->getMockBuilder( PageSelectQueryBuilder::class )
			->setConstructorArgs(
				[
					$this->db,
					$pageStore,
				]
			)
			->onlyMethods( [ 'fetchPageRecords' ] )
			->getMock();
		$pageSelectQueryBuilder->method( 'fetchPageRecords' )->willReturnCallback(
			function () use ( $pageSelectQueryBuilder ) {
				[ 'conds' => $conds ] = $pageSelectQueryBuilder->getQueryInfo();
				$pageRecords = new ArrayIterator();
				if ( isset( $conds['page_id'] ) ) {
					foreach ( $conds['page_id'] as $pageId ) {
						$pageRecords->append(
							$this->newMockPageRecord( "Page_No_$pageId", $pageId )
						);
					}
				}
				return $pageRecords;
			}
		);
		$pageStore->method( 'newSelectQueryBuilder' )
			->willReturn( $pageSelectQueryBuilder );

		$handler = new ChangeHandler(
			$affectedPagesFinder,
			$titleFactory,
			$pageStore,
			$updater,
			$this->getChangeRunCoalescer(),
			new NullLogger(),
			new WikibaseClientHookRunner( $this->createHookContainer() )
		);

		$inputRootJobParams = [ 'rootJobTimestamp' => '20171122040506' ];

		$handler->handleChange( $change, $inputRootJobParams );
		$actualRootJobParams = $updater->getRootJobParams();

		$this->assertSameSize( $expectedRootJobParams, $actualRootJobParams );

		foreach ( $expectedRootJobParams as $k => $exp ) {
			$act = $actualRootJobParams[$k];
			if ( $k !== 'scheduleRefreshLinks' ) {
				$this->assertSame( '20171122040506', $act['rootJobTimestamp'], "$k/rootJobTimestamp" );
			}
			$this->assertSame( $exp['rootJobSignature'], $act['rootJobSignature'], "$k/rootJobSignature" );
		}
	}

}
