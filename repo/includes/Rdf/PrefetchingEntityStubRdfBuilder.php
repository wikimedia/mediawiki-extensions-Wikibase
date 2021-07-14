<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Rdf;

use Wikibase\DataModel\Entity\EntityId;

/**
 * @license GPL-2.0-or-later
 */
interface PrefetchingEntityStubRdfBuilder extends EntityStubRdfBuilder {

	/**
	 * Mark an entity ID to be prefetched before the entityStub is added.
	 *
	 * @param EntityId $id the entity ID to prefetch stub data for.
	 */
	public function markForPrefetchingEntityStub( EntityId $id ): void;

}
