<?php
class WikidataContent extends Content {

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
    public function getTextForSearchIndex()
    {
        return ''; #TODO: recursively collect all values from all properties.
    }

    /**
     * @return String the wikitext to include when another page includes this  content, or false if the content is not
     *         includable in a wikitext page.
     */
    public function getWikitextForTransclusion()
    {
        return false;
    }

    /**
     * Returns a textual representation of the content suitable for use in edit summaries and log messages.
     *
     * @param int $maxlength maximum length of the summary text
     * @return String the summary text
     */
    public function getTextForSummary($maxlength = 250)
    {
        return $this->getDescription();
    }

    /**
     * Returns native represenation of the data. Interpretation depends on the data model used,
     * as given by getDataModel().
     *
     * @return mixed the native representation of the content. Could be a string, a nested array
     *         structure, an object, a binary blob... anything, really.
     */
    public function getNativeData()
    {
        return $this->mData;
    }

    /**
     * returns the content's nominal size in bogo-bytes.
     *
     * @return int
     */
    public function getSize()
    {
        return strlen( serialize( $this->mData) ); #TODO: keep and reuse value, content object is immutable!
    }

    /**
     * Returns true if this content is countable as a "real" wiki page, provided
     * that it's also in a countable location (e.g. a current revision in the main namespace).
     *
     * @param $hasLinks Bool: if it is known whether this content contains links, provide this information here,
     *                        to avoid redundant parsing to find out.
     */
    public function isCountable($hasLinks = null)
    {
        return !empty( $this->mData[ WikidataContent::PROP_DESCRIPTION ] ); #TODO: better/more methods
    }

    /**
     * @param null|Title $title
     * @param null $revId
     * @param null|ParserOptions $options
     * @return ParserOutput
     */
    public function getParserOutput(Title $title = null, $revId = null, ParserOptions $options = NULL)
    {
        // TODO: generate sensible HTML!

        $serialized = print_r( $this->getNativeData(), true );
        $html = '<pre class="wikidata-data">' . htmlspecialchars( $serialized ) . '</pre>';
        $po = new ParserOutput( $html );

        return $po;

    }

    #=================================================================================================================

    public function getPropertyNames( ) {
        //TODO: implement
    }

    public function getSystemPropertyNames( ) {
        //TODO: implement
    }

    public function getEditorialPropertyNames( ) {
        //TODO: implement
    }

    public function getStatementPropertyNames( ) {
        //TODO: implement
    }

    public function getPropertyMultilang( $name, $languages = null ) {
        //TODO: implement
    }

    public function getProperty( $name, $languag = null ) {
        //TODO: implement
    }

    public function getPropertyType( $name ) {
        //TODO: implement
    }

    public function isStatementProperty( $name ) {
        //TODO: implement
    }

    public function getDescription( $lang = null ) {
        //TODO: implement
    }

    public function getLabel( $lang = null ) {
        //TODO: implement
    }
}