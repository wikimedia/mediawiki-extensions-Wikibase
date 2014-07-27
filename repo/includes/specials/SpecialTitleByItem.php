<?php

namespace Wikibase\Repo\Specials;

use InvalidArgumentException;
use Html;
use SiteStore;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Lib\Store\SiteLinkLookup;

/**
 * Enables accessing a linked page on a site by providing the item id and site
 * id.
 *
 * @licence GNU GPL v2+
 */
class SpecialTitleByItem extends SpecialItemResolver {

	/**
	 * @var SiteStore
	 */
	private $siteStore;

	/**
	 * @var SiteLinkLookup
	 */
	private $siteLinkLookup;

	/**
	 * @see SpecialItemResolver::__construct
	 */
	public function __construct() {
		parent::__construct( 'TitleByItem', '', true );

		$settings = WikibaseRepo::getDefaultInstance()->getSettings();

		$this->initServices(
			WikibaseRepo::getDefaultInstance()->getSiteStore(),
			WikibaseRepo::getDefaultInstance()->getStore()->newSiteLinkCache()
		);
	}

	/**
	 * Initialize the services used be this special page.
	 * May be used to inject mock services for testing.
	 *
	 * @param SiteStore $siteStore
	 * @param SiteLinkLookup $siteLinkLookup
	 */
	public function initServices(
		SiteStore $siteStore,
		SiteLinkLookup $siteLinkLookup
	) {
		$this->siteStore = $siteStore;
		$this->siteLinkLookup = $siteLinkLookup;
	}

	/**
	 * @see SpecialItemResolver::execute
	 */
	public function execute( $subPage ) {
		parent::execute( $subPage );

		$request = $this->getRequest();
		$parts = ( $subPage === '' ) ? array() : explode( '/', $subPage, 2 );
		$site = trim( $request->getVal( 'site', isset( $parts[0] ) ? $parts[0] : '' ) );

		$itemString = trim( $request->getVal( 'itemid', isset( $parts[1] ) ? $parts[1] : 0 ) );
		try {
			$itemId = new ItemId( $itemString );
		} catch ( InvalidArgumentException $ex ) {
			$itemId = null;
			$itemString = '';
                }

		if ( $site !== '' && $itemId !== null ) {
			$site = $this->stringNormalizer->trimToNFC( $site );

			if ( !$this->siteStore->getSite( $site ) ) {
				// HACK: If the site ID isn't known, add "wiki" to it; this allows the wikipedia
				// subdomains to be used to refer to wikipedias, instead of requiring their
				// full global id to be used.
				// @todo: Ideally, if the site can't be looked up by global ID, we
				// should try to look it up by local navigation ID.
				// Support for this depends on bug 48934.
				$site .= 'wiki';
			}

			$links = $this->siteLinkLookup->getLinks( array( $itemId->getNumericId() ), array( $site ) );

			if ( isset( $links[0] ) ) {
				list( , $pageName, ) = $links[0];
				$siteObj = $this->siteStore->getSite( $site );
				$url = $siteObj->getPageUrl( $pageName );

				$this->getOutput()->redirect( $url );
				return;
			}

		}

		// If there is no sitelink to redirect to post the switch form
		$this->switchForm( $site, $itemString );
	}

	/**
	 * Output a form to go to a page for an item
	 *
	 * @param string $site
	 * @param string $itemString
	 */
	protected function switchForm( $site, $itemString ) {
		$this->getOutput()->addModules( 'wikibase.special.titleByItem' );

		$this->getOutput()->addHTML(
			Html::openElement(
				'form',
				array(
					'method' => 'get',
					'action' => $this->getPageTitle()->getFullUrl(),
					'name' => 'titlebyitem',
					'id' => 'wb-titlebyitem-form1'
				)
			)
			. Html::openElement( 'fieldset' )
			. Html::element(
				'legend',
				array(),
				$this->msg( 'wikibase-titlebyitem-lookup-fieldset' )->text()
			)
			. Html::element(
				'label',
				array( 'for' => 'wb-titlebyitem-sitename' ),
				$this->msg( 'wikibase-titlebyitem-lookup-site' )->text()
			)
			. Html::input(
				'site',
				$site ? htmlspecialchars( $site ) : '',
				'text',
				array(
					'id' => 'wb-titlebyitem-sitename',
					'size' => 12
				)
			)
			. ' '
			. Html::element(
				'label',
				array( 'for' => 'itemid' ),
				$this->msg( 'wikibase-titlebyitem-lookup-item' )->text()
			)
			. Html::input(
				'itemid',
				$itemString ? htmlspecialchars( $itemString ) : '',
				'text',
				array(
					'id' => 'itemid',
					'size' => 36,
					'class' => 'wb-input-text'
				)
			)
			. Html::input(
				'submit',
				$this->msg( 'wikibase-titlebyitem-submit' )->text(),
				'submit',
				array(
					'id' => 'wb-titlebyitem-submit',
					'class' => 'wb-input-button'
				)
			)
			. Html::closeElement( 'fieldset' )
			. Html::closeElement( 'form' )
		);
	}
}
