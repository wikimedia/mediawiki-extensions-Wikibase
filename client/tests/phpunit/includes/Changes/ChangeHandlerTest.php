<?php

namespace Wikibase\Client\Tests\Changes;

use ArrayIterator;
use Site;
use Title;
use Wikibase\Change;
use Wikibase\Client\Changes\AffectedPagesFinder;
use Wikibase\Client\Changes\ChangeHandler;
use Wikibase\Client\Changes\PageUpdater;
use Wikibase\Client\Store\TitleFactory;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\Client\Usage\PageEntityUsages;
use Wikibase\Client\Usage\UsageLookup;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;
use Wikibase\EntityChange;
use Wikibase\Lib\Store\SiteLinkLookup;
use Wikibase\Lib\Store\StorageException;
use Wikibase\NamespaceChecker;
use Wikibase\Test\MockRepository;
use Wikibase\Test\TestChanges;

/**
 * @covers Wikibase\Client\Changes\ChangeHandler
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseChange
 * @group ChangeHandlerTest
 *
 * @group Database
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ChangeHandlerTest extends \MediaWikiTestCase {

	/** @var Site $site */
	protected $site;

	protected function setUp() {
		parent::setUp();

		$this->site = new \MediaWikiSite();
		$this->site->setGlobalId( 'enwiki' );
		$this->site->setLanguageCode( 'en' );
		$this->site->addNavigationId( 'en' );
	}

	private function newChangeHandler( PageUpdater $updater = null, array $entities = array() ) {
		$repo = $this->getMockRepo( $entities );

		$usageLookup = $this->getUsageLookup( $repo );
		$titleFactory = $this->getTitleFactory( $entities );

		$transformer = $this->getMock( 'Wikibase\Client\Changes\ChangeListTransformer' );

		$transformer->expects( $this->any() )
			->method( 'transformChangeList' )
			->will( $this->returnArgument( 0 ) );

		$namespaceChecker = new NamespaceChecker( array(), array( NS_MAIN ) );

		// @todo: mock the finder directly
		$affectedPagesFinder = new AffectedPagesFinder(
			$usageLookup,
			$namespaceChecker,
			$titleFactory,
			'enwiki',
			'en',
			false
		);

		$handler = new ChangeHandler(
			$affectedPagesFinder,
			$titleFactory,
			$updater ? : new MockPageUpdater(),
			$transformer,
			'enwiki',
			true,
			true
		);

		return $handler;
	}

	private function getMockRepo( array $entities = array() ) {
		$repo = new MockRepository();

		// entity 1, revision 11
		$entity1 = Item::newEmpty();
		$entity1->setId( new ItemId( 'q1' ) );
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
		$entity1 = Item::newEmpty();
		$entity1->setId( new ItemId( 'q2' ) );
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

		$this->updateMockRepo( $repo, $entities );

		return $repo;
	}

	public function provideHandleChanges() {
		$empty = Item::newEmpty();
		$empty->setId( new ItemId( 'q55668877' ) );

		$changeFactory = TestChanges::getEntityChangeFactory();
		$itemCreation = $changeFactory->newFromUpdate( EntityChange::ADD, null, $empty );
		$itemDeletion = $changeFactory->newFromUpdate( EntityChange::REMOVE, $empty, null );

		$itemCreation->setField( 'time', '20130101010101' );
		$itemDeletion->setField( 'time', '20130102020202' );

		return array(
			array(),
			array( $itemCreation ),
			array( $itemDeletion ),
			array( $itemCreation, $itemDeletion ),
		);
	}

	/**
	 * @dataProvider provideHandleChanges
	 */
	public function testHandleChanges() {
		global $handleChangeCallCount, $handleChangesCallCount;
		$changes = func_get_args();

		$testHooks = array(
			'WikibaseHandleChange' => array( function( Change $change ) {
				global $handleChangeCallCount;
				$handleChangeCallCount++;
				return true;
			} ),
			'WikibaseHandleChanges' => array( function( array $changes ) {
				global $handleChangesCallCount;
				$handleChangesCallCount++;
				return true;
			} )
		);

		$this->mergeMwGlobalArrayValue( 'wgHooks', $testHooks );

		$handleChangeCallCount = 0;
		$handleChangesCallCount = 0;

		$changeHandler = $this->newChangeHandler();

		$changeHandler->handleChanges( $changes );

		$this->assertEquals( count( $changes ), $handleChangeCallCount );
		$this->assertEquals( 1, $handleChangesCallCount );

		unset( $handleChangeCallCount );
		unset( $handleChangesCallCount );
	}

	// ==========================================================================================

	public static function provideGetActions() {
		$changes = TestChanges::getChanges();

		$none = 0;
		$any = 0xFFFF;
		$all = ChangeHandler::HISTORY_ENTRY_ACTION
			| ChangeHandler::LINKS_UPDATE_ACTION
			| ChangeHandler::PARSER_PURGE_ACTION
			| ChangeHandler::RC_ENTRY_ACTION
			| ChangeHandler::WEB_PURGE_ACTION;

		return array(
			array( // #0
				$changes['property-creation'], $none, $any
			),
			array( // #1
				$changes['property-deletion'], $none, $any
			),
			array( // #2
				$changes['property-set-label'], $none, $any
			),

			array( // #3
				$changes['item-creation'], $none, $any
			),
			array( // #4
				$changes['item-deletion'], $none, $any
			),
			array( // #5
				$changes['item-deletion-linked'], $all, $none
			),

			array( // #6
				$changes['set-de-label'], $all, $none
			),
			array( // #7
				$changes['set-en-label'], $all, $none // may change
			),
			array( // #8
				$changes['set-en-aliases'], $none, $any
			),

			array( // #9
				$changes['add-claim'], $all, $none
			),
			array( // #10
				$changes['remove-claim'], $all, $none
			),

			array( // #11
				$changes['set-dewiki-sitelink'], $all, $none // may change
			),
			array( // #12
				$changes['set-enwiki-sitelink'], $all, $none // may change
			),

			array( // #13
				$changes['change-dewiki-sitelink'], $all, $none // may change
			),
			array( // #14
				$changes['change-enwiki-sitelink'], $all, $none // may change
			),

			array( // #15
				$changes['remove-dewiki-sitelink'], $all, $none // may change
			),
			array( // #16
				$changes['remove-enwiki-sitelink'], $all, $none // may change
			),
		);
	}

	/**
	 * @dataProvider provideGetActions
	 */
	public function testGetActions( Change $change, $expected, $unexpected ) {
		$handler = $this->newChangeHandler();
		$actions = $handler->getActions( $change );

		$this->assertEquals( $expected, ( $actions & $expected ), "expected actions" );
		$this->assertEquals( 0, ( $actions & $unexpected ), "unexpected actions" );
	}

	public static function provideGetEditComment() {
		$changes = TestChanges::getChanges();

		$dummy = \Title::newFromText( "Dummy" );

		return array(
			array( // #0
				$changes['item-deletion-linked'],
				$dummy,
				array( 'q100' => array( 'Emmy' ) ),
				array( 'message' => 'wikibase-comment-remove' )
			),
			array( // #1
				$changes['set-de-label'],
				$dummy,
				array( 'q100' => array( 'Emmy' ) ),
				'set-de-label:1|'
			),
			array( // #2
				$changes['add-claim'],
				$dummy,
				array( 'q100' => array( 'Emmy' ) ),
				'add-claim:1|'
			),
			array( // #3
				$changes['remove-claim'],
				$dummy,
				array( 'q100' => array( 'Emmy' ) ),
				'remove-claim:1|'
			),
			array( // #4
				$changes['set-dewiki-sitelink'],
				$dummy,
				array( 'q100' => array( 'Emmy' ) ),
				array(
					'sitelink' => array(
						'newlink' => array( 'site' => 'dewiki', 'page' => 'Dummy' ),
					),
					'message' => 'wikibase-comment-sitelink-add'
				)
			),
			array( // #5
				$changes['change-dewiki-sitelink'],
				$dummy,
				array( 'q100' => array( 'Emmy' ) ),
				array(
					'sitelink' => array(
						'oldlink' => array( 'site' => 'dewiki', 'page' => 'Dummy' ),
						'newlink' => array( 'site' => 'dewiki', 'page' => 'Dummy2' ),
					),
					'message' => 'wikibase-comment-sitelink-change'
				)
			),
			array( // #6
				$changes['change-enwiki-sitelink'],
				$dummy,
				array( 'q100' => array( 'Emmy' ) ),
				array(
					'sitelink' => array(
						'oldlink' => array( 'site' => 'enwiki', 'page' => 'Emmy' ),
						'newlink' => array( 'site' => 'enwiki', 'page' => 'Emmy2' ),
					),
					'message' => 'wikibase-comment-sitelink-change'
				)
			),
			array( // #7
				$changes['remove-dewiki-sitelink'],
				$dummy,
				array( 'q100' => array( 'Emmy2' ) ),
				array(
					'sitelink' => array(
						'oldlink' => array( 'site' => 'dewiki', 'page' => 'Dummy2' ),
					),
					'message' => 'wikibase-comment-sitelink-remove'
				)
			),
			array( // #8
				$changes['remove-enwiki-sitelink'],
				$dummy,
				array( 'q100' => array( 'Emmy2' ) ),
				array(
					'message' => 'wikibase-comment-unlink'
				)
			),
			array( // #9
				$changes['remove-enwiki-sitelink'],
				$dummy,
				array( 'q100' => array() ),
				array(
					'message' => 'wikibase-comment-unlink'
				)
			),
		);
	}

	/**
	 * Returns a map of fake local page IDs to the corresponding local page names.
	 * The fake page IDs are the IDs of the items that have a sitelink to the
	 * respective page on the local wiki:
	 *
	 * @example if Q100 has a link enwiki => 'Emmy',
	 * then 100 => 'Emmy' will be in the map returned by this method.
	 *
	 * @param array[] $entities Assoc array mapping entity IDs to lists of sitelinks.
	 * This is the form expected by the $entities parameter of testGetPagesToUpdate, etc.
	 *
	 * @return string[]
	 */
	private function getFakePageIdMap( array $entities ) {
		$titlesByPageId = array();
		$siteId = $this->site->getGlobalId();

		foreach ( $entities as $entityKey => $links ) {
			$id = new ItemId( $entityKey );

			// If $links[0] is set, it's considered a link to the local wiki.
			// The index 0 is effectively an alias for $siteId;
			if ( isset( $links[0] ) ) {
				$links[$siteId] = $links[0];
			}

			if ( isset( $links[$siteId] ) ) {
				$pageId = $id->getNumericId();
				$titlesByPageId[$pageId] = $links[$siteId];
			}
		}

		return $titlesByPageId;
	}

	/**
	 * Title factory, using spoofed local page ids that correspond to the ids of items linked to
	 * the respective page (see getUsageLookup).
	 *
	 * @param array[] $entities Assoc array mapping entity IDs to lists of sitelinks.
	 * This is the form expected by the $entities parameter of testGetPagesToUpdate, etc.
	 *
	 * @return TitleFactory
	 */
	private function getTitleFactory( array $entities ) {
		$titlesById = $this->getFakePageIdMap( $entities );
		$pageIdsByTitle = array_flip( $titlesById );

		$titleFactory = $this->getMock( 'Wikibase\Client\Store\TitleFactory' );

		$titleFactory->expects( $this->any() )
			->method( 'newFromID' )
			->will( $this->returnCallback( function( $id ) use ( $titlesById ) {
				if ( isset( $titlesById[$id] ) ) {
					return Title::newFromText( $titlesById[$id] );
				} else {
					throw new StorageException( 'Unknown ID: ' . $id );
				}
			} ) );

		$titleFactory->expects( $this->any() )
			->method( 'newFromText' )
			->will( $this->returnCallback( function( $text, $defaultNs = NS_MAIN ) use ( $pageIdsByTitle ) {
				$title = Title::newFromText( $text, $defaultNs );

				if ( !$title ) {
					throw new StorageException( 'Bad title text: ' . $text );
				}

				if ( isset( $pageIdsByTitle[$text] ) ) {
					$title->resetArticleID( $pageIdsByTitle[$text] );
				} else {
					throw new StorageException( 'Unknown title text: ' . $text );
				}

				return $title;
			} ) );

		return $titleFactory;
	}

	/**
	 * Returns a usage lookup based on $siteLinklookup.
	 * Local page IDs are spoofed using the numeric item ID as the local page ID.
	 *
	 * @param SiteLinkLookup $siteLinklookup
	 *
	 * @return UsageLookup
	 */
	private function getUsageLookup( SiteLinkLookup $siteLinklookup ) {
		$site = $this->site;

		$usageLookup = $this->getMock( 'Wikibase\Client\Usage\UsageLookup' );
		$usageLookup->expects( $this->any() )
			->method( 'getPagesUsing' )
			->will( $this->returnCallback(
				function( $ids ) use ( $siteLinklookup, $site ) {
					$pages = array();

					foreach ( $ids as $id ) {
						$links = $siteLinklookup->getSiteLinksForItem( $id );
						foreach ( $links as $link ) {
							if ( $link->getSiteId() == $site->getGlobalId() ) {
								// we use the numeric item id as the fake page id of the local page!
								$usages = array(
									new EntityUsage( $id, EntityUsage::SITELINK_USAGE ),
									new EntityUsage( $id, EntityUsage::LABEL_USAGE )
								);
								$pages[] = new PageEntityUsages( $id->getNumericId(), $usages );
							}
						}
					}

					return new ArrayIterator( $pages );
				} ) );

		return $usageLookup;
	}

	/**
	 * @dataProvider provideGetEditComment
	 */
	public function testGetEditComment( Change $change, \Title $title, $entities, $expected ) {
		$handler = $this->newChangeHandler( null, $entities );
		$comment = $handler->getEditComment( $change, $title );

		if ( is_array( $comment ) && is_array( $expected ) ) {
			$this->assertArrayEquals( $expected, $comment, false, true );
		} else {
			$this->assertEquals( $expected, $comment );
		}
	}

	public static function provideGetPagesToUpdate() {
		$changes = TestChanges::getChanges();

		return array(
			array( // #0
				$changes['property-creation'],
				array( 'q100' => array() ),
				array()
			),
			array( // #1
				$changes['property-deletion'],
				array( 'q100' => array() ),
				array()
			),
			array( // #2
				$changes['property-set-label'],
				array( 'q100' => array() ),
				array()
			),

			array( // #3
				$changes['item-creation'],
				array( 'q100' => array() ),
				array()
			),
			array( // #4
				$changes['item-deletion'],
				array( 'q100' => array() ),
				array()
			),
			array( // #5
				$changes['item-deletion-linked'],
				array( 'q100' => array( 'enwiki' => 'Emmy2' ) ),
				array( 'Emmy2' )
			),

			array( // #6
				$changes['set-de-label'],
				array( 'q100' => array( 'enwiki' => 'Emmy2' ) ),
				array(), // For the dummy page, only label and sitelink usage is defined.
			),
			array( // #7
				$changes['set-de-label'],
				array( 'q100' => array( 'enwiki' => 'User:Emmy2' ) ), // bad namespace
				array( )
			),
			array( // #8
				$changes['set-en-label'],
				array( 'q100' => array( 'enwiki' => 'Emmy2' ) ),
				array( 'Emmy2' )
			),
			array( // #8
				$changes['set-en-label'],
				array( 'q100' => array( 'enwiki' => 'User:Emmy2' ) ), // bad namespace
				array( )
			),
			array( // #9
				$changes['set-en-aliases'],
				array( 'q100' => array( 'enwiki' => 'Emmy2' ) ),
				array(), // For the dummy page, only label and sitelink usage is defined.
			),

			array( // #10
				$changes['add-claim'],
				array( 'q100' => array( 'enwiki' => 'Emmy2' ) ),
				array( ) // statements are ignored
			),
			array( // #11
				$changes['remove-claim'],
				array( 'q100' => array( 'enwiki' => 'Emmy2' ) ),
				array( ) // statements are ignored
			),

			array( // #12
				$changes['set-dewiki-sitelink'],
				array( 'q100' => array() ),
				array( ) // not yet linked
			),
			array( // #13
				$changes['set-enwiki-sitelink'],
				array( 'q100' => array( 'enwiki' => 'Emmy' ) ),
				array( 'Emmy' )
			),

			array( // #14
				$changes['change-dewiki-sitelink'],
				array( 'q100' => array( 'enwiki' => 'Emmy' ) ),
				array( 'Emmy' )
			),
			array( // #15
				$changes['change-enwiki-sitelink'],
				array( 'q100' => array( 'enwiki' => 'Emmy' ), 'q200' => array( 'enwiki' => 'Emmy2' ) ),
				array( 'Emmy', 'Emmy2' ),
				true
			),
			array( // #16
				$changes['change-enwiki-sitelink-badges'],
				array( 'q100' => array( 'enwiki' => 'Emmy2' ) ),
				array( 'Emmy2' ) // do we really want/need this to be updated?
			),

			array( // #17
				$changes['remove-dewiki-sitelink'],
				array( 'q100' => array( 'enwiki' => 'Emmy2' ) ),
				array( 'Emmy2' )
			),
			array( // #18
				$changes['remove-enwiki-sitelink'],
				array( 'q100' => array( 'enwiki' => 'Emmy2' ) ),
				array( 'Emmy2' )
			),
		);
	}

	private function updateMockRepo( MockRepository $repo, $entities ) {
		foreach ( $entities as $id => $siteLinks ) {
			if ( !( $siteLinks instanceof Entity ) ) {
				$entity = Item::newEmpty();
				$entity->setId( new ItemId( $id ) );

				foreach ( $siteLinks as $siteId => $page ) {
					if ( is_int( $siteId ) ) {
						$siteIdentifier = $this->site->getGlobalId();
					} else {
						$siteIdentifier = $siteId;
					}

					$entity->addSiteLink( new SiteLink( $siteIdentifier, $page ) );
				}
			} else {
				$entity = $siteLinks;
			}

			$repo->putEntity( $entity );
		}
	}

	private function titles2strings( array $titles ) {
		return array_map(
			function ( Title $title ) {
				return $title->getPrefixedDBKey();
			},
			$titles
		);
	}

	/**
	 * @dataProvider provideGetPagesToUpdate
	 */
	public function testGetPagesToUpdate( Change $change, $entities, array $expected, $dummy = false ) {
		$handler = $this->newChangeHandler( null, $entities );

		$toUpdate = $handler->getPagesToUpdate( $change );
		$toUpdate = $this->titles2strings( $toUpdate );

		$this->assertArrayEquals( $expected, $toUpdate );
	}

	public static function provideUpdatePages() {
		$rc = WikibaseClient::getDefaultInstance()->getSettings()
				->getSetting( 'injectRecentChanges' );

		$pto = self::provideGetPagesToUpdate();

		$cases = array();

		foreach ( $pto as $case ) {
			$updated = $case[2];

			$cases[] = array(
				$case[0], // $change
				$case[1], // $entities
				array(    // $expected // todo: depend on getAction()
					'purgeParserCache' => $updated,
					'purgeWebCache' => $updated,
					'scheduleRefreshLinks' => $updated,
					'injectRCRecord' => ( $rc ? $updated : array() ),
				)
			);
		}

		return $cases;
	}

	/**
	 * @dataProvider provideUpdatePages
	 */
	public function testUpdatePages( Change $change, $entities, array $expected ) {
		$updater = new MockPageUpdater();
		$handler = $this->newChangeHandler( $updater, $entities );

		$toUpdate = $handler->getPagesToUpdate( $change );
		$actions = $handler->getActions( $change );

		$handler->updatePages( $change, $actions, $toUpdate );
		$updates = $updater->getUpdates();

		foreach ( $expected as $k => $exp ) {
			$up = array_keys( $updates[$k] );
			$this->assertArrayEquals( $exp, $up );
		}

		if ( isset( $updates['injectRCRecord'] ) ) {
			foreach ( $updates['injectRCRecord'] as $rcAttr ) {
				$this->assertType( 'array', $rcAttr );
				$this->assertArrayHasKey( 'wikibase-repo-change', $rcAttr );
				$this->assertType( 'array', $rcAttr['wikibase-repo-change'] );
				$this->assertArrayHasKey( 'entity_type', $rcAttr['wikibase-repo-change'] );
			}
		}
	}

	public static function provideHandleChange() {
		return self::provideUpdatePages();
	}

	/**
	 * @dataProvider provideHandleChange
	 */
	public function testHandleChange( Change $change, $entities, array $expected ) {
		$updater = new MockPageUpdater();
		$handler = $this->newChangeHandler( $updater, $entities );

		$handler->handleChange( $change );
		$updates = $updater->getUpdates();

		foreach ( $expected as $k => $exp ) {
			$up = array_keys( $updates[$k] );
			$this->assertArrayEquals( $exp, $up );
		}
	}

}
