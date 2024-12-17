<?php

declare( strict_types=1 );

namespace Wikibase\Lib\Rdbms;

use Wikibase\DataAccess\DatabaseEntitySource;

/**
 * @license GPL-2.0-or-later
 */
class TermsDomainDbFactory {

	private RepoDomainDbFactory $repoDomainDbFactory;

	public function __construct( RepoDomainDbFactory $repoDomainDbFactory ) {
		$this->repoDomainDbFactory = $repoDomainDbFactory;
	}

	public function newTermsDb(): TermsDomainDb {
		return new RepoDomainTermsDb( $this->repoDomainDbFactory->newRepoDb() );
	}

	public function newForEntitySource( DatabaseEntitySource $entitySource ): TermsDomainDb {
		return new RepoDomainTermsDb( $this->repoDomainDbFactory->newForEntitySource( $entitySource ) );
	}

}
