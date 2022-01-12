<?php

namespace Wikibase\Client\Hooks;

use ParserOutput;
use Site;
use SiteLookup;
use Title;
use Wikibase\Client\NamespaceChecker;
use Wikibase\DataModel\SiteLink;

/**
 * @todo split this up and find a better home for stuff that adds
 * parser output properties and extension data.
 *
 * @license GPL-2.0-or-later
 * @author Nikola Smolenski <smolensk@eunet.rs>
 * @author Daniel Kinzler
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class LangLinkHandler {

	/**
	 * @var LanguageLinkBadgeDisplay
	 */
	private $badgeDisplay;

	/**
	 * @var NamespaceChecker
	 */
	private $namespaceChecker;

	/**
	 * @var SiteLinksForDisplayLookup
	 */
	private $siteLinksForDisplayLookup;

	/**
	 * @var SiteLookup
	 */
	private $siteLookup;

	/**
	 * @var string
	 */
	private $siteId;

	/**
	 * @var string[]
	 */
	private $siteGroups;

	/**
	 * @param LanguageLinkBadgeDisplay $badgeDisplay
	 * @param NamespaceChecker $namespaceChecker determines which namespaces wikibase is enabled on
	 * @param SiteLinksForDisplayLookup $siteLinkForDisplayLookup
	 * @param SiteLookup $siteLookup
	 * @param string $siteId The global site ID for the local wiki
	 * @param string[] $siteGroups The ID of the site groups to use for showing language links.
	 */
	public function __construct(
		LanguageLinkBadgeDisplay $badgeDisplay,
		NamespaceChecker $namespaceChecker,
		SiteLinksForDisplayLookup $siteLinkForDisplayLookup,
		SiteLookup $siteLookup,
		string $siteId,
		array $siteGroups
	) {
		$this->badgeDisplay = $badgeDisplay;
		$this->namespaceChecker = $namespaceChecker;
		$this->siteLinksForDisplayLookup = $siteLinkForDisplayLookup;
		$this->siteLookup = $siteLookup;
		$this->siteId = $siteId;
		$this->siteGroups = $siteGroups;
	}

	/**
	 * @param SiteLink[] $links
	 *
	 * @return SiteLink[] The SiteLinks in $links, indexed by interwiki prefix.
	 */
	private function indexLinksByInterwiki( array $links ) {
		$indexed = [];

		foreach ( $links as $link ) {
			$siteId = $link->getSiteId();
			$site = $this->siteLookup->getSite( $siteId );

			if ( !$site ) {
				continue;
			}

			$navIds = $site->getNavigationIds();
			$key = reset( $navIds );

			if ( $key !== false ) {
				$indexed[$key] = $link;
			}
		}

		return $indexed;
	}

	/**
	 * Checks if a page have interwiki links from Wikidata repo?
	 * Disabled for a page when either:
	 * - Wikidata not enabled for namespace
	 * - nel parser function = * (suppress all repo links)
	 *
	 * @param Title $title
	 * @param ParserOutput $parserOutput
	 *
	 * @return bool
	 */
	public function useRepoLinks( Title $title, ParserOutput $parserOutput ) {
		// use repoLinks in only the namespaces specified in settings
		if ( $this->namespaceChecker->isWikibaseEnabled( $title->getNamespace() ) === true ) {
			$nel = $this->getNoExternalLangLinks( $parserOutput );

			if ( in_array( '*', $nel ) ) {
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
	 * @param ParserOutput $parserOutput
	 * @param array $repoLinks An array that uses global site IDs as keys.
	 *
	 * @return SiteLink[] A filtered copy of $repoLinks, with any inappropriate
	 *         entries removed.
	 */
	public function suppressRepoLinks( ParserOutput $parserOutput, array $repoLinks ) {
		$nel = $this->getNoExternalLangLinks( $parserOutput );

		foreach ( $nel as $code ) {
			if ( $code === '*' ) {
				// all are suppressed
				return [];
			}

			$sites = $this->siteLookup->getSites();
			if ( $sites->hasNavigationId( $code ) ) {
				$site = $sites->getSiteByNavigationId( $code );
				$wiki = $site->getGlobalId();
				unset( $repoLinks[$wiki] );
			}
		}

		unset( $repoLinks[$this->siteId] ); // remove self-link

		return $repoLinks;
	}

	/**
	 * Filters the given list of links by site group:
	 * Any links pointing to a site that is not in $allowedGroups will be removed.
	 *
	 * @param array $repoLinks An array that uses global site IDs as keys.
	 * @param string[] $allowedGroups A list of allowed site groups
	 *
	 * @return array A filtered copy of $repoLinks, retaining only the links
	 *         pointing to a site in an allowed group.
	 */
	public function filterRepoLinksByGroup( array $repoLinks, array $allowedGroups ) {
		foreach ( $repoLinks as $wiki => $link ) {
			if ( !$this->siteLookup->getSite( $wiki ) ) {
				unset( $repoLinks[$wiki] );
				continue;
			}

			$site = $this->siteLookup->getSite( $wiki );

			if ( !in_array( $site->getGroup(), $allowedGroups ) ) {
				unset( $repoLinks[$wiki] );
				continue;
			}
		}

		return $repoLinks;
	}

	/**
	 * Get the noexternallanglinks page property from the ParserOutput,
	 * which is set by the {{#noexternallanglinks}} parser function.
	 *
	 * @see NoLangLinkHandler::getNoExternalLangLinks
	 *
	 * @param ParserOutput $parserOutput
	 *
	 * @return string[] A list of language codes, identifying which repository links to ignore.
	 *         Empty if {{#noexternallanglinks}} was not used on the page.
	 */
	public function getNoExternalLangLinks( ParserOutput $parserOutput ) {
		return NoLangLinkHandler::getNoExternalLangLinks( $parserOutput );
	}

	/**
	 * Converts a list of interwiki links into an associative array that maps
	 * global site IDs to the respective target pages on the designated wikis.
	 *
	 * @param string[] $flatLinks
	 *
	 * @return string[] An associative array, using site IDs for keys
	 *           and the target pages on the respective wiki as the associated value.
	 */
	private function localLinksToArray( array $flatLinks ) {
		$links = [];
		$sites = $this->siteLookup->getSites();

		foreach ( $flatLinks as $s ) {
			$parts = explode( ':', $s, 2 );
			if ( count( $parts ) !== 2 ) {
				continue;
			}

			list( $lang, $page ) = $parts;

			if ( $sites->hasNavigationId( $lang ) ) {
				$site = $sites->getSiteByNavigationId( $lang );
				$wiki = $site->getGlobalId();
				$links[$wiki] = $page;
			} else {
				wfWarn( "Failed to map interlanguage prefix $lang to a global site ID." );
			}
		}

		return $links;
	}

	/**
	 * Look up sitelinks for the given title on the repository and filter them
	 * taking into account any applicable configuration and any use of the
	 * {{#noexternallanglinks}} function on the page.
	 *
	 * The result is an associative array of links that should be added to the
	 * current page, excluding any target sites for which there already is a
	 * link on the page.
	 *
	 * @param Title $title The page's title
	 * @param ParserOutput $parserOutput   Parsed representation of the page
	 *
	 * @return SiteLink[] An associative array, using site IDs for keys
	 *         and the target pages in the respective languages as the associated value.
	 */
	public function getEffectiveRepoLinks( Title $title, ParserOutput $parserOutput ) {
		if ( !$this->useRepoLinks( $title, $parserOutput ) ) {
			return [];
		}
		$onPageLinks = $parserOutput->getLanguageLinks();
		$onPageLinks = $this->localLinksToArray( $onPageLinks );

		$repoLinks = $this->siteLinksForDisplayLookup->getSiteLinksForPageTitle( $title );

		$repoLinks = $this->filterRepoLinksByGroup( $repoLinks, $this->siteGroups );
		$repoLinks = $this->suppressRepoLinks( $parserOutput, $repoLinks );

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
	 * @param Title $title The page's title
	 * @param ParserOutput $parserOutput Parsed representation of the page
	 */
	public function addLinksFromRepository( Title $title, ParserOutput $parserOutput ) {
		$repoLinks = $this->getEffectiveRepoLinks( $title, $parserOutput );

		$this->addLinksToOutput( $repoLinks, $parserOutput );

		$repoLinksByInterwiki = $this->indexLinksByInterwiki( $repoLinks );
		$this->badgeDisplay->attachBadgesToOutput( $repoLinksByInterwiki, $parserOutput );
	}

	/**
	 * Adds the given SiteLinks to the given ParserOutput.
	 *
	 * @param SiteLink[] $links
	 * @param ParserOutput $parserOutput
	 */
	private function addLinksToOutput( array $links, ParserOutput $parserOutput ) {
		foreach ( $links as $siteId => $siteLink ) {
			$page = $siteLink->getPageName();
			$targetSite = $this->siteLookup->getSite( $siteId );

			if ( !$targetSite ) {
				wfLogWarning( "Unknown wiki '$siteId' used as sitelink target" );
				continue;
			}

			$interwikiCode = $this->getInterwikiCodeFromSite( $targetSite );

			if ( $interwikiCode ) {
				$link = "$interwikiCode:$page";
				$parserOutput->addLanguageLink( $link );
			} else {
				wfWarn( "No interlanguage prefix found for $siteId." );
			}
		}
	}

	/**
	 * Extracts the local interwiki code, which in case of the
	 * wikimedia site groups, is always the global id's prefix.
	 *
	 * @fixme put somewhere more sane and use site identifiers data,
	 * so that this works in non-wikimedia cases where the assumption
	 * is not true.
	 *
	 * @param Site $site
	 *
	 * @return string
	 */
	public function getInterwikiCodeFromSite( Site $site ) {
		// FIXME: We should use $site->getInterwikiIds, but the interwiki ids in
		// the sites table are wrong currently, see T137537.
		$id = $site->getGlobalId();
		$id = preg_replace( '/(wiki\w*|wiktionary)$/', '', $id );
		$id = strtr( $id, [ '_' => '-' ] );
		if ( !$id ) {
			$id = $site->getLanguageCode();
		}
		return $id;
	}

}
