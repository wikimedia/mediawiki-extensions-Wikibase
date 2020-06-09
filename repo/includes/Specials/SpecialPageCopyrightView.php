<?php

namespace Wikibase\Repo\Specials;

use Html;
use Language;
use Message;
use Wikibase\Repo\CopyrightMessageBuilder;

/**
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class SpecialPageCopyrightView {

	/**
	 * @var CopyrightMessageBuilder
	 */
	private $messageBuilder;

	/**
	 * @var string
	 */
	private $rightsUrl;

	/**
	 * @var string
	 */
	private $rightsText;

	/**
	 * @param CopyrightMessageBuilder $messageBuilder
	 * @param string $rightsUrl
	 * @param string $rightsText
	 */
	public function __construct( CopyrightMessageBuilder $messageBuilder, $rightsUrl, $rightsText ) {
		$this->messageBuilder = $messageBuilder;
		$this->rightsUrl = $rightsUrl;
		$this->rightsText = $rightsText;
	}

	/**
	 * @param Language $language
	 * @param string $saveMessageKey
	 *
	 * @return string
	 */
	public function getHtml( Language $language, $saveMessageKey ) {
		$message = $this->getCopyrightMessage( $language, $saveMessageKey );
		$renderedMessage = $this->render( $message, $language );

		return $this->wrapMessage( $renderedMessage );
	}

	/**
	 * @param Language $language
	 * @param string $saveMessageKey
	 *
	 * @return Message
	 */
	private function getCopyrightMessage( Language $language, $saveMessageKey ) {
		$copyrightMessage = $this->messageBuilder->build(
			$this->rightsUrl,
			$this->rightsText,
			$language,
			$saveMessageKey
		);

		return $copyrightMessage;
	}

	/**
	 * @param Message $copyrightMessage
	 * @param Language $language
	 *
	 * @return string
	 */
	private function render( Message $copyrightMessage, Language $language ) {
		return $copyrightMessage->inLanguage( $language )->parse();
	}

	/**
	 * @param string $renderedMessage
	 *
	 * @return string
	 */
	private function wrapMessage( $renderedMessage ) {
		return Html::rawElement( 'div', [], $renderedMessage );
	}

}
