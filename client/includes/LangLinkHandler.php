<?php

namespace Wikibase;

use ParserOutput;
use Site;
use SiteStore;
use Title;
use Wikibase\Client\Hooks\LanguageLinkBadgeDisplay;
use Wikibase\Client\Hooks\OtherProjectsSidebarGenerator;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;
use Wikibase\Lib\Store\EntityLookup;
use Wikibase\Lib\Store\SiteLinkLookup;

/**
 * @todo split this up and find a better home for stuff that adds
 * parser output properties and extension data.
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Nikola Smolenski <smolensk@eunet.rs>
 * @author Daniel Kinzler
 * @author Katie Filbert
 */
class LangLinkHandler {

	/**
	 * @var OtherProjectsSidebarGenerator
	 */
	private $otherProjectsSidebarGenerator;

	/**
	 * @var LanguageLinkBadgeDisplay
	 */
	private $badgeDisplay;

	/**
	 * @var string
	 */
	private $siteId;

	/**
	 * @var NamespaceChecker
	 */
	private $namespaceChecker;

	/**
	 * @var SiteLinkLookup
	 */
	private $siteLinkLookup;

	/**
	 * @var EntityLookup
	 */
	private $entityLookup;

	/**
	 * @var SiteStore
	 */
	private $siteStore;

	/**
	 * @var string
	 */
	private $siteGroup;

	/**
	 * @var ItemId[]
	 */
	private $itemIds;

	/**
	 * @param OtherProjectsSidebarGenerator $otherProjectsSidebarGenerator
	 * @param LanguageLinkBadgeDisplay $badgeDisplay
	 * @param string $siteId The global site ID for the local wiki
	 * @param NamespaceChecker $namespaceChecker determines which namespaces wikibase is enabled on
	 * @param SiteLinkLookup $siteLinkLookup A site link lookup service
	 * @param EntityLookup $entityLookup An entity lookup service
	 * @param SiteStore $siteStore A site definition lookup service
	 * @param string $siteGroup The ID of the site group to use for showing language links.
	 */
	public function __construct(
		OtherProjectsSidebarGenerator $otherProjectsSidebarGenerator,
		LanguageLinkBadgeDisplay $badgeDisplay,
		$siteId,
		NamespaceChecker $namespaceChecker,
		SiteLinkLookup $siteLinkLookup,
		EntityLookup $entityLookup,
		SiteStore $siteStore,
		$siteGroup
	) {
		$this->otherProjectsSidebarGenerator = $otherProjectsSidebarGenerator;
		$this->badgeDisplay = $badgeDisplay;
		$this->siteId = $siteId;
		$this->namespaceChecker = $namespaceChecker;
		$this->siteLinkLookup = $siteLinkLookup;
		$this->entityLookup = $entityLookup;
		$this->siteStore = $siteStore;
		$this->siteGroup = $siteGroup;
	}

	/**
	 * Finds the corresponding item on the repository and returns the item's site links.
	 *
	 * @since 0.1
	 *
	 * @param Title $title
	 *
	 * @return SiteLink[] A map of SiteLinks, indexed by global site id.
	 */
	public function getEntityLinks( Title $title ) {
		wfProfileIn( __METHOD__ );
		wfDebugLog( __CLASS__, __FUNCTION__ . ": Looking for sitelinks defined by the "
			. "corresponding item on the wikibase repo." );

		$links = array();

		$itemId = $this->getItemIdForTitle( $title );

		if ( $itemId !== null ) {
			wfDebugLog( __CLASS__, __FUNCTION__ . ': Item ID for ' . $title->getFullText()
				. ' is ' . $itemId->getSerialization() );

			//NOTE: SiteLinks we could get from $this->siteLinkLookup do not contain badges,
			//      so we have to fetch the links from the Item.

			/* @var Item $item */
			$item = $this->entityLookup->getEntity( $itemId );

			if ( $item ) {
				$links = iterator_to_array( $item->getSiteLinkList() );
				$links = $this->indexLinksBySiteId( $links );
			} else {
				wfWarn( __METHOD__ . ": Could not load item " . $itemId->getSerialization()
					. " for " . $title->getFullText() );
			}
		} else {
			wfDebugLog( __CLASS__, __FUNCTION__ . ": No corresponding item found for "
				. $title->getFullText() );
		}

		wfDebugLog( __CLASS__, __FUNCTION__ . ": Found " . count( $links ) . " links." );
		wfProfileOut( __METHOD__ );

		return $links;
	}

	/**
	 * @param SiteLink[] $links
	 *
	 * @return SiteLink[] The SiteLinks in $links, indexed by site ID
	 */
	private function indexLinksBySiteId( $links ) {
		$indexed = array();

		foreach ( $links as $link ) {
			$key = $link->getSiteId();
			$indexed[$key] = $link;
		}

		return $indexed;
	}

	/**
	 * @param SiteLink[] $links
	 *
	 * @return SiteLink[] The SiteLinks in $links, indexed by interwiki prefix.
	 */
	private function indexLinksByInterwiki( $links ) {
		$indexed = array();

		foreach ( $links as $link ) {
			$siteId = $link->getSiteId();
			$site = $this->siteStore->getSite( $siteId );

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
	 * @since 0.1
	 *
	 * @param Title $title
	 * @param ParserOutput $out
	 *
	 * @return bool
	 */
	public function useRepoLinks( Title $title, ParserOutput $out ) {
		wfProfileIn( __METHOD__ );

		// use repoLinks in only the namespaces specified in settings
		if ( $this->namespaceChecker->isWikibaseEnabled( $title->getNamespace() ) === true ) {
			$nel = $this->getNoExternalLangLinks( $out );

			if( in_array( '*', $nel ) ) {
				wfProfileOut( __METHOD__ );
				return false;
			}
			wfProfileOut( __METHOD__ );
			return true;
		}

		wfProfileOut( __METHOD__ );
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
	 * @return SiteLink[] A filtered copy of $repoLinks, with any inappropriate
	 *         entries removed.
	 */
	public function suppressRepoLinks( ParserOutput $out, array $repoLinks ) {
		wfProfileIn( __METHOD__ );

		$nel = $this->getNoExternalLangLinks( $out );

		foreach ( $nel as $code ) {
			if ( $code === '*' ) {
				// all are suppressed
				return array();
			}

			$siteList = $this->siteStore->getSites();

			if ( $siteList->hasNavigationId( $code ) ) {
				$site = $siteList->getSiteByNavigationId( $code );
				$wiki = $site->getGlobalId();
				unset( $repoLinks[$wiki] );
			}
		}

		unset( $repoLinks[$this->siteId] ); // remove self-link

		wfProfileOut( __METHOD__ );
		return $repoLinks;
	}

	/**
	 * Filters the given list of links by site group:
	 * Any links pointing to a site that is not in $allowedGroups will be removed.
	 *
	 * @since  0.4
	 *
	 * @param array $repoLinks An array that uses global site IDs as keys.
	 * @param string[] $allowedGroups A list of allowed site groups
	 *
	 * @return array A filtered copy of $repoLinks, retaining only the links
	 *         pointing to a site in an allowed group.
	 */
	public function filterRepoLinksByGroup( array $repoLinks, array $allowedGroups ) {
		wfProfileIn( __METHOD__ );

		foreach ( $repoLinks as $wiki => $link ) {
			$site = $this->siteStore->getSite( $wiki );

			if ( $site === null ) {
				wfDebugLog( __CLASS__, __FUNCTION__ . ': skipping link to unknown site ' . $wiki );

				unset( $repoLinks[$wiki] );
				continue;
			}

			if ( !in_array( $site->getGroup(), $allowedGroups ) ) {
				wfDebugLog( __CLASS__, __FUNCTION__ . ': skipping link to other group: ' . $wiki
					. ' belongs to ' . $site->getGroup() );

				unset( $repoLinks[$wiki] );
				continue;
			}
		}

		wfProfileOut( __METHOD__ );
		return $repoLinks;
	}

	/**
	 * Get the noexternallanglinks page property from the ParserOutput,
	 * which is set by the {{#noexternallanglinks}} parser function.
	 *
	 * @see NoLangLinkHandler::getNoExternalLangLinks
	 *
	 * @param ParserOutput $out
	 *
	 * @return string[] A list of language codes, identifying which repository links to ignore.
	 *         Empty if {{#noexternallanglinks}} was not used on the page.
	 */
	public function getNoExternalLangLinks( ParserOutput $out ) {
		return NoLangLinkHandler::getNoExternalLangLinks( $out );
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
		wfProfileIn( __METHOD__ );

		$links = array();

		foreach ( $flatLinks as $s ) {
			$parts = explode( ':', $s, 2 );

			if ( count($parts) === 2 ) {
				$lang = $parts[0];
				$page = $parts[1];

				$siteList = $this->siteStore->getSites();

				if ( $siteList->hasNavigationId( $lang ) ) {
					$site = $siteList->getSiteByNavigationId( $lang );
					$wiki = $site->getGlobalId();
					$links[$wiki] = $page;
				} else {
					wfWarn( "Failed to map interlanguage prefix $lang to a global site ID." );
				}
			}
		}

		wfProfileOut( __METHOD__ );
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
	 * @since 0.4
	 *
	 * @param Title $title The page's title
	 * @param ParserOutput $out   Parsed representation of the page
	 *
	 * @return SiteLink[] An associative array, using site IDs for keys
	 *         and the target pages in the respective languages as the associated value.
	 */
	public function getEffectiveRepoLinks( Title $title, ParserOutput $out ) {
		wfProfileIn( __METHOD__ );

		if ( !$this->useRepoLinks( $title, $out ) ) {
			wfProfileOut( __METHOD__ );
			return array();
		}

		$allowedGroups = array( $this->siteGroup );

		$onPageLinks = $out->getLanguageLinks();
		$onPageLinks = $this->localLinksToArray( $onPageLinks );

		$repoLinks = $this->getEntityLinks( $title );

		$repoLinks = $this->filterRepoLinksByGroup( $repoLinks, $allowedGroups );
		$repoLinks = $this->suppressRepoLinks( $out, $repoLinks );

		$repoLinks = array_diff_key( $repoLinks, $onPageLinks ); // remove local links

		wfProfileOut( __METHOD__ );
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
	 * @param Title $title The page's title
	 * @param ParserOutput $out Parsed representation of the page
	 */
	public function addLinksFromRepository( Title $title, ParserOutput $out ) {
		wfProfileIn( __METHOD__ );

		$repoLinks = $this->getEffectiveRepoLinks( $title, $out );

		$this->addLinksToOutput( $repoLinks, $out );

		$repoLinksByInterwiki = $this->indexLinksByInterwiki( $repoLinks );
		$this->badgeDisplay->attachBadgesToOutput( $repoLinksByInterwiki, $out );

		wfProfileOut( __METHOD__ );
	}

	/**
	 * Adds the given SiteLinks to the given ParserOutput.
	 *
	 * @param SiteLink[] $links
	 * @param ParserOutput $out
	 */
	private function addLinksToOutput( array $links, ParserOutput $out ) {
		foreach ( $links as $siteId => $siteLink ) {
			$page = $siteLink->getPageName();
			$targetSite = $this->siteStore->getSite( $siteId );

			if ( !$targetSite ) {
				wfLogWarning( "Unknown wiki '$siteId' used as sitelink target" );
				continue;
			}

			$interwikiCode = $this->getInterwikiCodeFromSite( $targetSite );

			if ( $interwikiCode ) {
				$link = "$interwikiCode:$page";
				$out->addLanguageLink( $link );
			} else {
				wfWarn( "No interlanguage prefix found for $siteId." );
			}
		}
	}

	/**
	 * Extracts the local interwiki code, which in case of the
	 * wikimedia site groups, is always set to the language code.
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
		return $site->getLanguageCode();
	}

	/**
	 * Add wikibase_item parser output property
	 *
	 * @since 0.4
	 *
	 * @param Title $title
	 * @param ParserOutput $out
	 */
	public function updateItemIdProperty( Title $title, ParserOutput $out ) {
		wfProfileIn( __METHOD__ );

		$itemId = $this->getItemIdForTitle( $title );

		if ( $itemId ) {
			$out->setProperty( 'wikibase_item', $itemId->getSerialization() );
		} else {
			$out->unsetProperty( 'wikibase_item' );
		}

		wfProfileOut( __METHOD__ );
	}

	/**
	 * @param Title $title
	 * @param ParserOutput $out
	 */
	public function updateOtherProjectsLinksData( Title $title, ParserOutput $out ) {
		$itemId = $this->getItemIdForTitle( $title );

		if ( $itemId ) {
			$otherProjects = $this->otherProjectsSidebarGenerator->buildProjectLinkSidebar( $title );
			$out->setExtensionData( 'wikibase-otherprojects-sidebar', $otherProjects );
		} else {
			$out->setExtensionData( 'wikibase-otherprojects-sidebar', array() );
		}
	}

	/**
	 * @param Title $title
	 *
	 * @return ItemId|null
	 */
	private function getItemIdForTitle( Title $title ) {
		$key = $title->getFullText();

		if ( !isset( $this->itemIds[$key] ) ) {
			$siteLink = new SiteLink( $this->siteId, $title->getFullText() );
			$this->itemIds[$key] = $this->siteLinkLookup->getEntityIdForSiteLink( $siteLink );
		}

		return $this->itemIds[$key];
	}

}
