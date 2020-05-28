<?php

declare( strict_types=1 );

namespace Wikibase\Lib\Store;

use RuntimeException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;

/**
 * Sometimes we need an EntityTermStoreWriter that is not expected to be called.
 *
 * The main use of this class is for ItemHandler and PropertyHandler which do things relating to both reading and writing.
 * If they should only be used for reading, such as with non local entity sources, the service is still needed, but should not be called.
 *
 * @license GPL-2.0-or-later
 */
class ThrowingEntityTermStoreWriter implements EntityTermStoreWriter {

	public function saveTermsOfEntity( EntityDocument $entity ) {
		throw new RuntimeException( "Should never be called" );
	}

	public function deleteTermsOfEntity( EntityId $entityId ) {
		throw new RuntimeException( "Should never be called" );
	}
}
