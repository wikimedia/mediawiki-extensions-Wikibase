<?php

declare( strict_types = 1 );

namespace Wikibase\Lib\Store\Sql\Terms;

use Psr\Log\LoggerInterface;
use Wikibase\DataAccess\DatabaseEntitySource;
use Wikibase\Lib\Rdbms\TermsDomainDbFactory;

/**
 * @license GPL-2.0-or-later
 */
class TermInLangIdsResolverFactory {

	/**
	 * @var TermsDomainDbFactory
	 */
	private $dbFactory;

	/**
	 * @var LoggerInterface
	 */
	private $logger;

	public function __construct(
		TermsDomainDbFactory $dbFactory,
		LoggerInterface $logger
	) {
		$this->logger = $logger;
		$this->dbFactory = $dbFactory;
	}

	public function getResolverForEntitySource( DatabaseEntitySource $entitySource ): DatabaseTermInLangIdsResolver {
		$db = $this->dbFactory->newForEntitySource( $entitySource );

		return new DatabaseTermInLangIdsResolver( $db, $this->logger );
	}
}
