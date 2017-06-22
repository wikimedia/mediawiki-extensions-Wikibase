<?php

namespace Wikibase\Repo\Store\Sql;

use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\DataModel\Services\EntityId\EntityIdPager;

/**
 * Factory for SqlEntityIdPager objects.
 *
 * @license GPL-2.0+
 * @author Marius Hoch
 */
class SqlEntityIdPagerFactory {

	/**
	 * @var EntityNamespaceLookup
	 */
	private $entityNamespaceLookup;

	/**
	 * @var EntityIdParser
	 */
	private $entityIdParser;

	public function __construct(
		EntityNamespaceLookup $entityNamespaceLookup,
		EntityIdParser $entityIdParser
	) {
		$this->entityNamespaceLookup = $entityNamespaceLookup;
		$this->entityIdParser = $entityIdParser;
	}

	/**
	 * @param null|string $entityType The desired entity type, or null for any type.
	 * @param string $redirectMode A EntityIdPager::XXX_REDIRECTS constant (default is NO_REDIRECTS).
	 *
	 * @return SqlEntityIdPager
	 */
	public function newSqlEntityIdPager( $entityType = null, $redirectMode = EntityIdPager::NO_REDIRECTS ) {
		return new SqlEntityIdPager(
			$this->entityNamespaceLookup,
			$this->entityIdParser,
			$entityType,
			$redirectMode
		);
	}

}
