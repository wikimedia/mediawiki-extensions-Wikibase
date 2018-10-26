<?php

namespace Wikibase\Client\Store\Sql;

use Wikimedia\Rdbms\LoadBalancer;

/**
 * Lookup of page.page_random based on page ID.
 *
 * @license GPL-2.0-or-later
 */
class PageRandomLookup {

	/**
	 * @var LoadBalancer
	 */
	private $loadBalancer;

	public function __construct( LoadBalancer $loadBalancer ) {
		$this->loadBalancer = $loadBalancer;
	}

	/**
	 * @param int $pageId
	 *
	 * @return float|null
	 */
	public function getPageRandom( $pageId ) {
		$db = $this->loadBalancer->getConnection( DB_REPLICA );

		$pageRandom = $db->selectField( 'page', 'page_random', [ 'page_id' => $pageId ] );

		$this->loadBalancer->reuseConnection( $db );

		return $pageRandom === false ? null : (float)$pageRandom;
	}

}
