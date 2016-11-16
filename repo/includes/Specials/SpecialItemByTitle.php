<?php

namespace Wikibase\Repo\Specials;

use HTMLForm;
use Html;
use Site;
use SiteStore;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Lib\Store\EntityTitleStoreLookup;
use Wikibase\Lib\Store\SiteLinkLookup;
use Wikibase\Repo\SiteLinkTargetProvider;
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
	 * @var EntityTitleStoreLookup
	 */
	private $titleLookup;

	/**
	 * @var LanguageNameLookup
	 */
	private $languageNameLookup;

	/**
	 * @var SiteStore
	 */
	private $sites;

	/**
	 * @var SiteLinkLookup
	 */
	private $siteLinkLookup;

	/**
	 * @var SiteLinkTargetProvider
	 */
	private $siteLinkTargetProvider;

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

		$siteLinkTargetProvider = new SiteLinkTargetProvider(
			$wikibaseRepo->getSiteStore(),
			$wikibaseRepo->getSettings()->getSetting( 'specialSiteLinkGroups' )
		);

		$this->initServices(
			$wikibaseRepo->getEntityTitleLookup(),
			new LanguageNameLookup(),
			$wikibaseRepo->getSiteStore(),
			$wikibaseRepo->getStore()->newSiteLinkStore(),
			$siteLinkTargetProvider
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
	 * @param EntityTitleStoreLookup $titleLookup
	 * @param LanguageNameLookup $languageNameLookup
	 * @param SiteStore $siteStore
	 * @param SiteLinkLookup $siteLinkLookup
	 * @param SiteLinkTargetProvider $siteLinkTargetProvider
	 */
	public function initServices(
		EntityTitleStoreLookup $titleLookup,
		LanguageNameLookup $languageNameLookup,
		SiteStore $siteStore,
		SiteLinkLookup $siteLinkLookup,
		SiteLinkTargetProvider $siteLinkTargetProvider
	) {
		$this->titleLookup = $titleLookup;
		$this->languageNameLookup = $languageNameLookup;
		$this->sites = $siteStore;
		$this->siteLinkLookup = $siteLinkLookup;
		$this->siteLinkTargetProvider = $siteLinkTargetProvider;
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
	 * Return options for the site input field.
	 *
	 * @return array
	 */
	private function getSiteOptions() {
		$options = array();
		foreach ( $this->siteLinkTargetProvider->getSiteList( $this->groups ) as $site ) {
			$siteId = $site->getGlobalId();
			$languageName = $this->languageNameLookup->getName( $site->getLanguageCode() );
			$options["$languageName ($siteId)"] = $siteId;
		}
		return $options;
	}

	/**
	 * Output a form to allow searching for a page
	 *
	 * @param string $siteId
	 * @param string $page
	 */
	private function switchForm( $siteId, $page ) {
		$siteExists = $siteId
			&& $this->siteLinkTargetProvider->getSiteList( $this->groups )->hasSite( $siteId );

		wfDebugLog( __CLASS__, __FUNCTION__ . ": Site $siteId exists: " . var_export( $siteExists, true ) );

		$formDescriptor = array(
			'site' => array(
				'name' => 'site',
				'default' => $siteId,
				'type' => 'combobox',
				'options' => $this->getSiteOptions(),
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
