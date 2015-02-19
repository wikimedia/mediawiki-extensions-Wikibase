<?php

namespace Wikibase\Client\Tests\Changes;

use ArrayIterator;
use Title;
use Wikibase\Change;
use Wikibase\Client\Changes\AffectedPagesFinder;
use Wikibase\Client\Changes\ChangeHandler;
use Wikibase\Client\Changes\PageUpdater;
use Wikibase\Client\Store\TitleFactory;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\Client\Usage\PageEntityUsages;
use Wikibase\Client\Usage\UsageLookup;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
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

	private function getAffectedPagesFinder( UsageLookup $usageLookup, TitleFactory $titleFactory ) {
		$namespaceChecker = new NamespaceChecker( array(), array( NS_MAIN ) );

		// @todo: mock the finder directly
		return new AffectedPagesFinder(
			$usageLookup,
			$namespaceChecker,
			$titleFactory,
			'enwiki',
			'en',
			false
		);
	}

	private function getChangeListTransformer() {
		$transformer = $this->getMock( 'Wikibase\Client\Changes\ChangeListTransformer' );

		$transformer->expects( $this->any() )
			->method( 'transformChangeList' )
			->will( $this->returnArgument( 0 ) );

		return $transformer;
	}

	private function getChangeHandler(
		array $pageNamesPerItemId = array(),
		PageUpdater $updater = null
	) {
		$mockRepository = $this->getMockRepository( $pageNamesPerItemId );
		$usageLookup = $this->getUsageLookup( $mockRepository );
		$titleFactory = $this->getTitleFactory( $pageNamesPerItemId );
		$affectedPagesFinder = $this->getAffectedPagesFinder( $usageLookup, $titleFactory );
		$changeListTransformer = $this->getChangeListTransformer();

		$handler = new ChangeHandler(
			$affectedPagesFinder,
			$titleFactory,
			$updater ?: new MockPageUpdater(),
			$changeListTransformer,
			'enwiki',
			true
		);

		return $handler;
	}

	private function getMockRepository( array $pageNamesPerItemId ) {
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

		$this->updateMockRepo( $repo, $pageNamesPerItemId );

		return $repo;
	}

	public function provideHandleChanges() {
		$empty = new Item( new ItemId( 'Q55668877' ) );

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

		$changeHandler = $this->getChangeHandler();

		$changeHandler->handleChanges( $changes );

		$this->assertEquals( count( $changes ), $handleChangeCallCount );
		$this->assertEquals( 1, $handleChangesCallCount );

		unset( $handleChangeCallCount );
		unset( $handleChangesCallCount );
	}

	public function provideGetUpdateActions() {
		return array(
			'empty' => array(
				array(),
				array(),
			),
			'sitelink usage' => array( // #1
				array( EntityUsage::SITELINK_USAGE ),
				array( ChangeHandler::LINKS_UPDATE_ACTION, ChangeHandler::WEB_PURGE_ACTION, ChangeHandler::RC_ENTRY_ACTION ),
				array( ChangeHandler::PARSER_PURGE_ACTION )
			),
			'label usage' => array(
				array( EntityUsage::LABEL_USAGE ),
				array( ChangeHandler::PARSER_PURGE_ACTION, ChangeHandler::WEB_PURGE_ACTION, ChangeHandler::RC_ENTRY_ACTION ),
				array( ChangeHandler::LINKS_UPDATE_ACTION )
			),
			'title usage' => array(
				array( EntityUsage::TITLE_USAGE ),
				array( ChangeHandler::PARSER_PURGE_ACTION, ChangeHandler::WEB_PURGE_ACTION, ChangeHandler::RC_ENTRY_ACTION ),
				array( ChangeHandler::LINKS_UPDATE_ACTION )
			),
			'other usage' => array(
				array( EntityUsage::OTHER_USAGE ),
				array( ChangeHandler::PARSER_PURGE_ACTION, ChangeHandler::WEB_PURGE_ACTION, ChangeHandler::RC_ENTRY_ACTION ),
				array( ChangeHandler::LINKS_UPDATE_ACTION )
			),
			'all usage' => array(
				array( EntityUsage::ALL_USAGE ),
				array( ChangeHandler::PARSER_PURGE_ACTION, ChangeHandler::WEB_PURGE_ACTION, ChangeHandler::RC_ENTRY_ACTION ),
				array()
			),
			'sitelink and other usage (does links update)' => array(
				array( EntityUsage::SITELINK_USAGE, EntityUsage::OTHER_USAGE ),
				array( ChangeHandler::LINKS_UPDATE_ACTION, ChangeHandler::PARSER_PURGE_ACTION, ChangeHandler::WEB_PURGE_ACTION, ChangeHandler::RC_ENTRY_ACTION ),
				array()
			),
		);
	}

	/**
	 * @dataProvider provideGetUpdateActions
	 */
	public function testGetUpdateActions( $aspects, $expected, $not = array() ) {
		$handler = $this->getChangeHandler();
		$actions = $handler->getUpdateActions( $aspects );

		sort( $expected );
		sort( $actions );

		// check that $actions contains AT LEAST $expected
		$actual = array_intersect( $actions, $expected );
		$this->assertEquals( array_values( $expected ), array_values( $actual ), 'expected actions' );

		$unexpected = array_intersect( $actions, $not );
		$this->assertEmpty( array_values( $unexpected ), 'unexpected actions: ' . implode( '|', $unexpected ) );
	}

	public function provideGetEditComment() {
		$changes = TestChanges::getChanges();

		$dummy = Title::newFromText( 'Dummy' );

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
	 * @param array[] $pageNamesPerItemId Assoc array mapping entity IDs to lists of sitelinks.
	 *
	 * @return string[]
	 */
	private function getFakePageIdMap( array $pageNamesPerItemId ) {
		$titlesByPageId = array();
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
	 * @param SiteLinkLookup $siteLinkLookup
	 *
	 * @return UsageLookup
	 */
	private function getUsageLookup( SiteLinkLookup $siteLinkLookup ) {
		$usageLookup = $this->getMock( 'Wikibase\Client\Usage\UsageLookup' );
		$usageLookup->expects( $this->any() )
			->method( 'getPagesUsing' )
			->will( $this->returnCallback(
				function( $ids ) use ( $siteLinkLookup ) {
					$pages = array();

					foreach ( $ids as $id ) {
						if ( !( $id instanceof ItemId ) ) {
							continue;
						}

						$links = $siteLinkLookup->getSiteLinksForItem( $id );
						foreach ( $links as $link ) {
							if ( $link->getSiteId() === 'enwiki' ) {
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
	public function testGetEditComment( Change $change, Title $title, array $pageNamesPerItemId, $expected ) {
		$handler = $this->getChangeHandler( $pageNamesPerItemId );
		$comment = $handler->getEditComment( $change, $title );

		if ( is_array( $comment ) && is_array( $expected ) ) {
			$this->assertArrayEquals( $expected, $comment, false, true );
		} else {
			$this->assertEquals( $expected, $comment );
		}
	}

	/**
	 * @param MockRepository $mockRepository
	 * @param array $pageNamesPerItemId Associative array of item id string => either Item object
	 * or array of site id => page name.
	 */
	private function updateMockRepo( MockRepository $mockRepository, array $pageNamesPerItemId ) {
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

		$empty = array(
			'purgeParserCache' => array(),
			'scheduleRefreshLinks' => array(),
			'purgeWebCache' => array(),
			'injectRCRecord' => array(),
		);

		$emmy2PurgeParser = array(
			'purgeParserCache' => array( 'Emmy2' => true ),
			'scheduleRefreshLinks' => array(),
			'purgeWebCache' => array( 'Emmy2' => true ),
			'injectRCRecord' => array( 'Emmy2' => true ),
		);

		$emmyUpdateLinks = array(
			'purgeParserCache' => array(),
			'scheduleRefreshLinks' => array( 'Emmy' => true ),
			'purgeWebCache' => array( 'Emmy' => true ),
			'injectRCRecord' => array( 'Emmy' => true ),
		);

		$emmy2UpdateLinks = array(
			'purgeParserCache' => array(),
			'scheduleRefreshLinks' => array( 'Emmy2' => true ),
			'purgeWebCache' => array( 'Emmy2' => true ),
			'injectRCRecord' => array( 'Emmy2' => true ),
		);

		$emmy2UpdateAll = array(
			'purgeParserCache' => array( 'Emmy2' => true ),
			'scheduleRefreshLinks' => array( 'Emmy2' => true ),
			'purgeWebCache' => array( 'Emmy2' => true ),
			'injectRCRecord' => array( 'Emmy2' => true ),
		);

		return array(
			array( // #0
				$changes['property-creation'],
				array( 'q100' => array() ),
				$empty
			),
			array( // #1
				$changes['property-deletion'],
				array( 'q100' => array() ),
				$empty
			),
			array( // #2
				$changes['property-set-label'],
				array( 'q100' => array() ),
				$empty
			),

			array( // #3
				$changes['item-creation'],
				array( 'q100' => array() ),
				$empty
			),
			array( // #4
				$changes['item-deletion'],
				array( 'q100' => array() ),
				$empty
			),
			array( // #5
				$changes['item-deletion-linked'],
				array( 'q100' => array( 'enwiki' => 'Emmy2' ) ),
				$emmy2UpdateAll
			),

			array( // #6
				$changes['set-de-label'],
				array( 'q100' => array( 'enwiki' => 'Emmy2' ) ),
				$empty, // For the dummy page, only label and sitelink usage is defined.
			),
			array( // #7
				$changes['set-en-label'],
				array( 'q100' => array( 'enwiki' => 'Emmy2' ) ),
				$emmy2PurgeParser
			),
			array( // #8
				$changes['set-en-label'],
				array( 'q100' => array( 'enwiki' => 'User:Emmy2' ) ), // bad namespace
				$empty
			),
			array( // #9
				$changes['set-en-aliases'],
				array( 'q100' => array( 'enwiki' => 'Emmy2' ) ),
				$empty, // For the dummy page, only label and sitelink usage is defined.
			),

			array( // #10
				$changes['add-claim'],
				array( 'q100' => array( 'enwiki' => 'Emmy2' ) ),
				$empty // statements are ignored
			),
			array( // #11
				$changes['remove-claim'],
				array( 'q100' => array( 'enwiki' => 'Emmy2' ) ),
				$empty // statements are ignored
			),

			array( // #12
				$changes['set-dewiki-sitelink'],
				array( 'q100' => array() ),
				$empty // not yet linked
			),
			array( // #13
				$changes['set-enwiki-sitelink'],
				array( 'q100' => array( 'enwiki' => 'Emmy' ) ),
				$emmyUpdateLinks
			),

			array( // #14
				$changes['change-dewiki-sitelink'],
				array( 'q100' => array( 'enwiki' => 'Emmy' ) ),
				$emmyUpdateLinks
			),
			array( // #15
				$changes['change-enwiki-sitelink'],
				array( 'q100' => array( 'enwiki' => 'Emmy' ), 'q200' => array( 'enwiki' => 'Emmy2' ) ),
				array(
					'purgeParserCache' => array(),
					'scheduleRefreshLinks' => array( 'Emmy' => true, 'Emmy2' => true ),
					'purgeWebCache' => array( 'Emmy' => true, 'Emmy2' => true ),
					'injectRCRecord' => array( 'Emmy' => true, 'Emmy2' => true ),
				)
			),
			array( // #16
				$changes['change-enwiki-sitelink-badges'],
				array( 'q100' => array( 'enwiki' => 'Emmy2' ) ),
				$emmy2UpdateLinks
			),

			array( // #17
				$changes['remove-dewiki-sitelink'],
				array( 'q100' => array( 'enwiki' => 'Emmy2' ) ),
				$emmy2UpdateLinks
			),
			array( // #18
				$changes['remove-enwiki-sitelink'],
				array( 'q100' => array( 'enwiki' => 'Emmy2' ) ),
				$emmy2UpdateLinks
			),
		);
	}

	/**
	 * @dataProvider provideHandleChange
	 */
	public function testHandleChange( Change $change, array $pageNamesPerItemId, array $expected ) {
		$updater = new MockPageUpdater();
		$handler = $this->getChangeHandler( $pageNamesPerItemId, $updater );

		$handler->handleChange( $change );
		$updates = $updater->getUpdates();

		$this->assertSameSize( $expected, $updates );

		foreach ( $expected as $k => $exp ) {
			$up = $updates[$k];
			$this->assertEquals( array_keys( $exp ), array_keys( $up ), $k );
		}
	}

}
