<?php

namespace Wikibase\Repo\Store\Sql;

use Wikibase\Lib\EntityIdComposer;
use Wikibase\Repo\Store\EntityIdPager;

/**
 * Factory for SqlEntityIdPager objects.
 *
 * @license GPL-2.0+
 * @author Marius Hoch
 */
class SqlEntityIdPagerFactory {

	/**
	 * @var EntityIdComposer
	 */
	private $entityIdComposer;

	/**
	 * @param EntityIdComposer $entityIdComposer
	 */
	public function __construct( EntityIdComposer $entityIdComposer ) {
		$this->entityIdComposer = $entityIdComposer;
	}

	/**
	 * @param null|string $entityType The desired entity type, or null for any type.
	 * @param string $redirectMode A EntityIdPager::XXX_REDIRECTS constant (default is NO_REDIRECTS).
	 *
	 * @return SqlEntityIdPager
	 */
	public function newSqlEntityIdPager( $entityType = null, $redirectMode = EntityIdPager::NO_REDIRECTS ) {
		return new SqlEntityIdPager(
			$this->entityIdComposer,
			$entityType,
			$redirectMode
		);
	}

}
