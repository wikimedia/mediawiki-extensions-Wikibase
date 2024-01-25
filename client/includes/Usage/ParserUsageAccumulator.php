<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Usage;

use MediaWiki\Parser\ParserOutput;
use Parser;

/**
 * Parent class for UsageAccumulators that track their usage
 * to ParserOutput objects.
 *
 * @license GPL-2.0-or-later
 * @author Arthur Taylor
 */
abstract class ParserUsageAccumulator extends UsageAccumulator {

	protected ParserOutput $parserOutput;

	public function __construct( ParserOutput $parserOutput ) {
		$this->parserOutput = $parserOutput;
	}

	public function hasStoredReferenceToDifferentParse( Parser $parser ): bool {
		return $parser->getOutput() !== $this->parserOutput;
	}

	public function getParserOutput(): ParserOutput {
		return $this->parserOutput;
	}

}
