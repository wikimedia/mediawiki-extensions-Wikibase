<?php
namespace Wikibase;

use Exception;
use RuntimeException;

/**
 * Exception thrown when the actual type of a Snak's DataValue mismatches the
 * value type associated with the DataType or the Snak's property.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class DataTypeMismatchException extends RuntimeException {

	public function __construct( $message = "", $code = 0, Exception $previous = null ) {
		parent::__construct( $message, $code, $previous );
	}
}
 