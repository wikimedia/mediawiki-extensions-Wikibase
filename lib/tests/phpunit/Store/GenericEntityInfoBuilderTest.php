<?php

namespace Wikibase\Lib\Tests\Store;

use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\GenericEntityInfoBuilder;
use Wikibase\Lib\Tests\MockRepository;

/**
 * @covers Wikibase\Lib\Store\GenericEntityInfoBuilder
 *
 * @group Wikibase
 * @group WikibaseEntityLookup
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class GenericEntityInfoBuilderTest extends EntityInfoBuilderTest {

	/**
	 * @param EntityId[] $ids
	 *
	 * @return GenericEntityInfoBuilder
	 */
	protected function newEntityInfoBuilder( array $ids ) {
		$repo = new MockRepository();

		foreach ( $this->getKnownEntities() as $entity ) {
			$repo->putEntity( $entity );
		}

		foreach ( $this->getKnownRedirects() as $from => $toId ) {
			$repo->putRedirect( new EntityRedirect( new ItemId( $from ), $toId ) );
		}

		return new GenericEntityInfoBuilder( $ids, new BasicEntityIdParser(), $repo );
	}

}
