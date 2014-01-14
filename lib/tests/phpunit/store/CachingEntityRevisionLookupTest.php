<?php

namespace Wikibase\Test;

use Wikibase\CachingEntityRevisionLookup;
use Wikibase\EntityRevision;
use Wikibase\EntityLookup;

/**
 * @covers Wikibase\CachingEntityLoader
 *
 * @since 0.4
 *
 * @group WikibaseLib
 * @group WikibaseEntityLookup
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class CachingEntityRevisionLookupTest extends EntityRevisionLookupTest {

	/**
	 * @see EntityLookupTest::newEntityLoader(newEntityLookup
	 *
	 * @param EntityRevision[] $entities
	 *
	 * @return EntityLookup
	 */
	protected function newEntityRevisionLookup( array $entities ) {
		$mock = new MockRepository();

		foreach ( $entities as $rev => $entityRev ) {
			$mock->putEntity( $entityRev->getEntity(), $entityRev->getRevision() );
		}

		return new CachingEntityRevisionLookup( $mock, new \HashBagOStuff() );
	}


	//FIXME: test revision verification logic! // DO NOT MERGE WITHOUT!
}
