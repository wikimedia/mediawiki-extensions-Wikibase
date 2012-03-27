<?php
class WikidataContentHandler extends ContentHandler {

    public function getDifferenceEngine(IContextSource $context, $old = 0, $new = 0, $rcid = 0,
                                        $refreshCache = false, $unhide = false) {

        return new WikidataDifferenceEngine($context, $old, $new, $rcid, $refreshCache, $unhide);
    }

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

        if ( $format == 'application/vnd.php.serialized' ) $data = unserialize( $blob ); #FIXME: suppress notice on failed serialization!
        else if ( $format == 'application/json' ) $data = json_decode( $blob, true ); #FIXME: suppress notice on failed serialization!
        else throw new MWException( "serialization format $format is not supported for Wikidata content model" );

        if ( $data === false || $data === null ) throw new MWContentSerializationException("failed to deserialize");

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

class WikidataDifferenceEngine extends DifferenceEngine {

    function generateContentDiffBody( Content $old, Content $new ) {
        $otext = print_r( $old->getNativeData(), true );
        $ntext = print_r( $new->getNativeData(), true );

        return $this->generateTextDiffBody( $otext, $ntext );
    }

}