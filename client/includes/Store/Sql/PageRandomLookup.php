<?php

namespace Wikibase\Client\Store\Sql;

use MediaWiki\Logger\LoggerFactory;
use Wikimedia\Rdbms\ILoadBalancer;

/**
 * Lookup of page.page_random based on page ID.
 *
 * @license GPL-2.0-or-later
 */
class PageRandomLookup {

	/**
	 * @var ILoadBalancer
	 */
	private $loadBalancer;

	public function __construct( ILoadBalancer $loadBalancer ) {
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

		if ( $pageRandom === false || $pageRandom < 0 || $pageRandom > 1 ) {
			$logger = LoggerFactory::getInstance( 'PageRandomLookup' );
			$logger->warning( 'page_random is not a probability: ' . $pageRandom );
			return null;
		}

		return (float)$pageRandom;
	}

}
