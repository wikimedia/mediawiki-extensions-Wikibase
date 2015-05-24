<?php

namespace Wikibase\Client\Hooks;

use Language;
use OutputPage;
use ParserOutput;
use Title;
use Wikibase\DataModel\SiteLink;

/**
 * Provides access to the badges of the current page's sitelinks
 * and adds some properties to the HTML output to display them.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 * @author Daniel Kinzler
 */
class LanguageLinkBadgeDisplay {

	/**
	 * @var SidebarLinkBadgeDisplay
	 */
	private $sidebarLinkBadgeDisplay;

	/**
	 * @param SidebarLinkBadgeDisplay $sidebarLinkBadgeDisplay
	 */
	public function __construct( SidebarLinkBadgeDisplay $sidebarLinkBadgeDisplay ) {
		$this->sidebarLinkBadgeDisplay = $sidebarLinkBadgeDisplay;
	}

	/**
	 * Attaches info about link badges in the given OutputPage, for later retrieval
	 * and processing by applyBadges().
	 *
	 * This is typically called in the context of parsing a wiki page.
	 *
	 * @param SiteLink[] $langLinks Site links indexed by local interwiki prefix.
	 * @param ParserOutput $parserOutput The output page to set the wikibase_badges property on.
	 */
	public function attachBadgesToOutput( array $langLinks, ParserOutput $parserOutput ) {
		$badgeInfoForAllLinks = array();

		foreach ( $langLinks as $key => $link ) {
			$badges = $link->getBadges();

			if ( !empty( $badges ) ) {
				$badgeInfoForAllLinks[$key] = $this->sidebarLinkBadgeDisplay->getBadgeInfo( $badges );
			}
		}

		$parserOutput->setExtensionData( 'wikibase_badges', $badgeInfoForAllLinks );
	}

	/**
	 * Applies the badges described in the wikibase_badges property of $output to
	 * the language link to $languageLinkTitle. The badge info for this linked is
	 * looked up in the wikibase_badges data using the key returned by
	 * $languageLinkTitle->getInterwiki().
	 *
	 * This is generally called in the context of generating skin output.
	 *
	 * @since 0.5
	 *
	 * @param array &$languageLink
	 * @param Title $languageLinkTitle
	 * @param OutputPage $output The output page to take the wikibase_badges property from.
	 */
	public function applyBadges( array &$languageLink, Title $languageLinkTitle, OutputPage $output ) {
		$badges = $output->getProperty( 'wikibase_badges' );

		if ( empty( $badges ) ) {
			return;
		}

		$navId = $languageLinkTitle->getInterwiki();
		if ( !isset( $badges[$navId] ) ) {
			return;
		}

		$this->sidebarLinkBadgeDisplay->applyBadgeToLink( $languageLink, $badges[$navId] );
	}
}
