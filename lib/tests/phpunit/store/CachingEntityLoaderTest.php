<?php

namespace Wikibase\Test;

use Wikibase\CachingEntityLoader;
use Wikibase\Item;
use Wikibase\Query;
use Wikibase\EntityLookup;
use Wikibase\Property;

/**
 * @covers Wikibase\CachingEntityLoader
 *
 * @since 0.4
 *
 * @file
 * @ingroup WikibaseLib
 * @ingroup Test
 *
 * @group WikibaseLib
 * @group WikibaseEntityLookup
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class CachingEntityLoaderTest extends EntityLookupTest {

	/**
	 * @see EntityLookupTest::newEntityLoader()
	 *
	 * @return EntityLookup
	 */
	protected function newEntityLoader( array $entities ) {
		$mock = new MockRepository();

		foreach ( $entities as $rev => $entity ) {
			$mock->putEntity( $entity, $rev );
		}

		return new CachingEntityLoader( $mock );
	}

}
