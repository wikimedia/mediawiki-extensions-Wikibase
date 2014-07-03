<?php

namespace Wikibase\Lib\Reporting;
/**
 * Mock implementation of the MessageReporter interface that
 * does nothing with messages it receives.
 *
 * @since 1.21
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class NullMessageReporter implements \Wikibase\Lib\Reporting\MessageReporter {

	/**
	 * @see MessageReporter::reportMessage
	 *
	 * @since 1.21
	 *
	 * @param string $message
	 */
	public function reportMessage( $message ) {
		// no-op
	}
}
