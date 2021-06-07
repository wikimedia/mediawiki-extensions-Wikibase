<?php

declare( strict_types = 1 );

namespace Wikibase\Lib\Store\Sql\Terms;

use Psr\Log\LoggerInterface;
use WANObjectCache;
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

	public function getResolverForEntityType( string $entityType ): DatabaseTermInLangIdsResolver {
		$db = $this->dbFactory->newForEntityType( $entityType );

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
