<?php

namespace Wikibase\Repo\Tests\Store\Sql;

use MediaWikiTestCase;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\EntityId\EntityIdPager;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Repo\Store\Sql\SqlEntityIdPager;
use Wikibase\Repo\Store\WikiPageEntityStore;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers Wikibase\Repo\Store\Sql\SqlEntityIdPager
 *
 * @group Wikibase
 * @group WikibaseStore
 *
 * @group Database
 *
 * @group medium
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Marius Hoch
 */
class SqlEntityIdPagerTest extends MediaWikiTestCase {

	public function setUp() {
		parent::setUp();
		$this->tablesUsed[] = 'page';
		$this->tablesUsed[] = 'redirect';
	}

	public function addDBDataOnce() {
		// We need to initially empty the table
		wfGetDB( DB_MASTER )->delete( 'page', '*', __METHOD__ );
		wfGetDB( DB_MASTER )->delete( 'redirect', '*', __METHOD__ );
	}

	/**
	 * @param EntityDocument[] $entities
	 * @param EntityRedirect[] $redirects
	 */
	private function insertEntities( array $entities = [], array $redirects = [] ) {
		$pageRows = [];
		foreach ( $entities as $entity ) {
			$pageRows[] = $this->getPageRow( $entity->getId(), false );
		}

		foreach ( $redirects as $redirect ) {
			$pageRows[] = $this->getPageRow( $redirect->getEntityId(), true );
		}

		$dbw = wfGetDB( DB_MASTER );
		$dbw->insert(
			'page',
			$pageRows,
			__METHOD__
		);

		if ( !$redirects ) {
			return;
		}

		$redirectRows = [];
		foreach ( $redirects as $redirect ) {
			$redirectRows[] = $this->getRedirectRow( $redirect );
		}

		$dbw->insert(
			'redirect',
			$redirectRows,
			__METHOD__
		);
	}

	private function getPageRow( EntityId $entityId, $isRedirect ) {
		$entityNamespaceLookup = WikibaseRepo::getDefaultInstance()->getEntityNamespaceLookup();

		return [
			'page_namespace' => $entityNamespaceLookup->getEntityNamespace( $entityId->getEntityType() ),
			'page_title' => $entityId->getSerialization(),
			'page_restrictions' => '',
			'page_random' => 0,
			'page_latest' => 0,
			'page_len' => 1,
			'page_is_redirect' => $isRedirect ? 1 : 0
		];
	}

	private function getRedirectRow( EntityRedirect $redirect ) {
		$entityTitleLookup = WikibaseRepo::getDefaultInstance()->getEntityTitleLookup();

		$redirectTitle = $entityTitleLookup->getTitleForId( $redirect->getEntityId() );
		return [
			'rd_from' => $redirectTitle->getArticleID(),
			'rd_namespace' => $redirectTitle->getNamespace(),
			'rd_title' => $redirectTitle->getDBkey()
		];
	}

	private function getIdStrings( array $entities ) {
		$ids = array_map( function ( $entity ) {
			if ( $entity instanceof EntityDocument ) {
				$entity = $entity->getId();
			} elseif ( $entity instanceof EntityRedirect ) {
				$entity = $entity->getEntityId();
			}

			return $entity->getSerialization();
		}, $entities );

		return $ids;
	}

	private function assertEqualIds( array $expected, array $actual, $msg = null ) {
		$expectedIds = $this->getIdStrings( $expected );
		$actualIds = $this->getIdStrings( $actual );

		$this->assertArrayEquals( $expectedIds, $actualIds, true );
	}

	/**
	 * @dataProvider listEntitiesProvider
	 *
	 * @param array $entityTypes
	 * @param int $limit
	 * @param int $calls
	 * @param string $redirectMode
	 * @param array[] $expectedChunks
	 * @param EntityDocument[] $entitiesToInsert
	 * @param EntityRedirect[] $redirectsToInsert
	 */
	public function testFetchIds(
		array $entityTypes,
		$limit,
		$calls,
		$redirectMode,
		array $expectedChunks,
		array $entitiesToInsert = [],
		array $redirectsToInsert = []
	) {
		$this->insertEntities( $entitiesToInsert, $redirectsToInsert );

		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$pager = new SqlEntityIdPager(
			$wikibaseRepo->getEntityNamespaceLookup(),
			$wikibaseRepo->getEntityIdParser(),
			$entityTypes,
			$redirectMode
		);

		for ( $i = 0; $i < $calls; $i++ ) {
			$actual = $pager->fetchIds( $limit );
			$this->assertEqualIds( $expectedChunks[$i], $actual );
		}
	}

	public function listEntitiesProvider() {
		$property = new Property( new PropertyId( 'P1' ), null, 'string' );
		$item = new Item( new ItemId( 'Q5' ) );
		$redirect = new EntityRedirect( new ItemId( 'Q55' ), new ItemId( 'Q5' ) );

		return [
			'empty' => [
				[],
				100,
				1,
				EntityIdPager::NO_REDIRECTS,
				[ [] ]
			],
			'no matches' => [
				[ Item::ENTITY_TYPE ],
				100,
				1,
				EntityIdPager::NO_REDIRECTS,
				[ [] ],
				[ $property ],
				[ $redirect ]
			],
			'some entities' => [
				[],
				100,
				1,
				EntityIdPager::NO_REDIRECTS,
				[ [ $property, $item ] ],
				[ $property, $item ],
				[ $redirect ]
			],
			'two chunks' => [
				[],
				1,
				2,
				EntityIdPager::NO_REDIRECTS,
				[ [ $property ], [ $item ] ],
				[ $property, $item ],
				[ $redirect ]
			],
			'chunks with limit > 1' => [
				[],
				2,
				3,
				EntityIdPager::INCLUDE_REDIRECTS,
				[ [ $property, $item ], [ $redirect ], [] ],
				[ $property, $item ],
				[ $redirect ]
			],
			'four chunks (two empty)' => [
				[],
				1,
				4,
				EntityIdPager::NO_REDIRECTS,
				[ [ $property ], [ $item ], [], [] ],
				[ $property, $item ],
				[ $redirect ]
			],
			'include redirects' => [
				[],
				100,
				1,
				EntityIdPager::INCLUDE_REDIRECTS,
				[ [ $property, $item, $redirect ] ],
				[ $property, $item ],
				[ $redirect ]
			],
			'only redirects' => [
				[],
				100,
				1,
				EntityIdPager::ONLY_REDIRECTS,
				[ [ $redirect ] ],
				[ $property, $item ],
				[ $redirect ]
			],
			'just properties' => [
				[ Property::ENTITY_TYPE ],
				100,
				1,
				EntityIdPager::NO_REDIRECTS,
				[ [ $property ] ],
				[ $property, $item ],
				[ $redirect ]
			],
			'exactly properties and items' => [
				[ Property::ENTITY_TYPE, Item::ENTITY_TYPE ],
				100,
				1,
				EntityIdPager::NO_REDIRECTS,
				[ [ $property, $item ] ],
				[ $property, $item ],
				[ $redirect ]
			],
			'limit' => [
				[ Property::ENTITY_TYPE ],
				1,
				1,
				EntityIdPager::NO_REDIRECTS,
				[ [ $property ] ],
				[ $property, $item ],
				[ $redirect ]
			]
		];
	}

	public function testSetPosition() {
		$property = new Property( new PropertyId( 'P1' ), null, 'string' );
		$item = new Item( new ItemId( 'Q5' ) );

		$this->insertEntities( [ $property, $item ] );

		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$pager = new SqlEntityIdPager(
			$wikibaseRepo->getEntityNamespaceLookup(),
			$wikibaseRepo->getEntityIdParser()
		);

		$ids = $pager->fetchIds( 2 );

		$this->assertCount( 2, $ids );

		/** @var WikiPageEntityStore $entityStore */
		$entityStore = $wikibaseRepo->getEntityStore();

		$propertyPage = $entityStore->getWikiPageForEntity( $property->getId() );

		$pager->setPosition( $propertyPage->getId() );

		$ids = $pager->fetchIds( 2 );

		$this->assertCount( 1, $ids );

		$this->assertEquals( new ItemId( 'Q5' ), $ids[0] );
	}

	public function testGetPositionReturnsZeroWhenNothingFetchedYet() {
		$entityNamespaceLookup = $this->getMockBuilder( EntityNamespaceLookup::class )
			->disableOriginalConstructor()
			->getMock();

		$pager = new SqlEntityIdPager(
			$entityNamespaceLookup,
			new BasicEntityIdParser()
		);

		$this->assertSame( 0, $pager->getPosition() );
	}

	public function testGetPositionReturnsPageIdOfLastFetchedEntity() {
		$property = new Property( new PropertyId( 'P1' ), null, 'string' );
		$item = new Item( new ItemId( 'Q5' ) );

		$this->insertEntities( [ $property, $item ] );

		$wikibaseRepo = WikibaseRepo::getDefaultInstance();

		/** @var WikiPageEntityStore $entityStore */
		$entityStore = $wikibaseRepo->getEntityStore();

		$itemPage = $entityStore->getWikiPageForEntity( $item->getId() );

		$pager = new SqlEntityIdPager(
			$wikibaseRepo->getEntityNamespaceLookup(),
			$wikibaseRepo->getEntityIdParser()
		);

		$pager->fetchIds( 100 );

		$this->assertSame( $itemPage->getId(), $pager->getPosition() );
	}

	public function testSetCutoffPosition() {
		$entities = [
			new Item( new ItemId( 'Q6' ) ),
			new Property( new PropertyId( 'P1' ), null, 'string' ),
			new Item( new ItemId( 'Q7' ) ),
			new Property( new PropertyId( 'P2' ), null, 'string' ),
			new Item( new ItemId( 'Q8' ) )
		];

		$this->insertEntities( $entities );

		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$pager = new SqlEntityIdPager(
			$wikibaseRepo->getEntityNamespaceLookup(),
			$wikibaseRepo->getEntityIdParser()
		);

		/** @var WikiPageEntityStore $entityStore */
		$entityStore = $wikibaseRepo->getEntityStore();

		$itemPage = $entityStore->getWikiPageForEntity( $entities[2]->getId() );

		$pager->setCutoffPosition( $itemPage->getId() );

		$ids = $pager->fetchIds( 10 );

		$this->assertEquals(
			[ new ItemId( 'Q6' ), new PropertyId( 'P1' ), new ItemId( 'Q7' ) ],
			$ids
		);
		$this->assertSame( $itemPage->getId(), $pager->getPosition() );

		// Now fetch the remaining entity ids
		$pager->setCutoffPosition( null );
		$ids = $pager->fetchIds( 10 );

		$this->assertEquals(
			[ new PropertyId( 'P2' ), new ItemId( 'Q8' ) ],
			$ids
		);
		$this->assertSame( $itemPage->getId() + 2, $pager->getPosition() );
	}

}
