<?php

namespace Wikibase;

/**
 * Represents a page on a given external Site.
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
 * @since 0.4
 *
 * @file
 *
 * @licence GNU GPL v2+
 * @author Marius Hoch < hoo@online.de >
 */
class SiteTitle {

	/**
	 * @since 0.4
	 *
	 * @var string
	 */
	protected $siteId;

	/**
	 * @since 0.4
	 *
	 * @var \Site
	 */
	protected $site;

	/**
	 * @since 0.4
	 *
	 * @var string
	 */
	protected $pageName;

	/**
	 * @since 0.4
	 *
	 * @var string
	 */
	protected $normalizedPageName;

	/**
	 * @since 0.4
	 *
	 * @param string|Site $site Site object or id of the target site
	 * @param string $pageName
	 */
	public function __construct( $site, $pageName ) {
		if ( $site instanceof Site ) {
			$this->site = $site;
			$this->siteId = $this->site->getGlobalId();
		} else {
			$this->siteId = Utils::trimToNFC( $site );
		}

		$this->pageName = Utils::trimToNFC( $pageName );
	}

	/**
	 * Returns the site object for the current page or null if an invalid site id has been given
	 *
	 * @since 0.4
	 *
	 * @return \Site|null
	 */
	public function getSite() {
		if ( !$this->site instanceof Site ) {
			$this->site = \SiteSQLStore::newInstance()->getSite( $this->siteId );
			if ( !$this->site instanceof \Site ) {
				return null;
			}
		}

		return $this->site;
	}

	/**
	 * Returns the page title for use in Wikibase
	 *
	 * @since 0.4
	 *
	 * @return string|null
	 */
	public function normalizePageName() {
		if ( is_string( $this->normalizedPageName ) ) {
			return $this->normalizedPageName;
		}

		if ( \Wikibase\Settings::get( 'normalizeItemByTitlePageNames' ) !== true ) {
			// We store MediaWiki page titles with spaces instead of underscores.
			// This isn't very reliable as it eg. doesn't resolve redirects, but it's
			// magnitudes faster than doing an API request to the target site (like
			// the code block below does).
			return str_replace( '_', ' ', $this->pageName );
		} else {
			// Try harder by requesting normalization on the external site
			$site = $this->getSite();

			if ( !$site ) {
				return null;
			}

			// Note: This will also resolve redirects
			$this->normalizedPageName = $site->normalizePageName( $this->pageName );
			if ( !is_string( $this->normalizedPageName ) ) {
				return null;
			}

			return $this->normalizedPageName;
		}
	}
}
