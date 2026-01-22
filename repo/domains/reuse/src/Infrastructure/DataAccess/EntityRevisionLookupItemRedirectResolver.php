<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Infrastructure\DataAccess;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Repo\Domains\Reuse\Domain\Services\ItemRedirectResolver;

/**
 * @license GPL-2.0-or-later
 */
class EntityRevisionLookupItemRedirectResolver implements ItemRedirectResolver {

	public function __construct( private readonly EntityRevisionLookup $entityRevisionLookup ) {
	}

	/**
	 * @inheritDoc
	 */
	public function resolveRedirect( ItemId $id ): ItemId {
		return $this->entityRevisionLookup->getLatestRevisionId( $id )
			->onConcreteRevision( fn() => $id )
			->onNonexistentEntity( fn() => $id )
			->onRedirect( fn( $revId, ItemId $redirectTarget ) => $redirectTarget )
			->map();
	}
}
