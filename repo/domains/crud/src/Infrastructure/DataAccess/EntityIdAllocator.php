<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Infrastructure\DataAccess;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Lib\Store\StorageException;
use Wikibase\Repo\Domains\Crud\Domain\Services\Exceptions\RateLimitReached;

/**
 * @license GPL-2.0-or-later
 */
class EntityIdAllocator {

	public function __construct( private readonly EntityStore $entityStore ) {
	}

	/**
	 * @throws RateLimitReached
	 */
	public function allocateId( EntityDocument $entity ): void {
		try {
			$this->entityStore->assignFreshId( $entity );
		} catch ( StorageException $e ) {
			if ( $e->getStatus()->hasMessage( 'actionthrottledtext' ) ) {
				throw new RateLimitReached();
			}
			throw $e; // It wasn't a rate limit error, so we rethrow as an unexpected exception.
		}
	}

}
