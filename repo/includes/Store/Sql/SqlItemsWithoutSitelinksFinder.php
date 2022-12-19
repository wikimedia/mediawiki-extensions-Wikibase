<?php

namespace Wikibase\Repo\Store\Sql;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Rdbms\RepoDomainDb;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Repo\Store\ItemsWithoutSitelinksFinder;

/**
 * Service for getting Items without sitelinks.
 *
 * @license GPL-2.0-or-later
 * @author Thomas Pellissier Tanon
 * @author Marius Hoch
 */
class SqlItemsWithoutSitelinksFinder implements ItemsWithoutSitelinksFinder {

	/**
	 * @var EntityNamespaceLookup
	 */
	private $entityNamespaceLookup;

	/**
	 * @var RepoDomainDb
	 */
	private $db;

	public function __construct(
		EntityNamespaceLookup $entityNamespaceLookup,
		RepoDomainDb $repoDomainDb
	) {
		$this->entityNamespaceLookup = $entityNamespaceLookup;
		$this->db = $repoDomainDb;
	}

	/**
	 * @param integer $limit Limit of the query.
	 * @param integer $offset Offset of the query.
	 *
	 * @return ItemId[]
	 */
	public function getItemsWithoutSitelinks( $limit = 50, $offset = 0 ) {
		$dbr = $this->db->connections()->getReadConnection();

		$itemIdSerializations = $dbr->selectFieldValues(
			[ 'page', 'page_props' ],
			'page_title',
			[
				'page_namespace' => $this->entityNamespaceLookup->getEntityNamespace( Item::ENTITY_TYPE ),
				'page_is_redirect' => 0,
				'pp_propname' => 'wb-sitelinks',
				$dbr->buildStringCast( 'pp_value' ) => '0',
			],
			__METHOD__,
			[
				'OFFSET' => $offset,
				'LIMIT' => $limit,
				'ORDER BY' => 'page_id DESC',
			],
			[
				'page_props' => [
					'INNER JOIN',
					'pp_page = page_id',
				],
			]
		);

		return $this->getItemIdsFromSerializations( $itemIdSerializations );
	}

	private function getItemIdsFromSerializations( array $itemIdSerializations ) {
		$itemIds = [];

		foreach ( $itemIdSerializations as $itemIdSerialization ) {
			$itemIds[] = new ItemId( $itemIdSerialization );
		}

		return $itemIds;
	}

}
