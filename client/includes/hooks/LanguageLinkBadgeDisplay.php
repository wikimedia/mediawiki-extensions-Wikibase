<?php

namespace Wikibase\Client\Hooks;

use Title;
use SiteList;
use Sanitizer;
use Wikibase\EntityLookup;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Client\ClientSiteLinkLookup;

/**
 * Provides access to the badges of the current page's sitelinks
 * and adds some properties to the HTML output to display them.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class LanguageLinkBadgeDisplay {

	/**
	 * @var ClientSiteLinkLookup
	 */
	protected $clientSiteLinkLookup;

	/**
	 * @var EntityLookup
	 */
	protected $entityLookup;

	/**
	 * @var SiteList
	 */
	protected $sites;

	/**
	 * @var array
	 */
	protected $displayBadges;

	/**
	 * @var string
	 */
	protected $langCode;

	/**
	 * @param ClientSiteLinkLookup $clientSiteLinkLookup
	 * @param EntityLookup $entityLookup
	 * @param SiteList $sites
	 * @param array $displayBadges
	 * @param string $langCode
	 */
	public function __construct( ClientSiteLinkLookup $clientSiteLinkLookup,
			EntityLookup $entityLookup, SiteList $sites, array $displayBadges, $langCode ) {
		$this->clientSiteLinkLookup = $clientSiteLinkLookup;
		$this->entityLookup = $entityLookup;
		$this->sites = $sites;
		$this->displayBadges = $displayBadges;
		$this->langCode = $langCode;
	}

	/**
	 * Looks up the item of the given title and gets all badges for the sitelink to
	 * the language link title. These badges are added as CSS classes to the language link.
	 *
	 * @since 0.5
	 *
	 * @param Title $title
	 * @param Title $languageLinkTitle
	 * @param array &$languageLink
	 */
	public function assignBadges( Title $title, Title $languageLinkTitle, array &$languageLink ) {
		$navId = $languageLinkTitle->getInterwiki();
		if ( !$this->sites->hasNavigationId( $navId ) ) {
			return;
		}

		$site = $this->sites->getSiteByNavigationId( $navId );
		$siteLink = $this->clientSiteLinkLookup->getSiteLink( $title, $site->getGlobalId() );
		if ( $siteLink === null ) {
			return;
		}

		$badges = $siteLink->getBadges();
		if ( empty( $badges ) ) {
			return;
		}

		$classes = $this->formatClasses( $badges );
		if ( !isset( $languageLink['class'] ) ) {
			$languageLink['class'] = $classes;
		} else {
			$languageLink['class'] .= ' ' . $classes;
		}

		$this->assignDisplayBadges( $badges, $languageLink );
	}

	/**
	 * Formats the badges array into a string of classes.
	 *
	 * @param ItemId[] $badges
	 *
	 * @return string
	 */
	private function formatClasses( array $badges ) {
		$classes = '';
		/* @var ItemId $badge */
		foreach ( $badges as $badge ) {
			$class = Sanitizer::escapeClass( $badge->getSerialization() );
			$classes .= "badge-$class ";
		}
		return rtrim( $classes );
	}

	/**
	 * Assigns the display badges setting to the language link.
	 *
	 * @param ItemId[] $badges
	 * @param array &$languageLink
	 */
	private function assignDisplayBadges( array $badges, array &$languageLink ) {
		foreach ( $this->displayBadges as $badge => $className ) {
			$badge = new ItemId( $badge );
			if ( in_array( $badge, $badges ) ) {
				// add class name
				$className = Sanitizer::escapeClass( $className );
				$languageLink['class'] .= ' ' . $className;

				// add title
				$title = $this->getDescription( $badge );
				if ( $title !== null ) {
					$languageLink['itemtitle'] = $title;
				}
				break;
			}
		}
	}

	/**
	 * Returns the description for the given badge.
	 *
	 * @since 0.5
	 *
	 * @param ItemId $badge
	 *
	 * @return string|null
	 */
	private function getDescription( ItemId $badge ) {
		$entity = $this->entityLookup->getEntity( $badge );
		if ( !$entity ) {
			return null;
		}

		$description = $entity->getDescription( $this->langCode );
		if ( !$description ) {
			return null;
		}
		return $description;
	}

}
