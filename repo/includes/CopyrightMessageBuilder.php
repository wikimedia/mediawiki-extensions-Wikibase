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

		return wfMessage(
			'wikibase-shortcopyrightwarning',
			$renderedSaveMessage,
			$renderedCopyrightPageMessage,
			"[$rightsUrl $rightsText]"
		);
	}

	/**
	 * @param Language $language
	 * @param string $saveMessageKey
	 *
	 * @return string
	 */
	private function renderSaveMessage( Language $language, $saveMessageKey ) {
		return wfMessage( $saveMessageKey )->inLanguage( $language )->text();
	}

	/**
	 * @return string
	 */
	private function renderCopyrightPageMessage() {
		return wfMessage( 'copyrightpage' )->inContentLanguage()->text();
	}

}
