<?php

namespace Wikibase\Repo\Specials;

use Html;
use InvalidArgumentException;
use SiteStore;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\Lib\Store\RedirectResolvingEntityLookup;
use Wikibase\Repo\WikibaseRepo;

/**
 * Enables accessing a linked page on a site by providing the item id and site
 * id.
 *
 * @licence GNU GPL v2+
 * @author Jan Zerebecki
 */
class SpecialGoToLinkedPage extends SpecialWikibasePage {

	/**
	 * @var SiteStore
	 */
	private $siteStore;

	/**
	 * @var RedirectResolvingEntityLookup
	 */
	private $entityLookup;

	/**
	 * @see SpecialWikibasePage::__construct
	 */
	public function __construct() {
		parent::__construct( 'GoToLinkedPage', '', true );

		$this->initServices(
			WikibaseRepo::getDefaultInstance()->getSiteStore(),
			WikibaseRepo::getDefaultInstance()->getEntityLookup()
		);
	}

	/**
	 * Initialize the services used be this special page.
	 * May be used to inject mock services for testing.
	 *
	 * @param SiteStore $siteStore
	 * @param RedirectResolvingEntityLookup $entityLookup
	 */
	public function initServices(
		SiteStore $siteStore,
		RedirectResolvingEntityLookup $entityLookup
	) {
		$this->siteStore = $siteStore;
		$this->entityLookup = $entityLookup;
	}

	/**
	 * @param string $subPage
	 * @return array array( string $site, ItemId $itemId, string $itemString )
	 */
	protected function getArguments( $subPage ) {
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

		return array( $site, $itemId, $itemString );
	}

	/**
	 * @param string $site
	 * @param ItemId|null $itemId
	 * @return string|null the URL to redirect to or null if the sitelink does not exist
	 */
	protected function getTargetUrl( $site, ItemId $itemId = null ) {
		if ( $site === '' || $itemId === null ) {
			return null;
		}
		$site = $this->stringNormalizer->trimToNFC( $site );

		if ( $this->getPageName( $site, $itemId ) === null ) {
			// HACK: If the site ID isn't known, add "wiki" to it; this allows the wikipedia
			// subdomains to be used to refer to wikipedias, instead of requiring their
			// full global id to be used.
			// @todo: Ideally, if the site can't be looked up by global ID, we
			// should try to look it up by local navigation ID.
			// Support for this depends on bug 48934.
			$site .= 'wiki';
		}

		$pageName = $this->getPageName( $site, $itemId );

		if ( $pageName !== null ) {
			$siteObj = $this->siteStore->getSite( $site );

			return $siteObj->getPageUrl( $pageName );
		}

		return null;
	}

	/**
	 * @param string $site
	 * @param ItemId $itemId
	 *
	 * @return string|null
	 */
	private function getPageName( $site, ItemId $itemId ) {
		/* @var $item Item */
		$item = $this->entityLookup->getEntity( $itemId );
		if ( !$item ) {
			return null;
		}

		$siteLinkList = $item->getSiteLinkList();

		if ( $siteLinkList->hasLinkWithSiteId( $site ) ) {
			return $siteLinkList->getBySiteId( $site )->getPageName();
		}

		return null;
	}

	/**
	 * @see SpecialWikibasePage::execute
	 *
	 * @param string|null $subPage
	 */
	public function execute( $subPage ) {
		parent::execute( $subPage );
		list( $site, $itemId, $itemString ) = $this->getArguments( $subPage );

		$url = $this->getTargetUrl( $site, $itemId );
		if ( null === $url ) {
			$this->outputForm( $site, $itemString );
		} else {
			$this->getOutput()->redirect( $url );
		}
	}

	/**
	 * Output a form via the context's OutputPage object to go to a
	 * sitelink (linked page) for an item and site id.
	 *
	 * @param string $site
	 * @param string $itemString
	 */
	protected function outputForm( $site, $itemString ) {
		$this->getOutput()->addModules( 'wikibase.special.goToLinkedPage' );

		$this->getOutput()->addHTML(
			Html::openElement(
				'form',
				array(
					'method' => 'get',
					'action' => $this->getPageTitle()->getFullUrl(),
					'name' => 'gotolinkedpage',
					'id' => 'wb-gotolinkedpage-form1'
				)
			)
			. Html::openElement( 'fieldset' )
			. Html::element(
				'legend',
				array(),
				$this->msg( 'wikibase-gotolinkedpage-lookup-fieldset' )->text()
			)
			. Html::element(
				'label',
				array( 'for' => 'wb-gotolinkedpage-sitename' ),
				$this->msg( 'wikibase-gotolinkedpage-lookup-site' )->text()
			)
			. Html::input(
				'site',
				$site ? htmlspecialchars( $site ) : '',
				'text',
				array(
					'id' => 'wb-gotolinkedpage-sitename',
					'size' => 12
				)
			)
			. ' '
			. Html::element(
				'label',
				array( 'for' => 'wb-gotolinkedpage-itemid' ),
				$this->msg( 'wikibase-gotolinkedpage-lookup-item' )->text()
			)
			. Html::input(
				'itemid',
				$itemString ? htmlspecialchars( $itemString ) : '',
				'text',
				array(
					'id' => 'wb-gotolinkedpage-itemid',
					'size' => 36,
					'class' => 'wb-input-text'
				)
			)
			. Html::input(
				'submit',
				$this->msg( 'wikibase-gotolinkedpage-submit' )->text(),
				'submit',
				array(
					'id' => 'wb-gotolinkedpage-submit',
					'class' => 'wb-input-button'
				)
			)
			. Html::closeElement( 'fieldset' )
			. Html::closeElement( 'form' )
		);
	}

}
