<?php

/**
 * Page for listing page without label.
 *
 * @since 0.3
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
class SpecialEntitiesWithoutLabel extends SpecialWikibaseQueryPage {

	/**
	 * The language used
	 *
	 * @since 0.3
	 *
	 * @var string
	 */
	protected $language = '';

	public function __construct() {
		parent::__construct( 'EntitiesWithoutLabel' );

	}

	/**
	 * @see SpecialWikibasePage::execute
	 *
	 * @since 0.3
	 *
	 * @param string $subPage
	 * @return bollean
	 */
	public function execute( $subPage ) {
		if ( !parent::execute( $subPage ) ) {
			return false;
		}

		$output = $this->getOutput();
		$request = $this->getRequest();

		$this->language = $request->getText( 'language', '' );
		if ( $this->language === '' && $subPage !== null ) {
			$this->language = $subPage;
		}
		if ( $this->language !== '' && !in_array( $this->language, \Wikibase\Utils::getLanguageCodes() ) ) {
			$output->addWikiMsg( 'wikibase-entitieswithoutlabel-invalid-language', $this->language );
			$this->language = '';
		}

		$output->addHTML(
			Html::openElement( 'form', array( 'action' => $this->getTitle()->getLocalURL() ) ) .
			Html::openElement( 'fieldset' ) .
			Html::element( 'legend', null, $this->msg( 'wikibase-entitieswithoutlabel-legend' )->text() ) .
			Html::openElement( 'p' ) .
			Html::element( 'label', array( 'for' => 'language' ), $this->msg( 'wikibase-entitieswithoutlabel-label-language' )->text() )  . ' ' .
			Html::input( 'language', $this->language ) .
			Xml::submitButton( $this->msg( 'wikibase-entitieswithoutlabel-submit' )->text() ) .
			Html::closeElement( 'p' ) .
			Html::closeElement( 'fieldset' ) .
			Html::closeElement( 'form' )
		);

		if ( $this->language !== '' ) {
			$this->showQuery();
		}
	}

	/**
	 * @see SpecialWikibaseQueryPage::getResult
	 *
	 * @since 0.3
	 *
	 * @param integer $offset
	 * @param integer $limit
	 * @return Title[]
	 */
	protected function getResult( $offset = 0, $limit = 0 ) {
		$entityPerPage = \Wikibase\StoreFactory::getStore( 'sqlstore' )->newEntityPerPage();
		return $entityPerPage->getEntitiesWithoutTerm( \Wikibase\Term::TYPE_LABEL, $this->language, null, $offset, $limit );
	}


	/**
	 * @see SpecialWikibaseQueryPage::getTitleForNavigation
	 *
	 * @since 0.3
	 *
	 * @return Title
	 */
	protected function getTitleForNavigation() {
		return $this->getTitle( $this->language );
	}
}
