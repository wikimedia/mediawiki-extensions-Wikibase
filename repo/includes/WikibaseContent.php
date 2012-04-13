<?php

/**
 * Structured data content.
 *
 * @since 0.1
 *
 * @file WikibaseContent.php
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 */
class WikibaseContent extends Content {

    const TYPE_TEXT = 'text';
    const TYPE_SCALAR = 'scalar'; # unit, precision, point-in-time
    const TYPE_DATE = 'date';
    const TYPE_TERM = 'term'; # lang, pronunciation
    const TYPE_ENTITY_REF = 'ref';

    const PROP_LABEL = 'label';
    const PROP_DESCRIPTION = 'description';
    const PROP_ALIAS = 'alias';

    public function __construct( $data ) {
        parent::__construct( CONTENT_MODEL_WIKIDATA );

        #TODO: assert $data is an array!
        $this->mData = $data;
    }

    /**
     * @return String a string representing the content in a way useful for building a full text search index.
     *         If no useful representation exists, this method returns an empty string.
     */
    public function getTextForSearchIndex() {
        return ''; #TODO: recursively collect all values from all properties.
    }

    /**
     * @return String the wikitext to include when another page includes this  content, or false if the content is not
     *         includable in a wikitext page.
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
        return $this->getDescription(); // FIXME: missing arg
    }

    /**
     * Returns native represenation of the data. Interpretation depends on the data model used,
     * as given by getDataModel().
     *
     * @return mixed the native representation of the content. Could be a string, a nested array
     *         structure, an object, a binary blob... anything, really.
     */
    public function getNativeData() {
        return $this->mData;
    }

    /**
     * returns the content's nominal size in bogo-bytes.
     *
     * @return int
     */
    public function getSize()  {
        return strlen( serialize( $this->mData ) ); #TODO: keep and reuse value, content object is immutable!
    }

    /**
     * Returns true if this content is countable as a "real" wiki page, provided
     * that it's also in a countable location (e.g. a current revision in the main namespace).
     *
     * @param $hasLinks Bool: if it is known whether this content contains links, provide this information here,
     *                        to avoid redundant parsing to find out.
     */
    public function isCountable( $hasLinks = null ) {
        return !empty( $this->mData[ WikibaseContent::PROP_DESCRIPTION ] ); #TODO: better/more methods
    }

    public function isEmpty()  {
        return empty( $this->mData );
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
        $wgLang->getCode();

        $html = $this->generateHtml( $wgLang );
        $po = new ParserOutput( $html );
		
		
		//$html = Html::rawElement('table', array('class' => 'wikitable'), $html);
        //$po = new ParserOutput( $html );

        $labels = array(
            "de" => $title->getText() . " in German",
            "en" => $title->getText() . " in English"
        );

        $label_update = new WikibaseLabelTableUpdate( $title, $labels );
        $po->addSecondaryDataUpdate( $label_update );

        return $po;
    }

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

        foreach ( $this->getTitles( $lang ) AS $language => $value ) {
            $htmlTable .= "\t\t";
            $htmlTable .= Html::openElement( 'tr' );
            $htmlTable .= Html::element( 'td', null, $language );
            $htmlTable .= Html::openElement ( 'td' );
            $link = 'http://'.$language.'.wikipedia.org/wiki/'.$value;
            $htmlTable .= Html::element( 'a', array( 'href' => $link ), $value );
            $htmlTable .= Html::closeElement( 'td' );
            $htmlTable .= Html::closeElement( 'tr' );
            $htmlTable .= "\n";
        }
        $htmlTable = Html::rawElement( 'table', array( 'class' => 'wikitable'), $htmlTable );
        $html .= $htmlTable;

        // debug output
        $htmlTable = '';
        $data = $this->getNativeData();
        $flat = WikibaseContentHandler::flattenArray( $data );
        foreach ( $flat as $k => $v ) {
            $htmlTable .= "\t\t";
            $htmlTable .= Html::openElement( 'tr' );
            $htmlTable .= Html::element( 'td', null, $k );
            $htmlTable .= Html::element( 'td', null, $v );
            $htmlTable .= Html::closeElement( 'tr' );
            $htmlTable .= "\n";
        }
        $htmlTable = Html::rawElement( 'table', array('class' => 'wikitable'), $htmlTable );
        $html .= $htmlTable;

        return $html;
    }

    #=================================================================================================================

    public function getPropertyNames() {
        //TODO: implement
    }

    public function getSystemPropertyNames() {
        //TODO: implement
    }

    public function getEditorialPropertyNames() {
        //TODO: implement
    }

    public function getStatementPropertyNames() {
        //TODO: implement
    }

    public function getPropertyMultilang( $name, $languages = null ) {
        //TODO: implement
    }

    public function getProperty( $name, $lang = null ) {
        //TODO: implement
    }

    public function getPropertyType( $name ) {
        //TODO: implement
    }

    public function isStatementProperty( $name ) {
        //TODO: implement
    }

    /**
     * @param Language $lang
     * @return String|null description
     */
    public function getDescription( Language $lang ) {
        $data = $this->getNativeData();
        if ( !isset( $data['description'][$lang->getCode()] ) ) {
            return null;
        } else {
            return $data['description'][$lang->getCode()]['value'];
        }
    }

    /**
     * @param Language $lang
     * @return String|null label
     */
    public function getLabel( Language $lang ) {
        $data = $this->getNativeData();
        if ( !isset( $data['label'][$lang->getCode()] ) ) {
            return null;
        } else {
            return $data['label'][$lang->getCode()]['value'];
        }
    }

    /**
     * @param Language $lang
     * @return array titles (languageCode => value)
     */
    public function getTitles( Language $lang ) {
        $data = $this->getNativeData();
        $titles = array();
        foreach ( $data['titles'] as $langCode => $title ) {
            $titles[$langCode] = $title['value'];
        }
        return $titles;
    }
}

class WikibaseLabelTableUpdate extends SecondaryDataUpdate {

    public function __construct( Title $title, $labels ) {
        $this->title = $title;
        $this->labels = $labels;
    }

    /**
     * Perform update.
     */
    public function doUpdate() {
        $s = $this->title->getDBkey() . ": " . json_encode( $this->labels );
        $s .= "\n";

        file_put_contents( "/tmp/updatetest.txt", $s, FILE_APPEND );
    }

}
