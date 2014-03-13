<?php

namespace Wikibase;

use Language;
use Message;

/**
 * Formats a parser error message
 *
 * @todo is there nothing like this in core? if not, move to core
 *
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class ParserErrorMessageFormatter {

	/* @var Language $language */
	protected $language;

	/**
	 * @since 0.4
	 *
	 * @param Language $language
	 */
	public function __construct( Language $language ) {
		$this->language = $language;
	}

	/**
	 * Formats an error message
	 * @todo is there really nothing like this function in core?
	 *
	 * @since 0.4
	 *
	 * @param Message $message
	 *
	 * @return string
	 */
	public function format( Message $message ) {
		return '';
	/*	return \Html::rawElement(
			'span',
			array( 'class' => 'error' ),
            $message->inLanguage( $this->language )->text()
		);
	*/
	}

}
