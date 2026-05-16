<?php

namespace Wikibase\Repo\Tests\Dumpers;

use InvalidArgumentException;
use MediaWikiIntegrationTestCase;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\EntityId\EntityIdPager;
use Wikibase\Lib\Store\EntityRevision;

/**
 * @license GPL-2.0-or-later
 */
class DumpGeneratorTestBase extends MediaWikiIntegrationTestCase {

	/**
	 * @param EntityId[] $ids
	 *
	 * @return EntityRevision[]
	 */
	public function makeEntityRevisions( array $ids ) {
		$entityRevisions = [];

		foreach ( $ids as $id ) {
			$entity = $this->makeEntity( $id );
			$entityRevision = new EntityRevision( $entity, 12, '19700112134640' );

			$key = $id->getSerialization();
			$entityRevisions[$key] = $entityRevision;
		}

		return $entityRevisions;
	}

	/**
	 * @param EntityId $id
	 *
	 * @throws InvalidArgumentException
	 * @return Item|Property
	 */
	protected function makeEntity( EntityId $id ) {
		if ( $id instanceof ItemId ) {
			$entity = new Item( $id );
			$entity->getSiteLinkList()->addNewSiteLink( 'test', 'Foo' . $id->getSerialization() );
		} elseif ( $id instanceof PropertyId ) {
			$entity = new Property( $id, null, 'wibblywobbly' );
		} else {
			throw new InvalidArgumentException( 'Unsupported entity type ' . $id->getEntityType() );
		}

		$entity->setLabel( 'en', 'label:' . $id->getSerialization() );

		return $entity;
	}

	/**
	 * Callback for providing dummy entity lists for the EntityIdPager mock.
	 *
	 * @param EntityId[] $ids
	 * @param string $entityType
	 * @param int $limit
	 * @param int &$offset
	 *
	 * @return EntityId[]
	 */
	public function listEntities( array $ids, $entityType, $limit, &$offset = 0 ) {
		$result = [];
		$size = count( $ids );

		while ( $offset < $size && count( $result ) < $limit ) {
			$id = $ids[ $offset ];
			$offset++;

			if ( $entityType !== null && $entityType !== $id->getEntityType() ) {
				continue;
			}

			$result[] = $id;
		}

		return $result;
	}

	/**
	 * @param EntityId[] $ids
	 * @param string|null $entityType
	 *
	 * @return EntityIdPager
	 */
	public function makeIdPager( array $ids, $entityType = null ) {
		$pager = $this->createMock( EntityIdPager::class );

		$offset = 0;

		$pager->method( 'fetchIds' )
			->willReturnCallback( function( $limit ) use ( $ids, $entityType, &$offset ) {
				return $this->listEntities( $ids, $entityType, $limit, $offset );
			} );

		return $pager;
	}

}
