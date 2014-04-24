<?php

namespace Wikibase\ChangeOp;

use InvalidArgumentException;

/**
 * Factory for ChangeOps that modify SiteLinks.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class SiteLinkChangeOpFactory {

	/**
	 * @param string $siteId
	 * @param string $pageName
	 * @param array|null $badges
	 *
	 * @throws InvalidArgumentException
	 * @return ChangeOp
	 */
	public function newSetSiteLinkOp( $siteId, $pageName, $badges = array() ) {
		return new ChangeOpSiteLink( $siteId, $pageName, $badges );
	}

	/**
	 * @param string $siteId
	 *
	 * @throws InvalidArgumentException
	 * @return ChangeOp
	 */
	public function newRemoveSiteLinkOp( $siteId ) {
		return new ChangeOpSiteLink( $siteId, null );
	}

}
