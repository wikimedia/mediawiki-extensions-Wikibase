<?php

declare( strict_types=1 );

namespace Wikibase\Lib\Rdbms;

use Wikimedia\Rdbms\ILBFactory;

/**
 * @license GPL-2.0-or-later
 */
class ClientDomainDbFactory {

	private ILBFactory $lbFactory;

	/**
	 * @var string[]
	 */
	private array $loadGroups;

	public function __construct( ILBFactory $lbFactory, array $loadGroups = [] ) {
		$this->lbFactory = $lbFactory;
		$this->loadGroups = $loadGroups;
	}

	public function newLocalDb(): ClientDomainDb {
		$localDomain = $this->lbFactory->getLocalDomainID();

		return new ClientDomainDb(
			$this->lbFactory,
			$localDomain,
			$this->loadGroups
		);
	}

}
