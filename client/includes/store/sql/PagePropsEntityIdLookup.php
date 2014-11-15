<?php

namespace Wikibase\Client\Store\Sql;

use LoadBalancer;
use Title;
use Wikibase\Client\Store\EntityIdLookup;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\Lib\Store\StorageException;

/**
 * Lookup of EntityIds based on wikibase_item entries in the page_props table.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 *
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

	/**
	 * @param LoadBalancer $loadBalancer
	 * @param EntityIdParser $idParser
	 */
	public function __construct(
		LoadBalancer $loadBalancer,
		EntityIdParser $idParser
	) {
		$this->loadBalancer = $loadBalancer;
		$this->idParser = $idParser;
	}

	/**
	 * @param Title[] $titles
	 *
	 * @throws StorageException
	 * @return EntityId[]
	 */
	public function getEntityIds( array $titles ) {
		$db = $this->loadBalancer->getConnection( DB_READ );

		$pageIds = array_map(
			function ( Title $title ) {
				return $title->getArticleID();
			},
			$titles
		);

		$res = $db->select(
			'page_props',
			array( 'pp_page', 'pp_value' ),
			array(
				'pp_page' => $pageIds,
				'pp_propname' => 'wikibase_item',
			),
			__METHOD__
		);

		$entityIds = array();

		foreach ( $res as $row ) {
			$entityIds[$row->pp_page] = $this->idParser->parse( $row->pp_value );
		}

		$this->loadBalancer->reuseConnection( $db );
		return $entityIds;
	}

}