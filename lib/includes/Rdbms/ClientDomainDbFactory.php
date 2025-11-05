<?php

declare( strict_types=1 );

namespace Wikibase\Lib\Rdbms;

use Wikimedia\Rdbms\ILBFactory;

/**
 * @license GPL-2.0-or-later
 */
class ClientDomainDbFactory {

	private ILBFactory $lbFactory;

	public function __construct( ILBFactory $lbFactory ) {
		$this->lbFactory = $lbFactory;
	}

	public function newLocalDb(): ClientDomainDb {
		$localDomain = $this->lbFactory->getLocalDomainID();

		return new ClientDomainDb(
			$this->lbFactory,
			$localDomain
		);
	}

}
