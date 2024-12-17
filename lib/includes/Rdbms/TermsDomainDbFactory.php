<?php

declare( strict_types=1 );

namespace Wikibase\Lib\Rdbms;

use Wikibase\DataAccess\DatabaseEntitySource;
use Wikimedia\Rdbms\ILBFactory;

/**
 * @license GPL-2.0-or-later
 */
class TermsDomainDbFactory {

	private bool $hasVirtualTermsDomain;
	private ILBFactory $lbFactory;
	private RepoDomainDbFactory $repoDomainDbFactory;

	public function __construct(
		bool $hasVirtualTermsDomain,
		ILBFactory $lbFactory,
		RepoDomainDbFactory $repoDomainDbFactory
	) {
		$this->hasVirtualTermsDomain = $hasVirtualTermsDomain;
		$this->lbFactory = $lbFactory;
		$this->repoDomainDbFactory = $repoDomainDbFactory;
	}

	public function newTermsDb(): TermsDomainDb {
		return $this->hasVirtualTermsDomain ?
			new VirtualTermsDomainDb( $this->lbFactory ) :
			new RepoDomainTermsDb( $this->repoDomainDbFactory->newRepoDb() );
	}

	public function newForEntitySource( DatabaseEntitySource $entitySource ): TermsDomainDb {
		return $this->hasVirtualTermsDomain ?
			new VirtualTermsDomainDb( $this->lbFactory ) :
			new RepoDomainTermsDb( $this->repoDomainDbFactory->newForEntitySource( $entitySource ) );
	}

}
