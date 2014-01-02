<?php

namespace Wikibase;

use Language;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class CopyrightMessage {

	const VERSION = 'wikibase-1';

	/**
	 * @param Language $language
	 */
	private $language;

	/**
	 * @param Language $language
	 */
	public function __construct( Language $language ) {
		$this->language = $language;
	}

	/**
	 * Returns an appropriate copyright message containing a link to the wiki's copyright policy.
	 *
	 * @return Message
	 */
	public function getMessage( $rightsUrl, $rightsText ) {
		$saveMessage = wfMessage( 'wikibase-save' )->inLanguage( $this->language )->text();
		$copyrightPage = wfMessage( 'copyrightpage' )->inLanguage( $this->language )->text();

		$rightsWarningMessage = wfMessage( 'wikibase-shortcopyrightwarning',
			$saveMessage, $copyrightPage, "[$rightsUrl $rightsText]"
		)->inLanguage( $this->language )->parse();

		return $rightsWarningMessage;
	}

	/**
	 * Returns a string indicating which version of the copyright message is being used when
	 * calling getCopyrightMessage.
	 *
	 * @return string
	 */
	public function getVersion() {
		return self::VERSION;
	}

}
