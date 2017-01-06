<?php

namespace Wikibase\Client\Usage;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Lookup\TermLookup;
use Wikibase\DataModel\Services\Lookup\TermLookupException;

/**
 * TermLookup decorator that records label usage in an TermLookup.
 *
 * @see UsageAccumulator
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class UsageTrackingTermLookup implements TermLookup {

	/**
	 * @var TermLookup
	 */
	private $termLookup;

	/**
	 * @var UsageAccumulator
	 */
	private $usageAccumulator;

	public function __construct( TermLookup $termLookup, UsageAccumulator $usageAccumulator ) {
		$this->termLookup = $termLookup;
		$this->usageAccumulator = $usageAccumulator;
	}

	/**
	 * Gets the label of an Entity with the specified EntityId and language code.
	 *
	 * @param EntityId $entityId
	 * @param string $languageCode
	 *
	 * @throws TermLookupException
	 * @return string|null
	 */
	public function getLabel( EntityId $entityId, $languageCode ) {
		$this->usageAccumulator->addLabelUsage( $entityId, $languageCode );
		return $this->termLookup->getLabel( $entityId, $languageCode );
	}

	/**
	 * Gets all labels of an Entity with the specified EntityId.
	 *
	 * @param EntityId $entityId
	 * @param string[] $languages
	 *
	 * @throws TermLookupException
	 * @return string[]
	 */
	public function getLabels( EntityId $entityId, array $languages ) {
		foreach ( $languages as $lang ) {
			$this->usageAccumulator->addLabelUsage( $entityId, $lang );
		}

		return $this->termLookup->getLabels( $entityId, $languages );
	}

	/**
	 * Gets the description of an Entity with the specified EntityId and language code.
	 *
	 * @param EntityId $entityId
	 * @param string $languageCode
	 *
	 * @throws TermLookupException
	 * @return string|null
	 */
	public function getDescription( EntityId $entityId, $languageCode ) {
		return $this->termLookup->getDescription( $entityId, $languageCode );
	}

	/**
	 * Gets all descriptions of an Entity with the specified EntityId.
	 *
	 * @param EntityId $entityId
	 * @param string[] $languages
	 *
	 * @throws TermLookupException
	 * @return string[]
	 */
	public function getDescriptions( EntityId $entityId, array $languages ) {
		return $this->termLookup->getDescriptions( $entityId, $languages );
	}

}
