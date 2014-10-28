<?php

namespace Wikibase\Repo\Specials;

use Html;
use Site;
use SiteStore;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Store\SiteLinkLookup;
use Wikibase\Repo\Content\ItemHandler;
use Wikibase\Repo\WikibaseRepo;

/**
 * Enables accessing items by providing the identifier of a site and the title
 * of the corresponding page on that site.
 *
 * @since 0.1
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
class SpecialItemByTitle extends SpecialItemResolver {

	/**
	 * @var EntityTitleLookup
	 */
	private $titleLookup;

	/**
	 * @var SiteStore
	 */
	private $sites;

	/**
	 * @var SiteLinkLookup
	 */
	private $siteLinkLookup;

	/**
	 * @var bool
	 */
	private $normalizeItemByTitlePageNames;

	/**
	 * site link groups
	 *
	 * @var string[]
	 */
	private $groups;

	/**
	 * @see SpecialItemResolver::__construct
	 *
	 * @since 0.1
	 */
	public function __construct() {
		// args $name, $restriction, $listed
		parent::__construct( 'ItemByTitle', '', true );

		$settings = WikibaseRepo::getDefaultInstance()->getSettings();

		$this->initSettings(
			$settings->getSetting( 'normalizeItemByTitlePageNames' ),
			$settings->getSetting( 'siteLinkGroups' )
		);

		$this->initServices(
			WikibaseRepo::getDefaultInstance()->getEntityTitleLookup(),
			WikibaseRepo::getDefaultInstance()->getSiteStore(),
			WikibaseRepo::getDefaultInstance()->getStore()->newSiteLinkCache()
		);
	}

	/**
	 * Initialize essential settings for this special page.
	 * may be used by unit tests to override global settings.
	 *
	 * @param $normalizeItemByTitlePageNames
	 * @param $siteLinkGroups
	 */
	public function initSettings(
		$normalizeItemByTitlePageNames,
		$siteLinkGroups
	) {
		$this->normalizeItemByTitlePageNames = $normalizeItemByTitlePageNames;
		$this->groups = $siteLinkGroups;
	}

	/**
	 * Initialize the services used be this special page.
	 * May be used to inject mock services for testing.
	 *
	 * @param EntityTitleLookup $titleLookup
	 * @param SiteStore $siteStore
	 * @param SiteLinkLookup $siteLinkLookup
	 */
	public function initServices(
		EntityTitleLookup $titleLookup,
		SiteStore $siteStore,
		SiteLinkLookup $siteLinkLookup
	) {
		$this->titleLookup = $titleLookup;
		$this->sites = $siteStore;
		$this->siteLinkLookup = $siteLinkLookup;
	}

	/**
	 * @see SpecialItemResolver::execute
	 *
	 * @since 0.1
	 */
	public function execute( $subPage ) {
		parent::execute( $subPage );

		// Setup
		$request = $this->getRequest();
		$parts = ( $subPage === '' ) ? array() : explode( '/', $subPage, 2 );
		$site = trim( $request->getVal( 'site', isset( $parts[0] ) ? $parts[0] : '' ) );
		$page = trim( $request->getVal( 'page', isset( $parts[1] ) ? $parts[1] : '' ) );

		$itemContent = null;

		// If there are enough data, then try to lookup the item content
		if ( isset( $site ) && isset( $page ) ) {
			// Try to get a item content
			$siteId = $this->stringNormalizer->trimToNFC( $site ); // no stripping of underscores here!
			$pageName = $this->stringNormalizer->trimToNFC( $page );

			if ( !$this->sites->getSite( $siteId ) ) {
				// HACK: If the site ID isn't known, add "wiki" to it; this allows the wikipedia
				// subdomains to be used to refer to wikipedias, instead of requiring their
				// full global id to be used.
				// @todo: Ideally, if the site can't be looked up by global ID, we
				// should try to look it up by local navigation ID.
				// Support for this depends on bug 48934.
				$siteId .= 'wiki';
			}

			/* @var ItemHandler $itemHandler */
			$itemId = $this->siteLinkLookup->getItemIdForLink( $siteId, $pageName );

			// Do we have an item content, and if not can we try harder?
			if ( $itemId === null && $this->normalizeItemByTitlePageNames === true ) {
				// Try harder by requesting normalization on the external site
				$siteObj = $this->sites->getSite( $siteId );
				if ( $siteObj instanceof Site ) {
					$pageName = $siteObj->normalizePageName( $page );
					$itemId = $this->siteLinkLookup->getItemIdForLink( $siteId, $pageName );
				}
			}

			// Redirect to the item page if we found its content
			if ( $itemId !== null ) {
				$title = $this->titleLookup->getTitleForId( $itemId );
				$itemUrl = $title->getFullUrl();
				$this->getOutput()->redirect( $itemUrl );
				return;
			}
		}

		// If there is no item content post the switch form
		$this->switchForm( $site, $page );
	}

	/**
	 * Output a form to allow searching for a page
	 *
	 * @since 0.1
	 *
	 * @param string $siteId
	 * @param string $page
	 */
	protected function switchForm( $siteId, $page ) {
		if ( $this->sites->getSites()->hasSite( $siteId ) ) {
			$site = $this->sites->getSite( $siteId );
			$siteExists = in_array( $site->getGroup(), $this->groups );
		} else {
			$siteExists = false;
		}

		wfDebugLog( __CLASS__, __FUNCTION__ . ": Site $siteId exists: " . var_export( $siteExists, true ) );

		$this->getOutput()->addModules( 'wikibase.special.itemByTitle' );

		$this->getOutput()->addHTML(
			Html::openElement(
				'form',
				array(
					'method' => 'get',
					'action' => $this->getPageTitle()->getFullUrl(),
					'name' => 'itembytitle',
					'id' => 'wb-itembytitle-form1'
				)
			)
			. Html::openElement( 'fieldset' )
			. Html::element(
				'legend',
				array(),
				$this->msg( 'wikibase-itembytitle-lookup-fieldset' )->text()
			)
			. Html::element(
				'label',
				array( 'for' => 'wb-itembytitle-sitename' ),
				$this->msg( 'wikibase-itembytitle-lookup-site' )->text()
			)
			. Html::input(
				'site',
				$siteId ? htmlspecialchars( $siteId ) : '',
				'text',
				array(
					'id' => 'wb-itembytitle-sitename',
					'size' => 12
				)
			)
			. ' '
			. Html::element(
				'label',
				array( 'for' => 'pagename' ),
				$this->msg( 'wikibase-itembytitle-lookup-page' )->text()
			)
			. Html::input(
				'page',
				$page ? htmlspecialchars( $page ) : '',
				'text',
				array(
					'id' => 'pagename',
					'size' => 36,
					'class' => 'wb-input-text'
				)
			)
			. Html::input(
				'submit',
				$this->msg( 'wikibase-itembytitle-submit' )->text(),
				'submit',
				array(
					'id' => 'wb-itembytitle-submit',
					'class' => 'wb-input-button'
				)
			)
			. Html::closeElement( 'fieldset' )
			. Html::closeElement( 'form' )
		);

		if ( $siteExists && isset( $page ) ) {
			$this->getOutput()->addHTML(
				Html::openElement( 'div' )
				. $this->msg( 'wikibase-itembytitle-create' )
					->params(
						wfUrlencode( $siteId ? $siteId : '' ),
						wfUrlencode( $page ? $page : '' )
					)
					->parse()
				. Html::closeElement( 'div' )
			);
		}
	}

}
