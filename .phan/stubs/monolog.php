<?php

/**
 * Minimal set of classes necessary to fulfill needs of parts of Wikibase relying on
 * Monolog.
 */

namespace Monolog\Processor{
	class PsrLogMessageProcessor{
		/**
		 * @param  array $record
		 * @return array
		 */
		public function __invoke(array $record) : array {
		}
	}
}
