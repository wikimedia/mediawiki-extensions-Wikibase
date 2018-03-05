<?php

namespace Wikibase\Lib;

use Exception;

/**
 * Used in special pages and elsewhere to handle user input errors,
 * allow them to bubble up to presentation layer and contain message
 * that can be displayed to the user in their language.
 *
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class UserInputException extends MessageException {

	/**
	 * @see MessageException::__construct
	 *
	 * @param string $key
	 * @param array $params List of parameters, depending on the message. Since this is a "user
	 *  input" exception, callers are expected to pass in unescaped user input. This class will take
	 *  care of proper wikitext escaping.
	 * @param string $message
	 * @param Exception|null $previous
	 */
	public function __construct( $key, array $params, $message, Exception $previous = null ) {
		parent::__construct( $key, array_map( 'wfEscapeWikiText', $params ), $message, $previous );
	}

}
