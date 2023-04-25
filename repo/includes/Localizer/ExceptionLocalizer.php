<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Localizer;

use Exception;
use InvalidArgumentException;
use Message;

/**
 * Interface for services that provide localized messages for various types of Exceptions.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
interface ExceptionLocalizer {

	/**
	 * Returns a Message object containing a localized message representing the exception,
	 * in a form appropriate for display to the user.
	 *
	 * The localized message may or may not contain the text returned by $ex->getMessage().
	 *
	 * @throws InvalidArgumentException If localization of the given exception is not supported.
	 */
	public function getExceptionMessage( Exception $ex ): Message;

	/**
	 * Whether this localizer can handle the given exception.
	 *
	 * This is intended for use by a dispatcher to determine which localizer
	 * can handle a given exception.
	 */
	public function hasExceptionMessage( Exception $ex ): bool;

}
