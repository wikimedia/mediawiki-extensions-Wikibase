<?php

declare( strict_types=1 );

namespace Wikibase\Lib\Store;

use Psr\Log\LoggerInterface;
use WANObjectCache;
use Wikibase\DataAccess\DatabaseEntitySource;
use Wikibase\DataModel\Services\EntityId\EntityIdComposer;
use Wikibase\Lib\Rdbms\RepoDomainDbFactory;
use Wikibase\Lib\Store\Sql\Terms\DatabaseMatchingTermsLookup;
use Wikibase\Lib\Store\Sql\Terms\DatabaseTypeIdsStore;

/**
 * @license GPL-2.0-or-later
 */
class MatchingTermsLookupFactory {

	/**
	 * @var EntityIdComposer
	 */
	private $entityIdComposer;

	/**
	 * @var RepoDomainDbFactory
	 */
	private $repoDomainDbFactory;

	/**
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * @var WANObjectCache
	 */
	private $objectCache;

	public function __construct(
		EntityIdComposer $entityIdComposer,
		RepoDomainDbFactory $repoDomainDbFactory,
		LoggerInterface $logger,
		WANObjectCache $objectCache
	) {
		$this->entityIdComposer = $entityIdComposer;
		$this->repoDomainDbFactory = $repoDomainDbFactory;
		$this->logger = $logger;
		$this->objectCache = $objectCache;
	}

	public function getLookupForSource( DatabaseEntitySource $entitySource ): MatchingTermsLookup {
		$repoDb = $this->repoDomainDbFactory->newForEntitySource( $entitySource );

		$databaseTypeIdsStore = new DatabaseTypeIdsStore(
			$repoDb,
			$this->objectCache,
			$this->logger
		);

		return new DatabaseMatchingTermsLookup(
			$repoDb,
			$databaseTypeIdsStore,
			$databaseTypeIdsStore,
			$this->entityIdComposer,
			$this->logger
		);
	}
}
