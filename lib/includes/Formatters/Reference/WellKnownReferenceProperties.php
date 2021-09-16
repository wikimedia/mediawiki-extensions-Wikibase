<?php

namespace Wikibase\Lib\Formatters\Reference;

use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Wikibase\DataModel\Entity\NumericPropertyId;

/**
 * A container for a set of property IDs for certain well-known roles.
 *
 * To split a list of reference snaks by these properties,
 * {@link ByWellKnownPropertiesGroupedSnaks} can be used.
 *
 * Instances of this class should be considered immutable;
 * its fields are directly public for convenience, but should not be modified.
 *
 * @license GPL-2.0-or-later
 */
class WellKnownReferenceProperties {

	/** @var NumericPropertyId|null */
	public $referenceUrlPropertyId;
	/** @var NumericPropertyId|null */
	public $titlePropertyId;
	/** @var NumericPropertyId|null */
	public $statedInPropertyId;
	/** @var NumericPropertyId|null */
	public $authorPropertyId;
	/** @var NumericPropertyId|null */
	public $publisherPropertyId;
	/** @var NumericPropertyId|null */
	public $publicationDatePropertyId;
	/** @var NumericPropertyId|null */
	public $retrievedDatePropertyId;

	private function __construct(
		?NumericPropertyId $referenceUrlPropertyId,
		?NumericPropertyId $titlePropertyId,
		?NumericPropertyId $statedInPropertyId,
		?NumericPropertyId $authorPropertyId,
		?NumericPropertyId $publisherPropertyId,
		?NumericPropertyId $publicationDatePropertyId,
		?NumericPropertyId $retrievedDatePropertyId
	) {
		$this->referenceUrlPropertyId = $referenceUrlPropertyId;
		$this->titlePropertyId = $titlePropertyId;
		$this->statedInPropertyId = $statedInPropertyId;
		$this->authorPropertyId = $authorPropertyId;
		$this->publisherPropertyId = $publisherPropertyId;
		$this->publicationDatePropertyId = $publicationDatePropertyId;
		$this->retrievedDatePropertyId = $retrievedDatePropertyId;
	}

	/**
	 * Convenience function to determine
	 * whether the properties are completely empty (unconfigured).
	 */
	public function isEmpty(): bool {
		return $this->referenceUrlPropertyId === null &&
			$this->titlePropertyId === null &&
			$this->statedInPropertyId === null &&
			$this->authorPropertyId === null &&
			$this->publisherPropertyId === null &&
			$this->publicationDatePropertyId === null &&
			$this->retrievedDatePropertyId === null;
	}

	/**
	 * Parse well-known reference properties from an array containing their serializations under the following keys:
	 * - referenceUrl
	 * - title
	 * - statedin
	 * - author
	 * - publisher
	 * - publicationDate
	 * - retrievedDate
	 *
	 * @param string[] $wellKnownPropertyIds The property ID serializations.
	 * @param LoggerInterface|null $logger Used to warn on unknown array keys and error on invalid values.
	 * @return WellKnownReferenceProperties
	 */
	public static function newFromArray( array $wellKnownPropertyIds, ?LoggerInterface $logger = null ): self {
		$logger = $logger ?: new NullLogger();

		$referenceUrlPropertyId = self::parseWellKnownProperty( $wellKnownPropertyIds, 'referenceUrl', $logger );
		$titlePropertyId = self::parseWellKnownProperty( $wellKnownPropertyIds, 'title', $logger );
		$statedInPropertyId = self::parseWellKnownProperty( $wellKnownPropertyIds, 'statedIn', $logger );
		$authorPropertyId = self::parseWellKnownProperty( $wellKnownPropertyIds, 'author', $logger );
		$publisherPropertyId = self::parseWellKnownProperty( $wellKnownPropertyIds, 'publisher', $logger );
		$publicationDatePropertyId = self::parseWellKnownProperty( $wellKnownPropertyIds, 'publicationDate', $logger );
		$retrievedDatePropertyId = self::parseWellKnownProperty( $wellKnownPropertyIds, 'retrievedDate', $logger );

		$extraKeys = array_diff( array_keys( $wellKnownPropertyIds ), [
			'referenceUrl',
			'title',
			'statedIn',
			'author',
			'publisher',
			'publicationDate',
			'retrievedDate',
		] );
		if ( $extraKeys !== [] ) {
			$logger->warning(
				'unknown well-known reference properties: {wellKnownNames}',
				[
					'wellKnownNames' => $extraKeys,
				]
			);
		}

		return new self(
			$referenceUrlPropertyId,
			$titlePropertyId,
			$statedInPropertyId,
			$authorPropertyId,
			$publisherPropertyId,
			$publicationDatePropertyId,
			$retrievedDatePropertyId
		);
	}

	private static function parseWellKnownProperty(
		array $wellKnownPropertyIds,
		string $wellKnownName,
		LoggerInterface $logger
	): ?NumericPropertyId {
		if ( array_key_exists( $wellKnownName, $wellKnownPropertyIds ) ) {
			$value = $wellKnownPropertyIds[$wellKnownName];
			if ( $value === null ) {
				return null;
			}
			try {
				return new NumericPropertyId( $value );
			} catch ( InvalidArgumentException $e ) {
				$logger->error(
					'cannot parse value {value} for well-known reference property {wellKnownName}: {exception}',
					[
						'value' => $value,
						'wellKnownName' => $wellKnownName,
						'exception' => $e,
					]
				);
			}
		} else {
			$logger->info(
				'no value specified for well-known reference property {wellKnownName} (set to null to disable this message)',
				[
					'wellKnownName' => $wellKnownName,
				]
			);
		}
		return null;
	}

}
