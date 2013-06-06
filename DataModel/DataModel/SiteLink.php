<?php

namespace Wikibase;

use MWException;
use Site;
use Sites;

/**
 * Class representing a link to another site, based upon the Sites class.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @since 0.1
 *
 * @file
 * @ingroup WikibaseDataModel
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SiteLink {

	/**
	 * Creates a new SiteLink representing a link to the given page on the given site. The page title is normalized
	 * for the SiteLink object is created. If you already have a normalized page title, use the constructor directly.
	 *
	 * @note  : If $normalize is set, this may cause an API request to the remote site, so beware that this function may
	 *          be slow slow and depend on an external service.
	 *
	 * @deprecated since 0.4, use the constructor or Site::newForType
	 *
	 * @param String $globalSiteId     The site's global ID
	 * @param String $page       The target page's title
	 * @param bool   $normalize  Whether the page title should be normalized (default: false)
	 *
	 * @see \Wikibase\Site::normalizePageName()
	 *
	 * @return \Wikibase\SiteLink the new SiteLink
	 * @throws \MWException if the $siteID isn't known.
	 */
	public static function newFromText( $globalSiteId, $page, $normalize = null ) {
		if ( $normalize !== null ) {
			throw new \Exception( 'Support for $normalize parameter has been dropped' );
		}

		$site = Sites::singleton()->getSite( $globalSiteId );

		if ( !$site ) {
			$site = new Site();
			$site->setGlobalId( $globalSiteId );
		}

		return new SiteLink( $site, $page );
	}

	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * @since 0.1
	 * @var String
	 */
	protected $page;

	/**
	 * @since 0.1
	 * @var Site
	 */
	protected $site;

	/**
	 * Constructor.
	 *
	 * @since 0.1
	 *
	 * @param Site   $site  The site the page link points to
	 * @param String $page  The target page's title. This is expected to already be normalized.
	 *
	 * @throws MWException
	 */
	public function __construct( Site $site, $page ) {
		if ( !is_string( $page ) ) {
			throw new MWException( '$page must be a string' );
		}

		$this->site = $site;
		$this->page = $page;
	}

	/**
	 * Returns the target page's title, as provided to the constructor.
	 *
	 * @since 0.1
	 *
	 * @return String
	 */
	public function getPage() {
		return $this->page;
	}

	/**
	 * Returns the target site's Site object.
	 *
	 * @since 0.1
	 *
	 * @return Site
	 */
	public function getSite() {
		return $this->site;
	}

	/**
	 * Returns the target's full URL.
	 *
	 * @since 0.1
	 *
	 * @return string The URL
	 */
	public function getUrl() {
		return $this->site->getPageUrl( $this->getPage() );
	}

	/**
	 * Returns a string representation of this site link
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function __toString() {
		return '[[' . $this->getSite()->getGlobalId() . ':' . $this->getPage() . ']]';
	}

	/**
	 * Converts a list of SiteLink objects to a structure of arrays.
	 *
	 * @since 0.1
	 *
	 * @param array $baseLinks a list of SiteLink objects
	 * @return array an associative array with on entry for each sitelink
	 */
	public static function siteLinksToArray( $baseLinks ) {
		$links = array();

		/**
		 * @var SiteLink $link
		 */
		foreach ( $baseLinks as $link ) {
			$links[ $link->getSite()->getGlobalId() ] = $link->getPage();
		}

		return $links;
	}

}
