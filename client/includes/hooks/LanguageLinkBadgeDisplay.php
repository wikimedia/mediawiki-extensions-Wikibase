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
	protected $badgeCssClasses;

	/**
	 * @var string
	 */
	protected $languageCode;

	/**
	 * @param ClientSiteLinkLookup $clientSiteLinkLookup
	 * @param EntityLookup $entityLookup
	 * @param SiteList $sites
	 * @param array $badgeCssClasses
	 * @param string $languageCode
	 */
	public function __construct( ClientSiteLinkLookup $clientSiteLinkLookup,
			EntityLookup $entityLookup, SiteList $sites, array $badgeCssClasses, $languageCode ) {
		$this->clientSiteLinkLookup = $clientSiteLinkLookup;
		$this->entityLookup = $entityLookup;
		$this->sites = $sites;
		$this->badgeCssClasses = $badgeCssClasses;
		$this->languageCode = $languageCode;
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

		$this->assignExtraBadge( $badges, $languageLink );
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
	 * Assigns an extra badge to the language link if there is one specified
	 * in the badgeCssClasses setting.
	 *
	 * @param ItemId[] $badges
	 * @param array &$languageLink
	 */
	private function assignExtraBadge( array $badges, array &$languageLink ) {
		foreach ( $this->badgeCssClasses as $badge => $className ) {
			if ( $this->containsSerialization( $badges, $badge ) ) {
				// add class name
				$className = Sanitizer::escapeClass( $className );
				$languageLink['class'] .= ' ' . $className;

				// add title
				$title = $this->getDescription( new ItemId( $badge ) );
				if ( $title !== null ) {
					$languageLink['itemtitle'] = $title;
				}
				break;
			}
		}
	}

	/**
	 * Checks whether the array of badges contains the given serialization.
	 *
	 * @param ItemId[] $badges
	 * @param string $serialization
	 *
	 * @return bool
	 */
	private function containsSerialization( array $badges, $serialization ) {
		foreach ( $badges as $badge ) {
			if ( $badge->getSerialization() === $serialization ) {
				return true;
			}
		}
		return false;
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

		$description = $entity->getDescription( $this->languageCode );
		if ( !$description ) {
			return null;
		}
		return $description;
	}

}
