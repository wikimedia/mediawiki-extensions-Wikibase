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
	 * @param string $saveMessageKey defaults to 'wikibase-save'
	 *
	 * @return Message
	 */
	public function build( $rightsUrl, $rightsText, Language $language, $saveMessageKey = 'wikibase-save' ) {
		$renderedSaveMessage = $this->renderSaveMessage( $language, $saveMessageKey );
		$renderedCopyrightPageMessage = $this->renderCopyrightPageMessage();

		$rightsWarningMessage = new Message(
			'wikibase-shortcopyrightwarning',
			array( $renderedSaveMessage, $renderedCopyrightPageMessage, "[$rightsUrl $rightsText]" )
		);

		return $rightsWarningMessage;
	}

	/**
	 * @param Language $language
	 * @param string $saveMessageKey
	 *
	 * @return string
	 */
	private function renderSaveMessage( Language $language, $saveMessageKey ) {
		$saveMessage = new Message( $saveMessageKey );
		return $saveMessage->inLanguage( $language )->text();
	}

	/**
	 * @return string
	 */
	private function renderCopyrightPageMessage() {
		$copyrightPageMessage = new Message( 'copyrightpage' );
		return $copyrightPageMessage->inContentLanguage()->text();
	}

}
