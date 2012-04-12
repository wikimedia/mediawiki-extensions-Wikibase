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
		$contentObject = $this->getContentObject();
		$arrayContent = $contentObject->getNativeData();
		$content = new WikibaseContent( $arrayContent );
		$parserOutput = $content->getParserOutput( $this->getTitle() );

		$out = $this->getContext()->getOutput();
		$out->addHTML( $parserOutput->getText() );
		
		// make sure required client sided resources will be loaded:
		$out->addModules( 'wikibase' );
	}
	
}
