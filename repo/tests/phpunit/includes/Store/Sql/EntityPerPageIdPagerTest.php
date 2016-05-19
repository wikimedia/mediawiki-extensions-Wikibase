<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Repo\Store\EntityPerPage;
use Wikibase\Repo\Store\SQL\EntityPerPageIdPager;

/**
 * @covers Wikibase\Repo\Store\SQL\EntityPerPageIdPager
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseStore
 * @group WikibaseEntityPerPage
 *
 * @group medium
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class EntityPerPageIdPagerTest extends \MediaWikiTestCase {

	/**
	 * @param EntityId[] $entityIds
	 * @param string|null $type
	 * @param mixed $redirectMode
	 *
	 * @return EntityPerPageIdPager
	 */
	protected function newPager( array $entityIds, $type = null, $redirectMode = EntityPerPage::NO_REDIRECTS ) {
		$keydIds = array();
		foreach ( $entityIds as $entityId ) {
			$key = $entityId->getSerialization();
			$keydIds[$key] = $entityId;
		}

		$listEntities = function(
			$entityType,
			$limit,
			EntityId $after = null,
			$actualRedirectMode = EntityPerPage::NO_REDIRECTS
		) use ( $keydIds, $redirectMode ) {
			$this->assertEquals( $redirectMode, $actualRedirectMode );

			reset( $keydIds );
			while ( $after && current( $keydIds ) && key( $keydIds ) <= $after->getSerialization() ) {
				next( $keydIds );
			}

			$result = array();
			while ( count( $result ) < $limit ) {
				$id = current( $keydIds );
				next( $keydIds );

				if ( !$id ) {
					break;
				}

				if ( $entityType !== null && $entityType !== $id->getEntityType() ) {
					continue;
				}

				$result[] = $id;
			}

			return $result;
		};

		$epp = $this->getMock( EntityPerPage::class );

		$epp->expects( $this->any() )
			->method( 'listEntities' )
			->will( $this->returnCallback( $listEntities ) );

		return new EntityPerPageIdPager( $epp, $type, $redirectMode );
	}

	protected function getIdStrings( array $entities ) {
		return array_map( function( EntityId $id ) {
			return $id->getSerialization();
		}, $entities );
	}

	protected function assertEqualIds( array $expected,array $actual, $msg = null ) {
		$expectedIds = $this->getIdStrings( $expected );
		$actualIds = $this->getIdStrings( $actual );

		$this->assertArrayEquals( $expectedIds, $actualIds, $msg );
	}

	/**
	 * @dataProvider fetchIdsProvider
	 */
	public function testFetchIds( array $entities, $type, $limit, array $expectedChunks ) {
		$pager = $this->newPager( $entities, $type, EntityPerPage::INCLUDE_REDIRECTS );

		foreach ( $expectedChunks as $expected ) {
			$actual = $pager->fetchIds( $limit );

			$this->assertEqualIds( $expected, $actual );
		}
	}

	public function fetchIdsProvider() {
		$property = new PropertyId( 'P5' );
		$item = new ItemId( 'Q1' );
		$item2 = new ItemId( 'Q2' );

		return array(
			'limit' => array(
				array( $property, $item, $item2 ),
				null,
				2,
				array(
					array( $property, $item ),
					array( $item2 ),
					array(),
				)
			),
			'limit and filter' => array(
				array( $item, $property, $item2 ),
				Item::ENTITY_TYPE,
				1,
				array(
					array( $item ),
					array( $item2 ),
					array(),
				)
			)
		);
	}

}
