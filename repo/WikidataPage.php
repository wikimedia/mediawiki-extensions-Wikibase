<?php
/**
 * @file
 * @ingroup Wikidata
 */

/**
 * Class for the data view pages.
 * 
 * @since 0.1
 */
class WikidataPage extends Article {

    public function view() {
        global $wgOut;
        $contentObject = $this->getContentObject();
        $arrayContent = $contentObject->getNativeData();
        $content = new WikidataContent( $arrayContent );
        $parserOutput = $content->getParserOutput( $this->getTitle() );
        $wgOut->addHTML( $parserOutput->getText() );
    }
	
}
