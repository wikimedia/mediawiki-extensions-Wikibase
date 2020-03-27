<?php

namespace Wikibase\Client\DataAccess;

use Language;
use MessageLocalizer;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Wikibase\Client\Usage\UsageAccumulator;
use Wikibase\Lib\Formatters\Reference\DataBridgeReferenceFormatter;
use Wikibase\Lib\Formatters\Reference\ReferenceFormatter;
use Wikibase\Lib\Formatters\Reference\WellKnownReferenceProperties;

/**
 * A factory for {@link ReferenceFormatter}s.
 *
 * @license GPL-2.0-or-later
 */
class ReferenceFormatterFactory {

	/** @var DataAccessSnakFormatterFactory */
	private $snakFormatterFactory;

	/** @var WellKnownReferenceProperties */
	private $properties;

	/** @var LoggerInterface */
	private $logger;

	public function __construct(
		DataAccessSnakFormatterFactory $snakFormatterFactory,
		WellKnownReferenceProperties $properties,
		LoggerInterface $logger = null
	) {
		$this->snakFormatterFactory = $snakFormatterFactory;
		$this->properties = $properties;
		$this->logger = $logger ?: new NullLogger();
	}

	public function newDataBridgeReferenceFormatter(
		MessageLocalizer $messageLocalizer,
		Language $language,
		UsageAccumulator $usageAccumulator
	): ReferenceFormatter {
		$this->logIfPropertiesEmpty();
		return new DataBridgeReferenceFormatter(
			$this->snakFormatterFactory->newWikitextSnakFormatter(
				$language,
				$usageAccumulator,
				DataAccessSnakFormatterFactory::TYPE_RICH_WIKITEXT
			),
			$this->properties,
			$messageLocalizer
		);
	}

	private function logIfPropertiesEmpty(): void {
		if ( $this->properties->isEmpty() ) {
			$this->logger->info(
				__METHOD__ . ': ' .
				'The well-known reference properties are being used to create a reference formatter, ' .
				'but they are completely unconfigured. ' .
				'The reference formatter will produce better results if you configure it, ' .
				'usually in a Wikibase Client setting called wellKnownReferencePropertyIds. ' .
				'See the Wikibase options documentation for details.'
			);
		}
	}

}
