<?php

namespace Wikibase;

use Language;
use Message;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class CopyrightMessageBuilder {

	/**
	 * Returns an appropriate copyright message containing a link to the wiki's copyright policy.
	 *
	 * @param string $rightsUrl
	 * @param string $rightsText
	 * @param Language $language
	 *
	 * @return Message
	 */
	public function build( $rightsUrl, $rightsText, Language $language ) {
		$renderedSaveMessage = $this->renderSaveMessage( $language );
		$renderedCopyrightPageMessage = $this->renderCopyrightPageMessage( $language );

		$rightsWarningMessage = new Message(
			'wikibase-shortcopyrightwarning',
			array( $renderedSaveMessage, $renderedCopyrightPageMessage, "[$rightsUrl $rightsText]" )
		);

		return $rightsWarningMessage;
	}

	/**
	 * @param Language $language
	 *
	 * @return string
	 */
	private function renderSaveMessage( Language $language ) {
		$saveMessage = new Message( 'wikibase-save' );
		return $saveMessage->inLanguage( $language )->text();
	}

	/**
	 * @param Language $language
	 *
	 * @return string
	 */
	private function renderCopyrightPageMessage( Language $language ) {
		$copyrightPageMessage = new Message( 'copyrightpage' );
		return $copyrightPageMessage->inLanguage( $language )->text();
	}

}
