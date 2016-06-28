<?php

namespace Wikibase\Repo\Specials;

use HTMLForm;
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
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
class SpecialItemByTitle extends SpecialWikibasePage {

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
	 * site link groups
	 *
	 * @var string[]
	 */
	private $groups;

	/**
	 * @see SpecialWikibasePage::__construct
	 *
	 * @since 0.1
	 */
	public function __construct() {
		// args $name, $restriction, $listed
		parent::__construct( 'ItemByTitle', '', true );

		$wikibaseRepo = WikibaseRepo::getDefaultInstance();

		$this->initSettings(
			$wikibaseRepo->getSettings()->getSetting( 'siteLinkGroups' )
		);

		$this->initServices(
			$wikibaseRepo->getEntityTitleLookup(),
			$wikibaseRepo->getSiteStore(),
			$wikibaseRepo->getStore()->newSiteLinkStore()
		);
	}

	/**
	 * Initialize essential settings for this special page.
	 * may be used by unit tests to override global settings.
	 *
	 * @param string[] $siteLinkGroups
	 */
	public function initSettings(
		array $siteLinkGroups
	) {
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
	 * @see SpecialWikibasePage::execute
	 *
	 * @since 0.1
	 *
	 * @param string|null $subPage
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
		if ( $site !== '' && $page !== '' ) {
			// FIXME: This code is duplicated in ItemByTitleHelper::getItemId!
			// Try to get a item content
			$siteId = $this->stringNormalizer->trimToNFC( $site ); // no stripping of underscores here!
			$pageName = $this->stringNormalizer->trimToNFC( $page );

			if ( !$this->sites->getSite( $siteId ) ) {
				// HACK: If the site ID isn't known, add "wiki" to it; this allows the wikipedia
				// subdomains to be used to refer to wikipedias, instead of requiring their
				// full global id to be used.
				// @todo: Ideally, if the site can't be looked up by global ID, we
				// should try to look it up by local navigation ID.
				// Support for this depends on bug T50934.
				$siteId .= 'wiki';
			}

			/* @var ItemHandler $itemHandler */
			$itemId = $this->siteLinkLookup->getItemIdForLink( $siteId, $pageName );

			// Do we have an item content, and if not can we try harder?
			if ( $itemId === null ) {
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
				$query = $request->getValues();
				unset( $query['title'] );
				unset( $query['site'] );
				unset( $query['page'] );
				$itemUrl = $title->getFullURL( $query );
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
	 * @param string $siteId
	 * @param string $page
	 */
	private function switchForm( $siteId, $page ) {
		if ( $this->sites->getSites()->hasSite( $siteId ) ) {
			$site = $this->sites->getSite( $siteId );
			$siteExists = in_array( $site->getGroup(), $this->groups );
		} else {
			$siteExists = false;
		}

		wfDebugLog( __CLASS__, __FUNCTION__ . ": Site $siteId exists: " . var_export( $siteExists, true ) );

		$this->getOutput()->addModules( 'wikibase.special.itemByTitle' );

		$formDescriptor = array(
			'site' => array(
				'name' => 'site',
				'default' => $siteId,
				'type' => 'text',
				'id' => 'wb-itembytitle-sitename',
				'size' => 12,
				'label-message' => 'wikibase-itembytitle-lookup-site'
			),
			'page' => array(
				'name' => 'page',
				'default' => $page ?: '',
				'type' => 'text',
				'id' => 'pagename',
				'size' => 36,
				'label-message' => 'wikibase-itembytitle-lookup-page'
			)
		);

		HTMLForm::factory( 'ooui', $formDescriptor, $this->getContext() )
			->setId( 'wb-itembytitle-form1' )
			->setMethod( 'get' )
			->setSubmitID( 'wb-itembytitle-submit' )
			->setSubmitTextMsg( 'wikibase-itembytitle-submit' )
			->setWrapperLegendMsg( 'wikibase-itembytitle-lookup-fieldset' )
			->setSubmitCallback( function () {// no-op
			} )->show();

		if ( $siteId && !$siteExists ) {
			$this->showErrorHTML( $this->msg( 'wikibase-itembytitle-error-site' ) );
		} elseif ( $siteExists && $page ) {
			$this->showErrorHTML( $this->msg( 'wikibase-itembytitle-error-item' ) );

			$createLink = $this->getTitleFor( 'NewItem' );
			$this->getOutput()->addHTML(
				Html::openElement( 'div' )
				. $this->msg(
					'wikibase-itembytitle-create',
					$createLink->getFullURL( array( 'site' => $siteId, 'page' => $page ) )
				)->parse()
				. Html::closeElement( 'div' )
			);
		}
	}

}
