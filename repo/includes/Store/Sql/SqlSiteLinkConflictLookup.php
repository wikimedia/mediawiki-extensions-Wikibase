<?php

namespace Wikibase\Repo\Store\Sql;

use DBAccessBase;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\SiteLink;
use Wikibase\Lib\EntityIdComposer;
use Wikibase\Repo\Store\SiteLinkConflictLookup;
use Wikimedia\Rdbms\IDatabase;

/**
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class SqlSiteLinkConflictLookup extends DBAccessBase implements SiteLinkConflictLookup {

	/**
	 * @var EntityIdComposer
	 */
	private $entityIdComposer;

	public function __construct( EntityIdComposer $entityIdComposer ) {
		parent::__construct();

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
			$dbr = $this->getConnection( DB_REPLICA );
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

		if ( !$db ) {
			$this->releaseConnection( $dbr );
		}

		return $conflicts;
	}

}
