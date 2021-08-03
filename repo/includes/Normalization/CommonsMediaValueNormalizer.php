<?php

namespace Wikibase\Repo\Normalization;

use DataValues\DataValue;
use DataValues\StringValue;
use Psr\Log\LoggerInterface;
use Wikibase\Lib\Normalization\DataValueNormalizer;
use Wikibase\Repo\CachingCommonsMediaFileNameLookup;

/**
 * @license GPL-2.0-or-later
 */
class CommonsMediaValueNormalizer implements DataValueNormalizer {

	/** @var CachingCommonsMediaFileNameLookup */
	private $fileNameLookup;

	/** @var LoggerInterface */
	private $logger;

	public function __construct(
		CachingCommonsMediaFileNameLookup $fileNameLookup,
		LoggerInterface $logger
	) {
		$this->fileNameLookup = $fileNameLookup;
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
		$fileName = $value->getValue();
		$normalized = $this->fileNameLookup->lookupFileName( $fileName );
		if ( $normalized !== null ) {
			return new StringValue( $normalized );
		} else {
			$this->logger->info(
				__METHOD__ . ': cannot normalize Commons file name {fileName}',
				[ 'fileName' => $fileName ]
			);
			return $value;
		}
	}

}
