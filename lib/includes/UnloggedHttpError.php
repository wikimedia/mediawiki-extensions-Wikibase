<?php

namespace Wikibase;

use HttpError;

/**
 * Unlogged variant of HttpError.
 * Needed until https://phabricator.wikimedia.org/T85795 got solved.
 *
 * @since 0.5
 * @license GNU GPL v2+
 * @author Marius Hoch < hoo@online.de >
 */
class UnloggedHttpError extends HttpError {
	/**
	 * @see MWException::isLoggable
	 *
	 * @return bool
	 */
	public function isLoggable() {
		return false;
	}

}
