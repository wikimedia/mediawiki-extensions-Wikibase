<?php

/**
 * Page for listing page without label.
 *
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
class SpecialListEntitiesWithoutLabel extends SpecialWikibaseQueryPage {

	/**
	 * @var string
	 */
	protected $language = '';

	public function __construct() {
		parent::__construct( 'ListEntitiesWithoutLabel' );

	}

	public function execute( $subPage ) {

		$this->setHeaders();

		$output = $this->getOutput();
		$request = $this->getRequest();

		$this->language = $request->getText( 'language', '' );
		if ( $this->language === '' && $subPage !== null ) {
			$this->language = $subPage;
		}
		//TODO Validate language code

		$output->addHTML(
			Html::openElement( 'form', array( 'action' => $this->getTitle()->getLocalURL() ) ) .
			Html::openElement( 'fieldset' ) .
			Html::element( 'legend', null, $this->msg( 'wikibase-listentitieswithoutlabel-legend' )->text() ) .
			Html::openElement( 'p' ) .
			Html::element( 'label', array( 'for' => 'language' ), $this->msg( 'wikibase-listentitieswithoutlabel-label-language' )->text() )  . ' ' .
			Html::input( 'language', $this->language ) .
			Html::Input( null, $this->msg( 'wikibase-listentitieswithoutlabel-submit' )->text(), 'submit' ) .
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

	public function getQueryInfo() {
		$dbr = wfGetDB( DB_SLAVE );
		return array(
			'tables' => array( 'page', 'revision', 'wb_entities_per_page' ),
			'fields' => array(
					'namespace' => 'page_namespace',
					'title' => 'page_title',
					'value' => 'rev_timestamp'
			),
			'conds' => array(
					'page_latest = rev_id',
					'page_id = epp_page_id',
					'NOT EXISTS( SELECT NULL FROM wb_terms WHERE `term_entity_id`=`epp_entity_id` AND `term_entity_type`=`epp_entity_type` AND `term_type`=\'label\' AND `term_language`=' . $dbr->addQuotes( $this->language ) . ' )'
			)
		);
	}

	/**
	 * Format a result row
	 *
	 * @param $skin Skin to use for UI elements
	 * @param $result Result row
	 * @return String
	 */
	public function formatResult( $skin, $result ) {
		$title = Title::makeTitleSafe( $result->namespace, $result->title );
		if ( !$title ) {
			return Html::element( 'span', array( 'class' => 'mw-invalidtitle' ),
				Linker::getInvalidTitleDescription( $this->getContext(), $result->namespace, $result->title ) );
		}

		if ( $this->isCached() ) {
			$link = Linker::link( $title );
		} else {
			$link = Linker::linkKnown( $title );
		}

		return $this->getLanguage()->specialList( $link, '' );
	}
}
