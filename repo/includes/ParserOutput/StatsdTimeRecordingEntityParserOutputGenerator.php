<?php

namespace Wikibase\Repo\ParserOutput;

use InvalidArgumentException;
use Liuggio\StatsdClient\Factory\StatsdDataFactoryInterface;
use ParserOutput;
use Wikibase\Lib\Store\EntityRevision;

/**
 * @license GPL-2.0-or-later
 */
class StatsdTimeRecordingEntityParserOutputGenerator implements EntityParserOutputGenerator {

	private $inner;
	private $stats;
	private $timingPrefix;

	/**
	 * @param EntityParserOutputGenerator $inner
	 * @param StatsdDataFactoryInterface $stats
	 * @param string $timingPrefix Resulting metric will be: $timingPrefix.getParserOutput.<html/nohtml>><entitytype>
	 */
	public function __construct(
		EntityParserOutputGenerator $inner,
		StatsdDataFactoryInterface $stats,
		$timingPrefix
	) {
		$this->inner = $inner;
		$this->stats = $stats;
		$this->timingPrefix = $timingPrefix;
	}

	/**
	 * Creates the parser output for the given entity.
	 *
	 * @param EntityRevision $entityRevision
	 * @param bool $generateHtml
	 *
	 * @throws InvalidArgumentException
	 * @return ParserOutput
	 */
	public function getParserOutput(
		EntityRevision $entityRevision,
		$generateHtml = true
	) {
		$start = microtime( true );
		$po = $this->inner->getParserOutput( $entityRevision, $generateHtml );
		$end = microtime( true );

		$htmlMetricPart = $generateHtml ? 'html' : 'nohtml';
		$this->stats->timing(
			"{$this->timingPrefix}.getParserOutput.{$htmlMetricPart}.{$entityRevision->getEntity()->getType()}",
			( $end - $start ) * 1000
		);

		return $po;
	}

}
