<?php

declare( strict_types=1 );

namespace Wikibase\Lib\Rdbms;

use InvalidArgumentException;
use Wikibase\DataAccess\DatabaseEntitySource;
use Wikimedia\Rdbms\ILBFactory;

/**
 * @license GPL-2.0-or-later
 */
class RepoDomainDbFactory {

	/**
	 * @var string
	 */
	private $repoDomain;

	/**
	 * @var ILBFactory
	 */
	private $lbFactory;

	/**
	 * @var string[]
	 */
	private $loadGroups;

	public function __construct(
		ILBFactory $lbFactory,
		string $repoDomain,
		array $loadGroups = []
	) {
		if ( $repoDomain === '' ) {
			throw new InvalidArgumentException( '"$repoDomain" must not be empty' );
		}

		$this->lbFactory = $lbFactory;
		$this->repoDomain = $repoDomain;
		$this->loadGroups = $loadGroups;
	}

	/**
	 * On a repo wiki, this creates a new RepoDomainDb for the local wiki, on a client it creates a RepoDomainDb for the configured Item and
	 * Property source (via EntitySources). Database operations related to entity data should *not* use this method in most cases and
	 * instead create a RepoDomainDb for the domain specified for the respective entity source.
	 */
	public function newRepoDb(): RepoDomainDb {
		return $this->newForDomain( $this->repoDomain );
	}

	public function newForEntitySource( DatabaseEntitySource $entitySource ): RepoDomainDb {
		return $this->newForDomain(
			$entitySource->getDatabaseName() ?: $this->lbFactory->getLocalDomainID() // db name === false means local db
		);
	}

	private function newForDomain( string $domainId ): RepoDomainDb {
		return new RepoDomainDb(
			$this->lbFactory,
			$domainId,
			$this->loadGroups
		);
	}

}
