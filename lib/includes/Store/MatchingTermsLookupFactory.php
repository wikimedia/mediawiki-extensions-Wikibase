<?php

declare( strict_types=1 );

namespace Wikibase\Lib\Store;

use Psr\Log\LoggerInterface;
use Wikibase\DataAccess\DatabaseEntitySource;
use Wikibase\DataModel\Services\EntityId\EntityIdComposer;
use Wikibase\Lib\Rdbms\TermsDomainDbFactory;
use Wikibase\Lib\Store\Sql\Terms\DatabaseMatchingTermsLookup;
use Wikibase\Lib\Store\Sql\Terms\DatabaseTypeIdsStore;
use Wikimedia\ObjectCache\WANObjectCache;

/**
 * @license GPL-2.0-or-later
 */
class MatchingTermsLookupFactory {

	/**
	 * @var EntityIdComposer
	 */
	private $entityIdComposer;

	/**
	 * @var TermsDomainDbFactory
	 */
	private $termsDomainDbFactory;

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
		TermsDomainDbFactory $termsDomainDbFactory,
		LoggerInterface $logger,
		WANObjectCache $objectCache
	) {
		$this->entityIdComposer = $entityIdComposer;
		$this->termsDomainDbFactory = $termsDomainDbFactory;
		$this->logger = $logger;
		$this->objectCache = $objectCache;
	}

	public function getLookupForSource( DatabaseEntitySource $entitySource ): MatchingTermsLookup {
		$termsDb = $this->termsDomainDbFactory->newForEntitySource( $entitySource );

		$databaseTypeIdsStore = new DatabaseTypeIdsStore(
			$termsDb,
			$this->objectCache,
			$this->logger
		);

		return new DatabaseMatchingTermsLookup(
			$termsDb,
			$databaseTypeIdsStore,
			$databaseTypeIdsStore,
			$this->entityIdComposer,
			$this->logger
		);
	}
}
