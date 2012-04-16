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

	// TODO: currently we are getting stuff that is not WikibaseContent here sometimes, such as MessageContent when viewing non-existing page.
	// TODO: either this is a bug and should not happen, or we should add handling for this here (MessageContent messages are not parsed ATM).
	public function view() {
		$content = $this->getContentObject();

		$parserOutput = $content->getParserOutput( $this->getTitle() );

		$out = $this->getContext()->getOutput();
		$out->addHTML( $parserOutput->getText() );
		
		// make sure required client sided resources will be loaded:
		$out->addModules( 'wikibase' );
	}
	
}
