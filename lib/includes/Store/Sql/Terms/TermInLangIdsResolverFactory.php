<?php

declare( strict_types = 1 );

namespace Wikibase\Lib\Store\Sql\Terms;

use Psr\Log\LoggerInterface;
use WANObjectCache;
use Wikimedia\Rdbms\ILBFactory;

/**
 * @license GPL-2.0-or-later
 */
class TermInLangIdsResolverFactory {
	/**
	 * @var ILBFactory
	 */
	private $loadBalancerFactory;
	/**
	 * @var LoggerInterface
	 */
	private $logger;
	/**
	 * @var WANObjectCache
	 */
	private $objectCache;

	/**
	 * @param ILBFactory $loadBalancerFactory
	 * @param LoggerInterface $logger
	 * @param WANObjectCache $objectCache
	 */
	public function __construct(
		ILBFactory $loadBalancerFactory,
		LoggerInterface $logger,
		WANObjectCache $objectCache
	) {
		$this->loadBalancerFactory = $loadBalancerFactory;
		$this->logger = $logger;
		$this->objectCache = $objectCache;
	}

	/**
	 * @param string|false $dbName The name of the database to use (use false for the local db)
	 */
	public function getResolverForDatabase( $dbName ): DatabaseTermInLangIdsResolver {
		$loadBalancer = $this->loadBalancerFactory
			->getMainLB( $dbName );

		$databaseTypeIdsStore = new DatabaseTypeIdsStore(
			$loadBalancer,
			$this->objectCache,
			$dbName,
			$this->logger
		);
		return new DatabaseTermInLangIdsResolver(
			$databaseTypeIdsStore,
			$databaseTypeIdsStore,
			$loadBalancer,
			$dbName,
			$this->logger
		);
	}
}
