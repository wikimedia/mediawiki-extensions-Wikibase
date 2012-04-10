<?php

/**
 *
 *
 * @since 0.1
 *
 * @file WikidataPage.settings.php
 * @ingroup WikidataRepo
 *
 * @licence GNU GPL v2+
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
