<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\ParserOutput;

use InvalidArgumentException;
use MediaWiki\Parser\ParserOutput;
use Wikibase\Lib\Store\EntityRevision;
use Wikimedia\Stats\StatsFactory;

/**
 * @license GPL-2.0-or-later
 */
class StatslibTimeRecordingEntityParserOutputGenerator implements EntityParserOutputGenerator {

	/**
	 * @var EntityParserOutputGenerator
	 */
	private $inner;

	/**
	 * @var StatsFactory
	 */
	private $statsFactory;

	/**
	 * @var string
	 */
	private $statsdTimingPrefix;

	/**
	 * @var string
	 */
	private $statsTimingPrefix;

	/**
	 * @param EntityParserOutputGenerator $inner
	 * @param StatsFactory $statsFactory
	 * @param string $statsdTimingPrefix Resulting metric will be: $timingPrefix.getParserOutput.<html/nohtml>><entitytype>
	 * @param string $statsTimingPrefix
	 */
	public function __construct(
		EntityParserOutputGenerator $inner,
		StatsFactory $statsFactory,
		string $statsdTimingPrefix,
		string $statsTimingPrefix
	) {
		$this->inner = $inner;
		$this->statsFactory = $statsFactory->withComponent( 'WikibaseRepo' );
		$this->statsdTimingPrefix = $statsdTimingPrefix;
		$this->statsTimingPrefix = $statsTimingPrefix;
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
		$htmlMetricPart = $generateHtml ? 'html' : 'nohtml';
		$entityRevisionEntityType = $entityRevision->getEntity()->getType();
		$timing = $this->statsFactory
			->getTiming(
				"{$this->statsTimingPrefix}_getParserOutput_duration_seconds"
			)
			->setLabels( [ 'html_metric' => $htmlMetricPart, 'entity_revision_entity_type' => $entityRevisionEntityType ] )
			->copyToStatsdAt(
				"{$this->statsdTimingPrefix}.getParserOutput.{$htmlMetricPart}.{$entityRevisionEntityType}"
			);

		$timing->start();
		$po = $this->inner->getParserOutput( $entityRevision, $generateHtml );
		$timing->stop();

		return $po;
	}

}
