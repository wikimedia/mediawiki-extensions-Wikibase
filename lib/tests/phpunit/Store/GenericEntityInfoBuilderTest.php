<?php

namespace Wikibase\Lib\Tests\Store;

use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Tests\MockRepository;

/**
 * @covers \Wikibase\Lib\Tests\Store\GenericEntityInfoBuilder
 *
 * @group Wikibase
 * @group WikibaseEntityLookup
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class GenericEntityInfoBuilderTest extends EntityInfoBuilderTestCase {

	/**
	 * @return GenericEntityInfoBuilder
	 */
	protected function newEntityInfoBuilder() {
		$repo = new MockRepository();

		foreach ( $this->getKnownEntities() as $entity ) {
			$repo->putEntity( $entity );
		}

		foreach ( $this->getKnownRedirects() as $from => $toId ) {
			$repo->putRedirect( new EntityRedirect( new ItemId( $from ), $toId ) );
		}

		return new GenericEntityInfoBuilder( new BasicEntityIdParser(), $repo );
	}

}
