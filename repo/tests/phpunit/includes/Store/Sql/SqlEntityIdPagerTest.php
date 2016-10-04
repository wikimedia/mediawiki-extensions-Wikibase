<?php

namespace Wikibase\Repo\Tests\Store\Sql;

use MediaWikiTestCase;
use MediaWiki\MediaWikiServices;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\EntityIdComposer;
use Wikibase\Repo\Store\EntityIdPager;
use Wikibase\Repo\Store\Sql\EntityPerPageTable;
use Wikibase\Repo\Store\Sql\SqlEntityIdPager;

/**
 * @covers Wikibase\Repo\Store\Sql\SqlEntityIdPager
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseStore
 *
 * @group Database
 *
 * @group medium
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 * @author Marius Hoch
 */
class SqlEntityIdPagerTest extends MediaWikiTestCase {

	/**
	 * @param EntityDocument[] $entities
	 * @param EntityRedirect[] $redirects
	 */
	private function fillEntityPerPageTable( array $entities = [], array $redirects = [] ) {
		$table = new EntityPerPageTable(
			MediaWikiServices::getInstance()->getDBLoadBalancer(),
			new BasicEntityIdParser(),
			new EntityIdComposer( [] )
		);
		$table->clear();

		foreach ( $entities as $entity ) {
			$pageId = $entity->getId()->getNumericId();
			$table->addEntityPage( $entity->getId(), $pageId );
		}

		foreach ( $redirects as $redirect ) {
			$pageId = $redirect->getEntityId()->getNumericId();
			$table->addRedirectPage( $redirect->getEntityId(), $pageId, $redirect->getTargetId() );
		}
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

		$this->assertArrayEquals( $expectedIds, $actualIds, $msg );
	}

	/**
	 * @dataProvider listEntitiesProvider
	 */
	public function testFetchIds( array $entities, array $redirects, $type, $limit, $calls, $redirectMode, array $expectedChunks ) {
		$this->fillEntityPerPageTable( $entities, $redirects );
		$pager = new SqlEntityIdPager(
			new EntityIdComposer( [] ),
			$type,
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
				[],
				null,
				100,
				1,
				EntityIdPager::NO_REDIRECTS,
				[ [] ]
			],
			'some entities' => [
				[ $item, $property ],
				[ $redirect ],
				null,
				100,
				1,
				EntityIdPager::NO_REDIRECTS,
				[ [ $property, $item ] ]
			],
			'two chunks' => [
				[ $item, $property ],
				[ $redirect ],
				null,
				1,
				2,
				EntityIdPager::NO_REDIRECTS,
				[ [ $property ], [ $item ] ]
			],
			'chunks with limit > 1' => [
				[ $item, $property ],
				[ $redirect ],
				null,
				2,
				3,
				EntityIdPager::INCLUDE_REDIRECTS,
				[ [ $property, $item ], [ $redirect ], [] ]
			],
			'four chunks (two empty)' => [
				[ $item, $property ],
				[ $redirect ],
				null,
				1,
				4,
				EntityIdPager::NO_REDIRECTS,
				[ [ $property ], [ $item ], [], [] ]
			],
			'include redirects' => [
				[ $item, $property ],
				[ $redirect ],
				null,
				100,
				1,
				EntityIdPager::INCLUDE_REDIRECTS,
				[ [ $property, $item, $redirect ] ]
			],
			'only redirects' => [
				[ $item, $property ],
				[ $redirect ],
				null,
				100,
				1,
				EntityIdPager::ONLY_REDIRECTS,
				[ [ $redirect ] ]
			],
			'just properties' => [
				[ $item, $property ],
				[ $redirect ],
				Property::ENTITY_TYPE,
				100,
				1,
				EntityIdPager::NO_REDIRECTS,
				[ [ $property ] ]
			],
			'limit' => [
				[ $item, $property ],
				[ $redirect ],
				Property::ENTITY_TYPE,
				1,
				1,
				EntityIdPager::NO_REDIRECTS,
				[ [ $property ] ] // current sort order is by numeric id, then type.
			],
			'no matches' => [
				[ $property ],
				[ $redirect ],
				Item::ENTITY_TYPE,
				100,
				1,
				EntityIdPager::NO_REDIRECTS,
				[ [] ]
			],
		];
	}

}
