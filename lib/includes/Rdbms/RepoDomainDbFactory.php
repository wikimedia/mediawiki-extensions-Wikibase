<?php

declare( strict_types=1 );

namespace Wikibase\Lib\Rdbms;

use InvalidArgumentException;
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

	public function __construct( ILBFactory $lbFactory, string $repoDomain, array $loadGroups = [] ) {
		if ( $repoDomain === '' ) {
			throw new InvalidArgumentException( '"$repoDomain" must not be empty' );
		}

		$this->repoDomain = $repoDomain;
		$this->lbFactory = $lbFactory;
		$this->loadGroups = $loadGroups;
	}

	/**
	 * On a repo wiki, this creates a new RepoDomainDb for the local wiki, on a client it creates a RepoDomainDb for the configured Item and
	 * Property source (via EntitySources). Database operations related to entity data should *not* use this method in most cases and
	 * instead create a RepoDomainDb for the domain specified for the respective entity source.
	 */
	public function newRepoDb(): RepoDomainDb {
		return new RepoDomainDb(
			$this->lbFactory,
			$this->repoDomain,
			$this->loadGroups
		);
	}

}
