<?php

namespace Wikibase\Repo\Store\Sql;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Services\EntityId\EntityIdComposer;
use Wikibase\DataModel\SiteLink;
use Wikibase\Lib\Rdbms\RepoDomainDb;
use Wikibase\Repo\Store\SiteLinkConflictLookup;

/**
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class SqlSiteLinkConflictLookup implements SiteLinkConflictLookup {

	/** @var RepoDomainDb */
	private $db;

	/**
	 * @var EntityIdComposer
	 */
	private $entityIdComposer;

	public function __construct(
		RepoDomainDb $db,
		EntityIdComposer $entityIdComposer
	) {
		$this->db = $db;
		$this->entityIdComposer = $entityIdComposer;
	}

	/**
	 * @see SiteLinkConflictLookup::getConflictsForItem
	 *
	 * @param Item $item
	 * @param int|null $db
	 *
	 * @return array[] An array of arrays, each with the keys "siteId", "itemId" and "sitePage".
	 */
	public function getConflictsForItem( Item $item, int $db = null ) {
		$siteLinks = $item->getSiteLinkList();

		if ( $siteLinks->isEmpty() ) {
			return [];
		}

		if ( !$db || $db === DB_REPLICA ) {
			$dbr = $this->db->connections()->getReadConnectionRef();
		} elseif ( $db === DB_PRIMARY ) {
			$dbr = $this->db->connections()->getWriteConnectionRef();
		} else {
			throw new InvalidArgumentException( '$db must be either DB_REPLICA or DB_PRIMARY' );
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
