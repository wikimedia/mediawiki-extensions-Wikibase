<?php

namespace Wikibase\Lib\Store;

use Exception;
use RunTimeException;

/**
 * @license GNU GPL v2+
 * @author Lucie-Aimée Kaffee
 */
class PropertyOrderProviderException extends RuntimeException {

	public function __construct(
		$message = 'PropertyOrderProvider Exception',
		$code = 0, Exception $previous = null
	) {
		parent::__construct( $message, $code, $previous );
	}

}
