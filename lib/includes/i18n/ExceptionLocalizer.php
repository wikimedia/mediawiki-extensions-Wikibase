<?php

namespace Wikibase\i18n;

use Exception;
use InvalidArgumentException;
use Message;

/**
 * Interface for services that provide localized messages for various types of Exceptions.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
interface ExceptionLocalizer {

	/**
	 * @param Exception $ex
	 *
	 * @throws InvalidArgumentException If localization of the given exception is not supported.
	 *
	 * @return Message
	 */
	function getExceptionMessage( Exception $ex );

	/**
	 * Whether this localizer can handle the given exception.
	 *
	 * @param Exception $ex
	 *
	 * @return bool
	 */
	function hasExceptionMessage( Exception $ex );

}
