<?php

namespace Wikibase\Lib\Normalization;

use DataValues\DataValue;
use DataValues\StringValue;
use Psr\Log\LoggerInterface;
use Wikibase\Lib\StringNormalizer;

/**
 * @license GPL-2.0-or-later
 */
class StringValueNormalizer implements DataValueNormalizer {

	/** @var StringNormalizer */
	private $stringNormalizer;

	/** @var LoggerInterface */
	private $logger;

	public function __construct(
		StringNormalizer $stringNormalizer,
		LoggerInterface $logger
	) {
		$this->stringNormalizer = $stringNormalizer;
		$this->logger = $logger;
	}

	public function normalize( DataValue $value ): DataValue {
		if ( !( $value instanceof StringValue ) ) {
			$this->logger->info(
				__METHOD__ . ': cannot normalize non-string value {value}',
				[
					'value' => $value->getArrayValue(),
				]
			);
			return $value;
		}
		return new StringValue( $this->stringNormalizer->cleanupToNFC( $value->getValue() ) );
	}

}
