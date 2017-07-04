<?php

namespace Wikibase\Client\Store\Sql;

use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\Store\EntityIdLookup;
use Wikimedia\Rdbms\LoadBalancer;

/**
 * Lookup of EntityIds based on wikibase_item entries in the page_props table.
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class PagePropsEntityIdLookup implements EntityIdLookup {

	/**
	 * @var LoadBalancer
	 */
	private $loadBalancer;

	/**
	 * @var EntityIdParser
	 */
	private $idParser;

	public function __construct(
		LoadBalancer $loadBalancer,
		EntityIdParser $idParser
	) {
		$this->loadBalancer = $loadBalancer;
		$this->idParser = $idParser;
	}

	/**
	 * @see EntityIdLookup::getEntityIds
	 *
	 * @param Title[] $titles
	 *
	 * @return EntityId[]
	 */
	public function getEntityIds( array $titles ) {
		$db = $this->loadBalancer->getConnection( DB_REPLICA );

		$pageIds = array_map(
			function ( Title $title ) {
				return $title->getArticleID();
			},
			$titles
		);

		$res = $db->select(
			'page_props',
			[ 'pp_page', 'pp_value' ],
			[
				'pp_page' => $pageIds,
				'pp_propname' => 'wikibase_item',
			],
			__METHOD__
		);

		$entityIds = [];

		foreach ( $res as $row ) {
			$entityIds[$row->pp_page] = $this->idParser->parse( $row->pp_value );
		}

		$this->loadBalancer->reuseConnection( $db );
		return $entityIds;
	}

	/**
	 * @see EntityIdLookup::getEntityIdForTitle
	 *
	 * @param Title $title
	 *
	 * @return EntityId|null
	 */
	public function getEntityIdForTitle( Title $title ) {
		$entityIds = $this->getEntityIds( [ $title ] );

		return reset( $entityIds ) ?: null;
	}

}
