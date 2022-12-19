<?php

namespace Wikibase\DataModel\Services\Diff\Internal;

use Diff\DiffOp\Diff\Diff;
use Diff\Patcher\ListPatcher;
use Diff\Patcher\MapPatcher;
use InvalidArgumentException;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLinkList;

/**
 * Package private.
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Thiemo Kreuz
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
				$patchedSiteLinks->addNewSiteLink(
					$siteId,
					$siteLinkData['name'],
					$this->getBadgesFromSiteLinkData( $siteLinkData )
				);
			}
		}

		return $patchedSiteLinks;
	}

	/**
	 * @param string[] $siteLinkData
	 *
	 * @return ItemId[]|null
	 */
	private function getBadgesFromSiteLinkData( array $siteLinkData ) {
		if ( !array_key_exists( 'badges', $siteLinkData ) ) {
			return null;
		}

		return array_map(
			static function( $idSerialization ) {
				return new ItemId( $idSerialization );
			},
			$siteLinkData['badges']
		);
	}

	private function getSiteLinksInDiffFormat( SiteLinkList $siteLinks ) {
		$linksInDiffFormat = [];

		foreach ( $siteLinks->toArray() as $siteLink ) {
			$linksInDiffFormat[$siteLink->getSiteId()] = [
				'name' => $siteLink->getPageName(),
				'badges' => array_map(
					static function( ItemId $id ) {
						return $id->getSerialization();
					},
					$siteLink->getBadges()
				),
			];
		}

		return $linksInDiffFormat;
	}

}
