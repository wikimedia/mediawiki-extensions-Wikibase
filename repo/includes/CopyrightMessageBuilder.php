<?php

namespace Wikibase\Repo;

use Language;
use Message;

/**
 * @license GPL-2.0-or-later
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

		return wfMessage( 'wikibase-shortcopyrightwarning' )
			->params(
				$renderedSaveMessage,
				$renderedCopyrightPageMessage,
				$rightsUrl,
				$rightsText
			);
	}

	/**
	 * @param Language $language
	 * @param string $key
	 *
	 * @return string Plain text
	 */
	private function renderSaveMessage( Language $language, $key ) {
		return wfMessage( $key )->inLanguage( $language )->text();
	}

	/**
	 * @return string Plain text
	 */
	private function renderCopyrightPageMessage() {
		return wfMessage( 'copyrightpage' )->inContentLanguage()->text();
	}

}
