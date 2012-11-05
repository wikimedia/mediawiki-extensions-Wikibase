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
	 * @var string
	 */
	protected $language = '';

	public function __construct() {
		parent::__construct( 'EntitiesWithoutLabel' );

	}

	public function execute( $subPage ) {

		$this->setHeaders();

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
			parent::execute( $subPage );
		}
	}

	/**
	 * @see Is this report expensive, i.e should it be cached?
	 *
	 * @return Boolean
	 */
	public function isExpensive() {
		return true;
	}

	/**
	 * Is there a feed available?
	 *
	 * @return Boolean
	 */
	public function isSyndicated() {
		return false;
	}

	/**
	 * Sort the results in descending order?
	 *
	 * @return Boolean
	 */
	public function sortDescending() {
		return true;
	}

	/**
	 * Does this query return timestamps rather than integers in its 'value' field?
	 *
	 * @return Boolean
	 */
	public function usesTimestamps() {
		return true;
	}

	public function getQueryInfo() {
		$dbr = wfGetDB( DB_SLAVE );
		return array(
			'tables' => array( 'page', 'revision', 'wb_entity_per_page', 'wb_terms' ),
			'fields' => array(
					'namespace' => 'page_namespace',
					'title' => 'page_title',
					'value' => 'rev_timestamp'
			),
			'conds' => array(
					'page_latest = rev_id',
					'page_id = epp_page_id',
					'term_entity_type IS NULL'
			),
			'join_conds' => array( 'wb_terms' => array(
				'LEFT JOIN',
				'term_entity_id = epp_entity_id AND term_entity_type = epp_entity_type AND term_type = \'label\' AND term_language = ' . $dbr->addQuotes( $this->language )
			) )
		);
	}

	/**
	 * Format a result row
	 *
	 * @param $skin Skin to use for UI elements
	 * @param $row Result row
	 * @return String
	 */
	public function formatResult( $skin, $row ) {
		$title = Title::makeTitleSafe( $row->namespace, $row->title );
		if ( $title instanceof Title ) {
			return Linker::linkKnown( $title );
		} else {
			return Html::element( 'span', array( 'class' => 'mw-invalidtitle' ),
			Linker::getInvalidTitleDescription( $this->getContext(), $row->namespace, $row->title ) );
		}
	}
}
