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
			return array();
		}

		$site = $this->sites->getSiteByNavigationId( $navId );

		$siteLinks = $this->clientSiteLinkLookup->getSiteLinks( $title );
		if ( !isset( $siteLinks[$site->getGlobalId()] ) ) {
			return array();
		}

		$siteLink = $siteLinks[$site->getGlobalId()];
		$linkBadges = array();
		foreach ( $siteLink->getBadges() as $badgeItemId ) {
			$badge = Sanitizier::escapeClass( $badgeItemId->getSerialization() );
			if ( !empty( $languageLink['class'] ) ) {
				$languageLink['class'] .= ' ';
			}
			$languageLink['class'] .= " badge-$badge";
			$linkBadges[] = $badge;
		}

		foreach ( $this->displayBadges as $badge ) {
			if ( in_array( $badge, $linkBadges ) ) {
				$title = $this->getTitle( $badge );
				if ( $title !== null ) {
					// if a badge comes later in the config,
					// this will override the title as documented.
					$languageLink['itemtitle'] = $title;
				}
			}
		}
	}

	/**
	 * Returns the title for the given badge.
	 *
	 * @since 0.5
	 *
	 * @param string $badge
	 *
	 * @return string|null
	 */
	protected function getTitle( $badge ) {
		$entity = $this->entityLookup->getEntity( new ItemId( $badge ) );
		if ( !$entity ) {
			return null;
		}

		$description = $entity->getLabel( $this->langCode );
		if ( !$description ) {
			return null;
		}
		return $description;
	}

}
