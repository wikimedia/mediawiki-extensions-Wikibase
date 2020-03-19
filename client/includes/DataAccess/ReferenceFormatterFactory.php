<?php

namespace Wikibase\Client\DataAccess;

use Language;
use MessageLocalizer;
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

	public function __construct(
		DataAccessSnakFormatterFactory $snakFormatterFactory,
		WellKnownReferenceProperties $properties
	) {
		$this->snakFormatterFactory = $snakFormatterFactory;
		$this->properties = $properties;
	}

	public function newDataBridgeReferenceFormatter(
		MessageLocalizer $messageLocalizer,
		Language $language,
		UsageAccumulator $usageAccumulator
	): ReferenceFormatter {
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

}
