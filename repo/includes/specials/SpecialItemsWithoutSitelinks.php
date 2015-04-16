<?php

namespace Wikibase\Repo\Specials;

use Html;
use Wikibase\Repo\Store\EntityPerPage;
use Wikibase\Repo\WikibaseRepo;

/**
 * Page for listing entities without site links.
 *
 * @since 0.4
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class SpecialItemsWithoutSitelinks extends SpecialWikibaseQueryPage {

	/**
	 * @var string|null
	 */
	private $siteId;

	/**
	 * @var EntityPerPage
	 */
	private $entityPerPage;

	public function __construct() {
		parent::__construct( 'ItemsWithoutSitelinks' );

		$this->initServices(
			WikibaseRepo::getDefaultInstance()->getStore()->newEntityPerPage()
		);
	}

	/**
	 * Initialize the services used be this special page.
	 * May be used to inject mock services for testing.
	 *
	 * @param EntityPerPage $entityPerPage
	 */
	public function initServices( EntityPerPage $entityPerPage ) {
		$this->entityPerPage = $entityPerPage;
	}

	/**
	 * @see SpecialWikibasePage::execute
	 *
	 * @since 0.4
	 *
	 * @param string|null $subPage
	 */
	public function execute( $subPage ) {
		parent::execute( $subPage );

		$this->prepareArguments( $subPage );
		$this->showForm();
		$this->showQuery();
	}

	private function prepareArguments( $subPage ) {
		$request = $this->getRequest();

		$this->siteId = $request->getText( 'site', $subPage );
		if ( $this->siteId === '' ) {
			$this->siteId = null;
		}
	}

	private function showForm() {
		$this->getOutput()->addHTML(
			Html::openElement(
				'form',
				array(
					'action' => $this->getPageTitle()->getLocalURL(),
					'name' => 'itemswithoutsitelinks',
					'id' => 'wb-itemswithoutsitelinks-form'
				)
			) .
			Html::openElement( 'fieldset' ) .
			Html::element(
				'legend',
				array(),
				$this->msg( 'wikibase-itemswithoutsitelinks-legend' )->text()
			) .
			Html::openElement( 'p' ) .
			Html::element(
				'label',
				array(
					'for' => 'wb-itemswithoutsitelinks-site'
				),
				$this->msg( 'wikibase-itemswithoutsitelinks-label-site' )->text()
			) . ' ' .
			Html::input(
				'site',
				$this->siteId !== null ? htmlspecialchars( $this->siteId ) : '',
				'text',
				array(
					'id' => 'wb-itemswithoutsitelinks-site',
					'class' => 'wb-input-text'
				)
			) . ' ' .
			Html::input(
				'',
				$this->msg( 'wikibase-entitieswithoutlabel-submit' )->text(),
				'submit',
				array(
					'id' => 'wikibase-listproperties-submit',
					'class' => 'wb-input-button'
				)
			) .
			Html::closeElement( 'p' ) .
			Html::closeElement( 'fieldset' ) .
			Html::closeElement( 'form' )
		);
	}

	/**
	 * @see SpecialWikibaseQueryPage::getResult
	 *
	 * @since 0.4
	 */
	protected function getResult( $offset = 0, $limit = 0 ) {
		return $this->entityPerPage->getItemsWithoutSitelinks( $this->siteId, $limit, $offset );
	}

}
