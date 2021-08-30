<?php

declare( strict_types = 1 );

namespace Wikibase\Lib\Store\Sql\Terms;

use Psr\Log\LoggerInterface;
use WANObjectCache;
use Wikibase\DataAccess\DatabaseEntitySource;
use Wikibase\Lib\Rdbms\RepoDomainDbFactory;

/**
 * @license GPL-2.0-or-later
 */
class TermInLangIdsResolverFactory {

	/**
	 * @var RepoDomainDbFactory
	 */
	private $dbFactory;

	/**
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * @var WANObjectCache
	 */
	private $objectCache;

	public function __construct(
		RepoDomainDbFactory $dbFactory,
		LoggerInterface $logger,
		WANObjectCache $objectCache
	) {
		$this->logger = $logger;
		$this->objectCache = $objectCache;
		$this->dbFactory = $dbFactory;
	}

	public function getResolverForEntitySource( DatabaseEntitySource $entitySource ): DatabaseTermInLangIdsResolver {
		$db = $this->dbFactory->newForEntitySource( $entitySource );

		$databaseTypeIdsStore = new DatabaseTypeIdsStore(
			$db,
			$this->objectCache,
			$this->logger
		);
		return new DatabaseTermInLangIdsResolver(
			$databaseTypeIdsStore,
			$databaseTypeIdsStore,
			$db,
			$this->logger
		);
	}
}
