<?php

namespace Wikibase\DataModel\Entity\Diff;

use Diff\DiffOp\Diff\Diff;
use Diff\Patcher\ListPatcher;
use Diff\Patcher\MapPatcher;
use InvalidArgumentException;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\SiteLinkList;

/**
 * Package private.
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Thiemo Mättig
 */
class SiteLinkListPatcher {

	/**
	 * @var MapPatcher
	 */
	private $patcher;

	public function __construct() {
		$this->patcher = new MapPatcher( false, new ListPatcher() );
	}

	/**
	 * @param SiteLinkList $siteLinks
	 * @param Diff $patch
	 *
	 * @return SiteLinkList
	 * @throws InvalidArgumentException
	 */
	public function getPatchedSiteLinkList( SiteLinkList $siteLinks, Diff $patch ) {
		$baseData = $this->getSiteLinksInDiffFormat( $siteLinks );
		$patchedData = $this->patcher->patch( $baseData, $patch );

		$patchedSiteLinks = new SiteLinkList();

		foreach ( $patchedData as $siteId => $siteLinkData ) {
			if ( array_key_exists( 'name', $siteLinkData ) ) {
				$badges = null;

				if ( array_key_exists( 'badges', $siteLinkData ) ) {
					$badges = array_map(
						function( $idSerialization ) {
							return new ItemId( $idSerialization );
						},
						$siteLinkData['badges']
					);
				}

				$patchedSiteLinks->addNewSiteLink( $siteId, $siteLinkData['name'], $badges );
			}
		}

		return $patchedSiteLinks;
	}

	private function getSiteLinksInDiffFormat( SiteLinkList $siteLinks ) {
		$linksInDiffFormat = array();

		/**
		 * @var SiteLink $siteLink
		 */
		foreach ( $siteLinks as $siteLink ) {
			$linksInDiffFormat[$siteLink->getSiteId()] = array(
				'name' => $siteLink->getPageName(),
				'badges' => array_map(
					function( ItemId $itemId ) {
						return $itemId->getSerialization();
					},
					$siteLink->getBadges()
				)
			);
		}

		return $linksInDiffFormat;
	}

}
