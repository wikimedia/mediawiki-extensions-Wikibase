<?php
class WikidataContentHandler extends ContentHandler {

    public function __construct() {
        $formats = array(
            'application/json',
            'application/vnd.php.serialized' #FIXME: find out what mime type the api uses for serialized php objects
        );

        parent::__construct( CONTENT_MODEL_WIKIDATA, $formats );
    }

    public function getDefaultFormat() {
        global $wgWikidataSerialisationFormat;

        return $wgWikidataSerialisationFormat;
    }

    /**
     * @param WikidataContent $content
     * @param null|String $format
     * @return String
     */
    public function serialize(Content $content, $format = null)
    {
        global $wgWikidataSerialisationFormat;

        if ( !$format ) $format = $wgWikidataSerialisationFormat;

        #FIXME: assert $content is a WikidataContent instance
        $data = $content->getNativeData();

        if ( $format == 'application/vnd.php.serialized' ) $blob = serialize( $data );
        else if ( $format == 'application/json' ) $blob = json_encode( $data );
        else throw new MWException( "serialization format $format is not supported for Wikidata content model" );

        return $blob;
    }

    /**
     * @param $blob String
     * @param null|String $format
     * @return WikidataContent
     */
    public function unserialize($blob, $format = null)
    {
        global $wgWikidataSerialisationFormat;

        if ( !$format ) $format = $wgWikidataSerialisationFormat;

        if ( $format == 'application/vnd.php.serialized' ) $data = unserialize( $blob );
        else if ( $format == 'application/json' ) $data = json_decode( $blob );
        else throw new MWException( "serialization format $format is not supported for Wikidata content model" );

        return new WikidataContent( $data );
    }

    /**
     * @return WikidataContent
     */
    public function emptyContent()
    {
        $data = array();
        return new WikidataContent( $data );
    }
}