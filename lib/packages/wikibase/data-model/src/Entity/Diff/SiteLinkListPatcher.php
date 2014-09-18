<?php

namespace Wikibase\DataModel\Entity\Diff;

use Diff\DiffOp\Diff\Diff;
use Diff\Patcher\ListPatcher;
use Diff\Patcher\MapPatcher;
use InvalidArgumentException;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\TermList;

/**
 * Package private.
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SiteLinkListPatcher {

	/**
	 * @var MapPatcher
	 */
	private $patcher;

	public function __construct() {
		$this->patcher = new MapPatcher();
	}

	/**
	 * @param SiteLinkList $links
	 * @param Diff $patch
	 *
	 * @return SiteLinkList
	 * @throws InvalidArgumentException
	 */
	public function getPatchedSiteLinkList( SiteLinkList $links, Diff $patch ) {
		$patcher = new MapPatcher( false, new ListPatcher() );

		$links = $this->getLinksInDiffFormat( $links );
		$links = $patcher->patch( $links, $patch );

		$siteLinks = new SiteLinkList();

		foreach ( $links as $siteId => $linkData ) {
			if ( array_key_exists( 'name', $linkData ) ) {
				$siteLinks->addSiteLink( new SiteLink(
					$siteId,
					$linkData['name'],
					array_map(
						function( $idSerialization ) {
							return new ItemId( $idSerialization );
						},
						$linkData['badges']
					)
				) );
			}
		}

		return $siteLinks;
	}

	private function getLinksInDiffFormat( SiteLinkList $links ) {
		$linksInDiffFormat = array();

		/**
		 * @var SiteLink $siteLink
		 */
		foreach ( $links as $siteLink ) {
			$linksInDiffFormat[$siteLink->getSiteId()] = array(
				'name' => $siteLink->getPageName(),
				'badges' => array_map(
					function( ItemId $id ) {
						return $id->getSerialization();
					},
					$siteLink->getBadges()
				)
			);
		}

		return $linksInDiffFormat;
	}

}
