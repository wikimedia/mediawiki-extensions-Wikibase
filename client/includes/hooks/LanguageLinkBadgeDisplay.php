<?php

namespace Wikibase\Client\Hooks;

use Title;
use Language;
use SiteList;
use Sanitizer;
use Wikibase\Lib\Store\EntityLookup;
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
	protected $badgeClassNames;

	/**
	 * @var Language
	 */
	protected $language;

	/**
	 * @param ClientSiteLinkLookup $clientSiteLinkLookup
	 * @param EntityLookup $entityLookup
	 * @param SiteList $sites
	 * @param array $badgeClassNames
	 * @param Language $language
	 */
	public function __construct( ClientSiteLinkLookup $clientSiteLinkLookup,
			EntityLookup $entityLookup, SiteList $sites, array $badgeClassNames, Language $language ) {
		$this->clientSiteLinkLookup = $clientSiteLinkLookup;
		$this->entityLookup = $entityLookup;
		$this->sites = $sites;
		$this->badgeClassNames = $badgeClassNames;
		$this->language = $language;
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

		$this->assignExtraBadges( $badges, $languageLink );
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
	 * Assigns the extra badge class names specified in the badgeClassNames setting
	 * to the language link and also adds a title according to the items' labels.
	 *
	 * @param array $badges
	 * @param array &$languageLink
	 */
	private function assignExtraBadges( array $badges, array &$languageLink ) {
		$titles = array();
		/** @var ItemId $badge */
		foreach ( $badges as $badge ) {
			$badgeSerialization = $badge->getSerialization();
			if ( isset( $this->badgeClassNames[$badgeSerialization] ) ) {
				// add class name
				$className = Sanitizer::escapeClass( $this->badgeClassNames[$badgeSerialization] );
				$languageLink['class'] .= ' ' . $className;

				// add title
				$title = $this->getTitle( $badge );
				if ( $title !== null ) {
					$titles[] = $title;
				}
			}
		}
		if ( !empty( $titles ) ) {
			$languageLink['itemtitle'] = $this->language->commaList( $titles );
		}
	}

	/**
	 * Returns the title for the given badge.
	 *
	 * @since 0.5
	 *
	 * @param ItemId $badge
	 *
	 * @return string|null
	 */
	private function getTitle( ItemId $badge ) {
		$entity = $this->entityLookup->getEntity( $badge );
		if ( !$entity ) {
			return null;
		}

		$title = $entity->getLabel( $this->language->getCode() );
		if ( !$title ) {
			return null;
		}
		return $title;
	}

}
