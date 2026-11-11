<?php

declare( strict_types=1 );

namespace Wikibase\Lib\Store;

use Wikibase\DataAccess\DatabaseEntitySource;
use Wikibase\DataModel\Services\EntityId\EntityIdComposer;
use Wikibase\Lib\Rdbms\TermsDomainDbFactory;
use Wikibase\Lib\Store\Sql\Terms\DatabaseMatchingTermsLookup;

/**
 * @license GPL-2.0-or-later
 */
class MatchingTermsLookupFactory {

	/**
	 * @var EntityIdComposer
	 */
	private $entityIdComposer;

	/**
	 * @var TermsDomainDbFactory
	 */
	private $termsDomainDbFactory;

	public function __construct(
		EntityIdComposer $entityIdComposer,
		TermsDomainDbFactory $termsDomainDbFactory,
	) {
		$this->entityIdComposer = $entityIdComposer;
		$this->termsDomainDbFactory = $termsDomainDbFactory;
	}

	public function getLookupForSource( DatabaseEntitySource $entitySource ): MatchingTermsLookup {
		$termsDb = $this->termsDomainDbFactory->newForEntitySource( $entitySource );

		return new DatabaseMatchingTermsLookup(
			$termsDb,
			$this->entityIdComposer,
		);
	}
}
