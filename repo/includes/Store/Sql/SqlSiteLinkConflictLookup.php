<?php

namespace Wikibase\Repo\Store\Sql;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Services\EntityId\EntityIdComposer;
use Wikibase\DataModel\SiteLink;
use Wikibase\Repo\Store\SiteLinkConflictLookup;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\ILoadBalancer;

/**
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class SqlSiteLinkConflictLookup implements SiteLinkConflictLookup {

	/** @var ILoadBalancer */
	private $loadBalancer;

	/**
	 * @var EntityIdComposer
	 */
	private $entityIdComposer;

	public function __construct(
		ILoadBalancer $loadBalancer,
		EntityIdComposer $entityIdComposer
	) {
		$this->loadBalancer = $loadBalancer;
		$this->entityIdComposer = $entityIdComposer;
	}

	/**
	 * @see SiteLinkConflictLookup::getConflictsForItem
	 *
	 * @param Item $item
	 * @param IDatabase|null $db
	 *
	 * @return array[] An array of arrays, each with the keys "siteId", "itemId" and "sitePage".
	 */
	public function getConflictsForItem( Item $item, IDatabase $db = null ) {
		$siteLinks = $item->getSiteLinkList();

		if ( $siteLinks->isEmpty() ) {
			return [];
		}

		if ( $db ) {
			$dbr = $db;
		} else {
			$dbr = $this->loadBalancer->getConnectionRef( ILoadBalancer::DB_REPLICA );
		}

		$anyOfTheLinks = '';

		/** @var SiteLink $siteLink */
		foreach ( $siteLinks as $siteLink ) {
			if ( $anyOfTheLinks !== '' ) {
				$anyOfTheLinks .= "\nOR ";
			}

			$anyOfTheLinks .= '(';
			$anyOfTheLinks .= 'ips_site_id=' . $dbr->addQuotes( $siteLink->getSiteId() );
			$anyOfTheLinks .= ' AND ';
			$anyOfTheLinks .= 'ips_site_page=' . $dbr->addQuotes( $siteLink->getPageName() );
			$anyOfTheLinks .= ')';
		}

		// TODO: $anyOfTheLinks might get very large and hit some size limit imposed
		//       by the database. We could chop it up of we know that size limit.
		//       For MySQL, it's select @@max_allowed_packet.

		$conflictingLinks = $dbr->select(
			'wb_items_per_site',
			[
				'ips_site_id',
				'ips_site_page',
				'ips_item_id',
			],
			"($anyOfTheLinks) AND ips_item_id != " . (int)$item->getId()->getNumericId(),
			__METHOD__
		);

		$conflicts = [];

		foreach ( $conflictingLinks as $link ) {
			$conflicts[] = [
				'siteId' => $link->ips_site_id,
				'itemId' => $this->entityIdComposer->composeEntityId(
					'',
					Item::ENTITY_TYPE,
					$link->ips_item_id
				),
				'sitePage' => $link->ips_site_page,
			];
		}

		return $conflicts;
	}

}
