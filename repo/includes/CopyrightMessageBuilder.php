<?php

namespace Wikibase\Repo;

use MediaWiki\Language\Language;
use MediaWiki\Message\Message;

/**
 * @license GPL-2.0-or-later
 * @author Daniel Werner
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class CopyrightMessageBuilder {

	/**
	 * Returns an appropriate copyright message containing a link to the wiki's copyright policy.
	 * The message is optimized for being displayed as a tooltip on desktop, or in a dialog
	 * for mobile editing.
	 *
	 * @param string $rightsUrl
	 * @param string $rightsText
	 * @param Language $language
	 * @param ?string $saveMessageKey when not given, defaults to wikibase-save or wikibase-publish, depending
	 *   on the value of the global variable $wgEditSubmitButtonLabelPublish.
	 * @param bool $mobileEditing defaults to false
	 *
	 * @return Message
	 */
	public function build(
		string $rightsUrl,
		string $rightsText,
		Language $language,
		?string $saveMessageKey = null,
		bool $mobileEditing = false
	): Message {
		if ( $mobileEditing ) {
			return $this->buildForMobileDialog( $rightsUrl, $rightsText );
		} else {
			return $this->buildForDesktopTooltip( $rightsUrl, $rightsText, $language, $saveMessageKey );
		}
	}

	/**
	 * Returns a copyright message for use within a tooltip in desktop editing
	 *
	 * @param string $rightsUrl
	 * @param string $rightsText
	 * @param Language $language
	 * @param ?string $saveMessageKey
	 *
	 * @return Message
	 */
	private function buildForDesktopTooltip(
		string $rightsUrl,
		string $rightsText,
		Language $language,
		?string $saveMessageKey
	): Message {
		global $wgEditSubmitButtonLabelPublish;

		$saveMessageKey ??= ( $wgEditSubmitButtonLabelPublish ) ? 'wikibase-publish' : 'wikibase-save';

		$renderedSaveMessage = $this->renderSaveMessage( $language, $saveMessageKey );
		$renderedCopyrightPageMessage = $this->renderCopyrightPageMessage();

		if ( $rightsUrl === '' ) {
			return wfMessage( 'wikibase-shortcopyrightwarning-minimal' )
				->params(
					$renderedSaveMessage,
					$renderedCopyrightPageMessage
				);
		}

		return wfMessage( 'wikibase-shortcopyrightwarning' )
			->params(
				$renderedSaveMessage,
				$renderedCopyrightPageMessage,
				$rightsUrl,
				$rightsText
			);
	}

	/**
	 * Returns a copyright message for use within a dialog in mobile editing
	 *
	 * @param string $rightsUrl
	 * @param string $rightsText
	 *
	 * @return Message
	 */
	private function buildForMobileDialog( string $rightsUrl, string $rightsText ): Message {
		$renderedCopyrightPageMessage = $this->renderCopyrightPageMessage();

		if ( $rightsUrl === '' ) {
			return wfMessage( 'wikibase-copyrightdialog-warning-minimal' )
				->params( $renderedCopyrightPageMessage );
		}

		return wfMessage( 'wikibase-copyrightdialog-warning' )
			->params(
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
	private function renderSaveMessage( Language $language, string $key ): string {
		return wfMessage( $key )->inLanguage( $language )->text();
	}

	/**
	 * @return string Plain text
	 */
	private function renderCopyrightPageMessage(): string {
		return wfMessage( 'copyrightpage' )->inContentLanguage()->text();
	}

}
