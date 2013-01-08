<?php

namespace Wikibase;
use Sites;
use Site;
use Title;
use ParserOutput;

/**
 * Handles language links.
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
 * @ingroup WikibaseClient
 * @ingroup RefuctoredCode
 *
 * @licence GNU GPL v2+
 * @author Nikola Smolenski <smolensk@eunet.rs>
 * @author Daniel Kinzler
 * @author Katie Filbert
 */
class LangLinkHandler {

	protected $siteId;
	protected $namespaces;
	protected $siteLinksLookup;
	protected $sites;

	private $sitesByNavigationId = null;

	/**
	 * Constructs a new LangLinkHandler using the given service instances.
	 *
	 * @param string         $siteId The global site ID for the local wiki
	 * @param array          $namespaces The list of namespaces for which language links should be handled.
	 * @param SiteLinkLookup $siteLinksLookup A site link lookup service
	 * @param Sites          $sites A site definition lookup service
	 */
	public function __construct( $siteId, array $namespaces,
			SiteLinkLookup $siteLinksLookup, Sites $sites ) {
		$this->siteId = $siteId;
		$this->namespaces = $namespaces;
		$this->siteLinksLookup = $siteLinksLookup;
		$this->sites = $sites;
	}

	/**
	 * Finds the corresponding item on the repository and returns the item's site links.
	 *
	 * @since    0.1
	 *
	 * @param Title $title
	 *
	 * @return SiteLink[]
	 */
	public function getEntityLinks( Title $title ) {
		wfDebugLog( __CLASS__, __FUNCTION__ . ": Looking for sitelinks defined by the corresponding item on the wikibase repo." );

		$itemId = $this->siteLinksLookup->getItemIdForLink(
			$this->siteId,
			$title->getFullText()
		);

		$links =  array();

		if ( $itemId !== false ) {
			wfDebugLog( __CLASS__, __FUNCTION__ . ": Item ID for " . $title->getFullText() . " is " . $itemId );

			$links = $this->siteLinksLookup->getSiteLinksForItem(
				new EntityId( Item::ENTITY_TYPE, $itemId ) );
		} else {
			wfDebugLog( __CLASS__, __FUNCTION__ . ": No corresponding item found for " . $title->getFullText() );
		}

		wfDebugLog( __CLASS__, __FUNCTION__ . ": Found " . count( $links ) . " links." );
		return $links;
	}

	/**
	 * Checks if a page have interwiki links from Wikidata repo?
	 * Disabled for a page when either:
	 * - Wikidata not enabled for namespace
	 * - nel parser function = * (suppress all repo links)
	 *
	 * @since 0.1
	 *
	 * @param \Title        $title
	 * @param \ParserOutput $out
	 *
	 * @return boolean
	 */
	public function useRepoLinks( Title $title, ParserOutput $out ) {
		// use repoLinks in only the namespaces specified in settings
		if ( in_array( $title->getNamespace(), $this->namespaces ) ) {
			$nel = self::getNoExternalLangLinks( $out );

			if( in_array( '*', $nel ) ) {
				return false;
			}
			return true;
		}
		return false;
	}

	/**
	 * Returns a filtered version of $repoLinks, containing only links that should be considered
	 * for combining with the local inter-language links. This takes into account the
	 * {{#noexternallanglinks}} parser function, and also removed any link to
	 * this wiki itself.
	 *
	 * This function does not remove links to wikis for which there is already an
	 * inter-language link defined in the local wikitext. This is done later
	 * by getEffectiveRepoLinks().
	 *
	 * @since 0.1
	 *
	 * @param ParserOutput $out
	 * @param array $repoLinks An array that uses global site IDs as keys.
	 *
	 * @return array A filtered copy of $repoLinks, which any inappropriate
	 *         entries removed.
	 */
	public function suppressRepoLinks( ParserOutput $out, $repoLinks ) {
		$nel = self::getNoExternalLangLinks( $out );

		foreach ( $nel as $code ) {
			$site = $this->getSiteByNavigationId( $code );

			if ( $site === false ) {
				continue;
			}

			$wiki = $site->getGlobalId();
			unset( $repoLinks[$wiki] );
		}

		unset( $repoLinks[$this->siteId] ); // remove self-link

		return $repoLinks;
	}

	/**
	 * Get the noexternallanglinks page property from the ParserOutput,
	 * which is set by the {{#noexternallanglinks}} parser function.
	 *
	 * @param \ParserOutput $out
	 *
	 * @return Array A list of language codes, identifying which repository links to ignore.
	 *         Empty if {{#noexternallanglinks}} was not used on the page.
	 */
	public static function getNoExternalLangLinks( ParserOutput $out ) {
		$nel = $out->getProperty( 'noexternallanglinks' );

		if( empty( $nel ) ) {
			$nel = array();
		} else {
			$nel = unserialize( $nel );
		}

		return $nel;
	}

	/**
	 * Set the noexternallanglinks page property in the ParserOutput,
	 * which is set by the {{#noexternallanglinks}} parser function.
	 *
	 * @since 0.4
	 *
	 * @param \ParserOutput $out
	 * @param array $noexternallanglinks a list of languages to suppress
	 */
	public static function setNoExternalLangLinks( ParserOutput $out, array $noexternallanglinks ) {
		$out->setProperty( 'noexternallanglinks', serialize( $noexternallanglinks )  );
	}

	/**
	 * Returns a Site object for the given navigational ID (alias inter-language prefix).
	 *
	 * @since 0.4
	 *
	 * @todo: move this functionality into Sites/SiteList/SiteArray!
	 *
	 * @param string $id The navigation ID to find a site for.
	 *
	 * @return bool|Site The site with the given navigational ID, or false if not found.
	 */
	protected function getSiteByNavigationId( $id ) {
		//FIXME: this needs to be moved into core, into SiteList resp. SiteArray!
		if ( $this->sitesByNavigationId === null ) {
			$this->sitesByNavigationId = array();

			/* @var Site $site */
			foreach ( $this->sites->getSites() as $site ) {
				$ids = $site->getNavigationIds();

				foreach ( $ids as $navId ) {
					$this->sitesByNavigationId[$navId] = $site;
				}
			}
		}

		return isset( $this->sitesByNavigationId[$id] ) ? $this->sitesByNavigationId[$id] : false;
	}

	/**
	 * Converts a list of interwiki links into an associative array that maps
	 * global site IDs to the respective target pages on the designated wikis.
	 *
	 * @since 0.4
	 *
	 * @param array $flatLinks
	 *
	 * @return array An associative array, using site IDs for keys
	 *           and the target pages on the respective wiki as the associated value.
	 */
	protected function localLinksToArray( array $flatLinks ) {
		$links = array();

		foreach ( $flatLinks as $s ) {
			$parts = explode( ':', $s, 2 );

			if ( count($parts) === 2 ) {
				$lang = $parts[0];
				$page = $parts[1];

				$site = $this->getSiteByNavigationId( $lang );

				if ( $site ) {
					$wiki = $site->getGlobalId();
					$links[$wiki] = $page;
				} else {
					wfWarn( "Failed to map interlanguage prefix $lang to a global site ID." );
				}
			}
		}

		return $links;
	}

	/**
	 * Converts a list of SiteLink objects into an associative array that maps
	 * global site IDs to the respective target pages on the designated wikis.
	 *
	 * @since 0.4
	 *
	 * @param SiteLink[] $repoLinks
	 *
	 * @return array An associative array, using site IDs for keys
	 *         and the target pages on the respective wiki as the associated value.
	 */
	protected function repoLinksToArray( array $repoLinks ) {
		$links = array();

		/* @var SiteLink $link */
		foreach ( $repoLinks as $link ) {
			$wiki = $link->getSite()->getGlobalId();
			$page = $link->getPage();

			$links[$wiki] = $page;
		}

		return $links;
	}

	/**
	 * Look up sitelinks for the given title on the repository and filter them
	 * taking into account any applicable configuration and any use of the
	 * {{#noexternallanglinks}} function on the page.
	 *
	 * The result is an associative array of links that should be added to the
	 * current page, excluding any target languages for which there already is a
	 * link on the page.
	 *
	 * @since 0.4
	 *
	 * @param Title        $title The page's title
	 * @param ParserOutput $out   Parsed representation of the page
	 *
	 * @return \Wikibase\SiteLink[] An associative array, using site IDs for keys
	 *         and the target pages in the respective languages as the associated value.
	 */
	public function getEffectiveRepoLinks( Title $title, ParserOutput $out ) {
		if ( !$this->useRepoLinks( $title, $out ) ) {
			return array();
		}

		$onPageLinks = $out->getLanguageLinks();
		$onPageLinks = $this->localLinksToArray( $onPageLinks );

		$repoLinks = $this->getEntityLinks( $title );
		$repoLinks = $this->repoLinksToArray( $repoLinks );
		$repoLinks = $this->suppressRepoLinks( $out, $repoLinks );

		$repoLinks = array_diff_key( $repoLinks, $onPageLinks ); // remove local links

		return $repoLinks;
	}

	/**
	 * Look up sitelinks for the given title on the repository and add them
	 * to the ParserOutput object, taking into account any applicable
	 * configuration and any use of the {{#noexternallanglinks}} function on the page.
	 *
	 * The language links are not sorted, call sortLanguageLinks() to do that.
	 *
	 * @since 0.4
	 *
	 * @param \Title        $title The page's title
	 * @param \ParserOutput $out   Parsed representation of the page
	 */
	public function addLinksFromRepository( Title $title, ParserOutput $out ) {
		$repoLinks = $this->getEffectiveRepoLinks( $title, $out );

		foreach ( $repoLinks as $wiki => $page ) {
			$site = $this->sites->getSite( $wiki );

			$nav = $site->getNavigationIds();
			$nav = array_values( $nav );

			if ( isset( $nav[0] ) ) {
				$lang = $nav[0];

				$link = "$lang:$page";
				$out->addLanguageLink( $link );
			} else {
				wfWarn( "No interlanguage prefix found for $wiki." );
			}
		}
	}
}
