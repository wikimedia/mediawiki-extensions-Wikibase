<?php

namespace Wikibase\Repo\View;

use InvalidArgumentException;
use Message;
use Sanitizer;
use Site;
use SiteList;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\Term\FingerprintProvider;
use Wikibase\Lib\Store\EntityLookup;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Template\TemplateFactory;

/**
 * Creates views for lists of site links.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Adrian Lang <adrian.lang@wikimedia.de>
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class SiteLinksView {

	/**
	 * @var TemplateFactory
	 */
	private $templateFactory;

	/**
	 * @var SiteList
	 */
	private $sites;

	/**
	 * @var SectionEditLinkGenerator
	 */
	private $sectionEditLinkGenerator;

	/**
	 * @var EntityLookup
	 */
	private $entityLookup;

	/**
	 * @var LanguageNameLookup
	 */
	private $languageNameLookup;

	/**
	 * @var string[]
	 */
	private $badgeItems;

	/**
	 * @var string[]
	 */
	private $specialSiteLinkGroups;

	/**
	 * @var string
	 */
	private $languageCode;

	/**
	 * @param TemplateFactory $templateFactory
	 * @param SiteList $sites
	 * @param SectionEditLinkGenerator $sectionEditLinkGenerator
	 * @param EntityLookup $entityLookup
	 * @param LanguageNameLookup $languageNameLookup
	 * @param string[] $badgeItems
	 * @param string[] $specialSiteLinkGroups
	 * @param string $languageCode
	 */
	public function __construct(
		TemplateFactory $templateFactory,
		SiteList $sites,
		SectionEditLinkGenerator $sectionEditLinkGenerator,
		EntityLookup $entityLookup,
		LanguageNameLookup $languageNameLookup,
		array $badgeItems,
		array $specialSiteLinkGroups,
		$languageCode
	) {
		$this->sites = $sites;
		$this->sectionEditLinkGenerator = $sectionEditLinkGenerator;
		$this->entityLookup = $entityLookup;
		$this->badgeItems = $badgeItems;
		$this->specialSiteLinkGroups = $specialSiteLinkGroups;
		$this->languageCode = $languageCode;
		$this->templateFactory = $templateFactory;
		$this->languageNameLookup = $languageNameLookup;
	}

	/**
	 * Builds and returns the HTML representing a WikibaseEntity's site-links.
	 *
	 * @since 0.5
	 *
	 * @param SiteLink[] $siteLinks the site links to render
	 * @param ItemId|null $itemId The id of the item or might be null, if a new item.
	 * @param string[] $groups An array of site group IDs
	 * @param bool $editable whether editing is allowed (enabled edit links)
	 *
	 * @return string
	 * @throws InvalidArgumentException
	 */
	public function getHtml( array $siteLinks, $itemId, array $groups, $editable ) {
		if ( $itemId !== null && !( $itemId instanceof ItemId ) ) {
			throw new InvalidArgumentException( '$itemId must be an ItemId or null.' );
		}

		$html = '';

		if ( count( $groups ) === 0 ) {
			return $html;
		}

		foreach ( $groups as $group ) {
			$html .= $this->getHtmlForSiteLinkGroup( $siteLinks, $itemId, $group, $editable );
		}

		return $this->templateFactory->render( 'wikibase-sitelinkgrouplistview',
			$this->templateFactory->render( 'wikibase-listview', $html )
		);
	}

	/**
	 * Builds and returns the HTML representing a group of a WikibaseEntity's site-links.
	 *
	 * @param SiteLink[] $siteLinks the site links to render
	 * @param ItemId|null $itemId The id of the item
	 * @param string $group a site group ID
	 * @param bool $editable
	 *
	 * @return string
	 */
	private function getHtmlForSiteLinkGroup( array $siteLinks, $itemId, $group, $editable ) {
		return $this->templateFactory->render( 'wikibase-sitelinkgroupview',
			// TODO: support entity-id as prefix for element IDs.
			htmlspecialchars( 'sitelinks-' . $group, ENT_QUOTES ),
			wfMessage( 'wikibase-sitelinks-' . $group )->parse(),
			'', // counter
			$this->templateFactory->render( 'wikibase-sitelinklistview',
				$this->getHtmlForSiteLinks(
					$this->getSiteLinksForTable( $this->getSitesForGroup( $group ), $siteLinks ),
					$group === 'special'
				)
			),
			htmlspecialchars( $group ),
			$this->getHtmlForEditSection( $itemId, '', 'edit', $editable )
		);
	}

	/**
	 * Get all sites for a given site group, with special handling for the
	 * "special" site group.
	 *
	 * @param string $group
	 *
	 * @return SiteList
	 */
	private function getSitesForGroup( $group ) {
		$siteList = new SiteList();

		if ( $group === 'special' ) {
			$groups = $this->specialSiteLinkGroups;
		} else {
			$groups = array( $group );
		}

		foreach ( $groups as $group ) {
			$sites = $this->sites->getGroup( $group );
			foreach ( $sites as $site ) {
				$siteList->setSite( $site );
			}
		}

		return $siteList;
	}

	/**
	 * @param SiteList $sites
	 * @param SiteLink[] $itemSiteLinks
	 *
	 * @return array[]
	 */
	private function getSiteLinksForTable( SiteList $sites, array $itemSiteLinks ) {
		$siteLinksForTable = array(); // site links of the currently handled site group

		foreach ( $itemSiteLinks as $siteLink ) {
			if ( !$sites->hasSite( $siteLink->getSiteId() ) ) {
				// FIXME: Maybe show it instead
				continue;
			}

			$site = $sites->getSite( $siteLink->getSiteId() );

			$siteLinksForTable[] = array(
				'siteLink' => $siteLink,
				'site' => $site
			);
		}

		// Sort the sitelinks according to their global id
		$safetyCopy = $siteLinksForTable; // keep a shallow copy
		$sortOk = usort(
			$siteLinksForTable,
			function( array $a, array $b ) {
				return strcmp( $a['siteLink']->getSiteId(), $b['siteLink']->getSiteId() );
			}
		);

		if ( !$sortOk ) {
			$siteLinksForTable = $safetyCopy;
		}

		return $siteLinksForTable;
	}

	/**
	 * @param array[] $siteLinksForTable
	 * @param bool $isSpecialGroup
	 *
	 * @return string
	 */
	private function getHtmlForSiteLinks( $siteLinksForTable, $isSpecialGroup ) {
		$html = '';

		foreach ( $siteLinksForTable as $siteLinkForTable ) {
			$html .= $this->getHtmlForSiteLink( $siteLinkForTable, $isSpecialGroup );
		}

		return $html;
	}

	/**
	 * @param array $siteLinkForTable
	 * @param bool $isSpecialGroup
	 *
	 * @return string
	 */
	private function getHtmlForSiteLink( $siteLinkForTable, $isSpecialGroup ) {
		/** @var Site $site */
		$site = $siteLinkForTable['site'];

		/** @var SiteLink $siteLink */
		$siteLink = $siteLinkForTable['siteLink'];

		if ( $site->getDomain() === '' ) {
			return $this->getHtmlForUnknownSiteLink( $siteLink );
		}

		$languageCode = $site->getLanguageCode();
		$siteId = $siteLink->getSiteId();

		// FIXME: this is a quickfix to allow a custom site-name for the site groups which are
		// special according to the specialSiteLinkGroups setting
		if ( $isSpecialGroup ) {
			// FIXME: not escaped?
			$siteNameMsg = wfMessage( 'wikibase-sitelinks-sitename-' . $siteId );
			$siteName = $siteNameMsg->exists() ? $siteNameMsg->parse() : $siteId;
		} else {
			// TODO: get an actual site name rather then just the language
			$siteName = htmlspecialchars( $this->languageNameLookup->getName( $languageCode ) );
		}

		// TODO: for non-JS, also set the dir attribute on the link cell;
		// but do not build language objects for each site since it causes too much load
		// and will fail when having too much site links
		return $this->templateFactory->render( 'wikibase-sitelinkview',
			htmlspecialchars( $siteId ), // ID used in classes
			$languageCode,
			'auto',
			htmlspecialchars( $siteId ), // displayed site ID
			$siteName,
			$this->getHtmlForPage( $siteLink, $site )
		);
	}

	/**
	 * @param SiteLink $siteLink
	 * @param Site $site
	 *
	 * @return string
	 */
	private function getHtmlForPage( $siteLink, $site ) {
		$pageName = $siteLink->getPageName();

		return $this->templateFactory->render( 'wikibase-sitelinkview-pagename',
			htmlspecialchars( $site->getPageUrl( $pageName ) ),
			htmlspecialchars( $pageName ),
			$this->getHtmlForBadges( $siteLink ),
			$site->getLanguageCode()
		);
	}

	/**
	 * @param SiteLink $siteLink
	 *
	 * @return string
	 */
	private function getHtmlForUnknownSiteLink( $siteLink ) {
		// FIXME: No need for separate template; Use 'wikibase-sitelinkview' template.
		return $this->templateFactory->render( 'wikibase-sitelinkview-unknown',
			htmlspecialchars( $siteLink->getSiteId() ),
			htmlspecialchars(  $siteLink->getPageName() )
		);
	}

	/**
	 * @param ItemId|null $itemId
	 * @param string $subPage defaults to ''
	 * @param string $action defaults to 'edit'
	 * @param bool $enabled defaults to true
	 *
	 * @return string
	 */
	private function getHtmlForEditSection( $itemId, $subPage = '', $action = 'edit', $enabled = true ) {
		$specialPageUrlParams = array();

		if ( $itemId !== null ) {
			$specialPageUrlParams[] = $itemId->getSerialization();

			if ( $subPage !== '' ) {
				$specialPageUrlParams[] = $subPage;
			}
		}

		return $this->sectionEditLinkGenerator->getHtmlForEditSection(
			'SetSiteLink',
			$specialPageUrlParams,
			$action,
			new Message( 'wikibase-' . $action ),
			$enabled
		);
	}

	private function getHtmlForBadges( SiteLink $siteLink ) {
		$html = '';

		foreach ( $siteLink->getBadges() as $badge ) {
			$serialization = $badge->getSerialization();
			$classes = Sanitizer::escapeClass( $serialization );
			if ( !empty( $this->badgeItems[$serialization] ) ) {
				$classes .= ' ' . Sanitizer::escapeClass( $this->badgeItems[$serialization] );
			}

			$html .= $this->templateFactory->render( 'wb-badge',
				$classes,
				$this->getTitleForBadge( $badge ),
				$badge
			);
		}

		return $this->templateFactory->render( 'wikibase-badgeselector', $html );
	}

	/**
	 * Returns the title for the given badge id.
	 * @todo use TermLookup when we have one
	 *
	 * @param ItemId $badgeId
	 *
	 * @return string
	 */
	private function getTitleForBadge( ItemId $badgeId ) {
		$badge = $this->entityLookup->getEntity( $badgeId );

		if ( $badge instanceof FingerprintProvider ) {
			$labels = $badge->getFingerprint()->getLabels();

			if ( $labels->hasTermForLanguage( $this->languageCode ) ) {
				return $labels->getByLanguage( $this->languageCode )->getText();
			}
		}

		return $badgeId->getSerialization();
	}

}
