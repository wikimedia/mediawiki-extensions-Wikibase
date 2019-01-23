<?php

namespace Wikibase\Lib\Formatters;

use Liuggio\StatsdClient\Factory\StatsdDataFactoryInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikimedia\Assert\Assert;

/**
 * Wrapper class giving ability to replace EntityIdFormatter in production in a controlled manner.
 *
 * @license GPL-2.0-or-later
 */
class ControlledFallbackEntityIdFormatter implements EntityIdFormatter {

	use LoggerAwareTrait;

	/**
	 * @var EntityIdFormatter
	 */
	private $targetFormatter;

	/**
	 * @var EntityIdFormatter
	 */
	private $fallbackFormatter;

	/**
	 * @var StatsdDataFactoryInterface
	 */
	private $statsdDataFactory;

	/**
	 * @var string
	 */
	private $statsPrefix;

	/**
	 * @param EntityIdFormatter $targetFormatter
	 * @param EntityIdFormatter $fallbackFormatter
	 * @param StatsdDataFactoryInterface $statsdDataFactory
	 * @param string $statsPrefix
	 */
	public function __construct(
		EntityIdFormatter $targetFormatter,
		EntityIdFormatter $fallbackFormatter,
		StatsdDataFactoryInterface $statsdDataFactory,
		$statsPrefix
	) {
		Assert::parameterType( 'string', $statsPrefix, '$statsPrefix' );

		$this->targetFormatter = $targetFormatter;
		$this->fallbackFormatter = $fallbackFormatter;
		$this->logger = new NullLogger();
		$this->statsdDataFactory = $statsdDataFactory;
		$this->statsPrefix = $statsPrefix;
	}

	public function formatEntityId( EntityId $value ) {
		static $previousTargetValues = [];

		try {
			$formatEntityId = $this->targetFormatter->formatEntityId( $value );

			if ( !array_key_exists( $value->getSerialization(), $previousTargetValues ) ) {
				$previousTargetValues[$value->getSerialization()] = true;
				$this->statsdDataFactory->increment( $this->statsPrefix . 'targetFormatterCalledUnique' );
			}
			$this->statsdDataFactory->increment( $this->statsPrefix . 'targetFormatterCalled' );

			return $formatEntityId;
		} catch ( \Exception $e ) { //TODO: Catch Throwable once we move to php7
			$this->logTargetFormatterFailure( $value, $e );

			return $this->formatUsingFallbackFormatter( $value );
		}
	}

	/**
	 * @param EntityId $value
	 * @return string
	 */
	private function formatUsingFallbackFormatter( EntityId $value ) {
		$formatEntityId = $this->fallbackFormatter->formatEntityId( $value );
		$this->statsdDataFactory->increment( $this->statsPrefix . 'fallbackFormatterCalled' );
		return $formatEntityId;
	}

	/**
	 * @param EntityId $value
	 * @param \Exception $e
	 */
	private function logTargetFormatterFailure( EntityId $value, \Exception $e ) {
		$this->logger->error(
			'Failed to format entity ID. Using fallback formatter.'
			. ' Error: {exception_message}',
			[
				'entityId' => $value->getSerialization(),
				'exception' => $e,
			]
		);
		$this->statsdDataFactory->increment( $this->statsPrefix . 'targetFormatterFailed' );
	}

}
