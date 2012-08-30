<?php

namespace Wikibase;
use MWException;

/**
 * Represents a lookup database table for sitelinks.
 * It should have these fields: ips_item_id, ips_site_id, ips_site_page.
 *
 * @since 0.1
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SiteLinkTable {

	/**
	 * @since 0.1
	 *
	 * @var string
	 */
	protected $table;

	/**
	 * @since 0.1
	 *
	 * @param string $table
	 */
	public function __construct( $table ) {
		$this->table = $table;
	}

	/**
	 * Saves the links for the provided item.
	 *
	 * @since 0.1
	 *
	 * @param Item $item
	 * @param string|null $function
	 *
	 * @return boolean Success indicator
	 */
	public function saveLinksOfItem( Item $item, $function = null ) {
		$function = is_null( $function ) ? __METHOD__ : $function;

		$dbw = wfGetDB( DB_MASTER );

		$idField = array( 'ips_item_id' => $item->getId() );

		$success = $dbw->delete(
			$this->table,
			$idField,
			$function
		);

		/* @var SiteLink $siteLink */
		foreach ( $item->getSiteLinks() as $siteLink ) {
			$success = $dbw->insert(
				$this->table,
				array_merge(
					$idField,
					array(
						'ips_site_id' => $siteLink->getGlobalID(),
						'ips_site_page' => $siteLink->getPage(),
					)
				),
				$function
			) && $success;
		}

		return $success;
	}

	/**
	 * Removes the links for the provided item.
	 *
	 * @since 0.1
	 *
	 * @param Item $item
	 * @param string|null $function
	 *
	 * @return boolean Success indicator
	 */
	public function deleteLinksOfItem( Item $item, $function = null ) {
		return wfGetDB( DB_MASTER )->delete(
			$this->table,
			array( 'ips_item_id' => $item->getId() ),
			is_null( $function ) ? __METHOD__ : $function
		);
	}

	/**
	 * Returns the id of the item that is equivalent to the
	 * provided page, or false if there is none.
	 *
	 * @since 0.1
	 *
	 * @param string $globalSiteId
	 * @param string $pageTitle
	 *
	 * @return integer|boolean
	 */
	public function getItemIdForPage( $globalSiteId, $pageTitle ) {
		$result = wfGetDB( DB_SLAVE )->selectRow(
			$this->table,
			array( 'ips_item_id' ),
			array(
				'ips_site_id' => $globalSiteId,
				'ips_site_page' => $pageTitle,
			)
		);

		return $result === false ? false : $result->ips_item_id;
	}

}