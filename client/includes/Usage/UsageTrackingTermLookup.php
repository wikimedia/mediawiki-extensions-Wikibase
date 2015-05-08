<?php

namespace Wikibase\Client\Usage;

use OutOfBoundsException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\Store\TermLookup;

/**
 * TermLookup decorator that records label usage in an TermLookup.
 *
 * @see UsageAccumulator
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
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

	/**
	 * @param TermLookup $termLookup
	 * @param UsageAccumulator $usageAccumulator
	 */
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
	 * @throws OutOfBoundsException if no label in that language is known
	 * @return string
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
	 * @throws OutOfBoundsException
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
	 * @throws OutOfBoundsException if no description in that language is known
	 * @return string
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
	 * @throws OutOfBoundsException
	 * @return string[]
	 */
	public function getDescriptions( EntityId $entityId, array $languages ) {
		return $this->termLookup->getDescriptions( $entityId, $languages );
	}

}
