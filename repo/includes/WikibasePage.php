<?php

/**
 * Class representing a Wikibase page.
 *
 * @since 0.1
 *
 * @file WikibasePage.php
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 */
class WikibasePage extends Article {

    public function view() {
        global $wgOut, $wgLang;
        $wgLang->getCode();
        
        $contentObject = $this->getContentObject();
        $arrayContent = $contentObject->getNativeData();
        $content = new WikibaseContent( $arrayContent );
        $parserOutput = $content->getParserOutput( $this->getTitle() );
        $wgOut->addHTML( $parserOutput->getText() );
        $wgOut->setPageTitle( $content->getLabel( $wgLang ) );
        //$wgOut->setHTMLTitle( $wgOut->msg( 'pagetitle' )->rawParams( Sanitizer::stripAllTags( $content->getLabel( $wgLang ) ) ) );
		
		// make sure required client sided resources will be loaded:
		$wgOut->addModules( 'wikibase' );
    }
	
}
