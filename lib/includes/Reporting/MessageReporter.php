<?php

namespace Wikibase\Lib\Reporting;

/**
 * Interface for objects that can report messages.
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
interface MessageReporter {

	/**
	 * Report the provided message.
	 *
	 * @param string $message
	 */
	public function reportMessage( $message );

}
