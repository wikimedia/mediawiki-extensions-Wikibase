<?php

/**
 * Structured data content.
 *
 * TODO: describe exact purpose
 * TODO: don't we want to have a context here? would seem so since we are creating HTML
 * TODO: do we actually want to create HTML here?
 *
 * @since 0.1
 *
 * @file WikibaseContent.php
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 */
class WikibaseContent extends Content {

	/**
	 * @since 0.1
	 * @var WikibaseItem
	 */
	protected $item;
	
	public function __construct( array $data ) {
		parent::__construct( CONTENT_MODEL_WIKIBASE );
		$this->data = $data;
		$this->item = WikibaseItem::newFromArray( $data );
	}

	/**
	 * Returns the WikibaseItem part of this WikibaseContent.
	 *
	 * @since 0.1
	 *
	 * @return WikibaseItem $item
	 */
	public function getItem() {
		return $this->item;
	}

	/**
	 * Sets the WikibaseItem part of this WikibaseContent.
	 *
	 * @since 0.1
	 *
	 * @param WikibaseItem $item
	 */
	public function setItem( WikibaseItem $item ) {
		$this->item = $item;
	}
    public function __construct( $data ) {
        parent::__construct( CONTENT_MODEL_WIKIBASE );

	/**
	 * @return String a string representing the content in a way useful for building a full text search index.
	 *		 If no useful representation exists, this method returns an empty string.
	 */
	public function getTextForSearchIndex() {
		return ''; #TODO: recursively collect all values from all properties.
	}

	/**
	 * @return String the wikitext to include when another page includes this  content, or false if the content is not
	 *		 includable in a wikitext page.
	 */
	public function getWikitextForTransclusion() {
		return false;
	}

	/**
	 * Returns a textual representation of the content suitable for use in edit summaries and log messages.
	 *
	 * @param int $maxlength maximum length of the summary text
	 * @return String the summary text
	 */
	public function getTextForSummary( $maxlength = 250 ) {
		return $this->item->getDescription( $GLOBALS['wgLang'] );
	}

	/**
	 * Returns native represenation of the data. Interpretation depends on the data model used,
	 * as given by getDataModel().
	 *
	 * @return mixed the native representation of the content. Could be a string, a nested array
	 *		 structure, an object, a binary blob... anything, really.
	 */
	public function getNativeData() {
		return $this->data;
	}

	/**
	 * returns the content's nominal size in bogo-bytes.
	 *
	 * @return int
	 */
	public function getSize()  {
		return strlen( serialize( $this->data ) ); #TODO: keep and reuse value, content object is immutable!
	}

	/**
	 * Returns true if this content is countable as a "real" wiki page, provided
	 * that it's also in a countable location (e.g. a current revision in the main namespace).
	 *
	 * @param $hasLinks Bool: if it is known whether this content contains links, provide this information here,
	 *						to avoid redundant parsing to find out.
	 */
	public function isCountable( $hasLinks = null ) {
		return !empty( $this->data[ WikibaseContent::PROP_DESCRIPTION ] ); #TODO: better/more methods
	}

	public function isEmpty()  {
		return empty( $this->data );
	}

	/**
	 * @param null|Title $title
	 * @param null $revId
	 * @param null|ParserOptions $options
	 * @return ParserOutput
	 */
	public function getParserOutput( Title $title = null, $revId = null, ParserOptions $options = NULL )  {
		global $wgLang;

		// FIXME: StubUserLang::_unstub() not yet called in certain cases, dummy call to init Language object to $wgLang
		// TODO: use $options->getTargetLanguage() ?
		$wgLang->getCode();

		$parserOutput = new ParserOutput( $this->generateHtml( $wgLang ) );

		$parserOutput->addSecondaryDataUpdate( new WikibaseItemStructuredSave( $this->item, $title ) );

		return $parserOutput;
	}

	/**
	 * TODO: we sure we want to do this here? I'd expect to do this in some kind of view action...
	 *
	 * @param null|Language $lang
	 * @return String
	 */
	private function generateHtml( Language $lang = null ) {
		// TODO: generate sensible HTML!
		$html = '';
		$label =  $this->item->getLabel( $lang );
		if ( $label === null ) {
			$label = '';
		}
		$description =  $this->item->getDescription( $lang );
		if ( $description === null ) {
			$description = '';
		}
		$html .= Html::element( 'h1', null, $label );
		$html .= Html::element( 'p', null, $description );
		$html .= Html::element( 'hr', null, null );
		$htmlTable = '';

		foreach ( $this->item->getTitles( $lang ) AS $language => $value ) {
			$htmlTable .= "\t\t";
			$htmlTable .= Html::openElement( 'tr' );
			$htmlTable .= Html::element( 'td', null, $language );
			$htmlTable .= Html::openElement ( 'td' );
			$link = 'http://'.$language.'.wikipedia.org/'.$value;
			$htmlTable .= Html::element( 'a', array( 'href' => $link ), $value );
			$htmlTable .= Html::closeElement( 'td' );
			$htmlTable .= Html::closeElement( 'tr' );
			$htmlTable .= "\n";
		}
		$htmlTable = Html::rawElement( 'table', array( 'class' => 'wikitable'), $htmlTable );
		$html .= $htmlTable;

    /**
     * @param null|Language $lang
     * @return String
     */
    private function generateHtml( Language $lang = null ) {
        // TODO: generate sensible HTML!
        $html = '';
        $label =  $this->getLabel( $lang );
        if ( $label === null ) {
            $label = '';
        }
        $description =  $this->getDescription( $lang );
        if ( $description === null ) {
            $description = '';
        }
        
        $html .= Html::openElement( 'div', array( 'class' => 'wb-property-container' ) );
        $html .= HTML::element( 'div', array( 'class' => 'wb-property-container-key', 'title' => 'description' ) );
		$html .= HTML::element( 'span', array( 'class' => 'wb-property-container-value'), $description );
        $html .= Html::closeElement('div');

        $htmlTable = '';

		return $html;
	}

}

