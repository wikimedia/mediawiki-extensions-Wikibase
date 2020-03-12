<?php

namespace Wikibase\Lib\Formatters\Reference;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\Snak;

/**
 * A list of snaks, grouped by a certain set of properties,
 * with all snaks of other properties in one “other” group.
 *
 * For convenience, properties can be specified as null (not known).
 * Snaks are never grouped by such properties,
 * and getting the snaks for them simply returns an empty array.
 *
 * @license GPL-2.0-or-later
 */
class ByCertainPropertyIdGrouper {

	/** @var Snak[][] (property ID serializations as keys) */
	private $groups = [];
	/** @var Snak[] */
	private $otherGroup = [];
	/** @var bool whether any of the certain property IDs was null */
	private $hasUnknownPropertyId = false;

	/**
	 * @param Snak[]|iterable<Snak> $snaks
	 * @param (PropertyId|null)[] $certainPropertyIds
	 */
	public function __construct( iterable $snaks, array $certainPropertyIds ) {
		foreach ( $certainPropertyIds as $certainPropertyId ) {
			/** @var PropertyId $certainPropertyId */
			'@phan-var PropertyId $certainPropertyId';
			if ( $certainPropertyId !== null ) {
				$this->groups[$certainPropertyId->getSerialization()] = [];
			} else {
				$this->hasUnknownPropertyId = true;
			}
		}

		foreach ( $snaks as $snak ) {
			/** @var Snak $snak */
			'@phan-var Snak $snak';
			$propertyId = $snak->getPropertyId();
			$isOtherSnak = true;
			foreach ( $certainPropertyIds as $certainPropertyId ) {
				/** @var PropertyId|null $certainPropertyId */
				'@phan-var PropertyId|null $certainPropertyId';
				if ( $propertyId->equals( $certainPropertyId ) ) {
					$this->groups[$propertyId->getSerialization()][] = $snak;
					$isOtherSnak = false;
					break;
				}
			}
			if ( $isOtherSnak ) {
				$this->otherGroup[] = $snak;
			}
		}
	}

	/**
	 * @throws InvalidArgumentException if the property ID is not one of the ones given in the constructor
	 * @return Snak[]
	 */
	public function getByPropertyId( ?PropertyId $certainPropertyId ): array {
		if ( $certainPropertyId === null ) {
			if ( $this->hasUnknownPropertyId ) {
				return [];
			} else {
				throw new InvalidArgumentException( 'None of the certain porperty IDs were unknown (null)' );
			}
		}

		$serialization = $certainPropertyId->getSerialization();
		if ( array_key_exists( $serialization, $this->groups ) ) {
			return $this->groups[$serialization];
		} else {
			throw new InvalidArgumentException( 'Not one of the certain property IDs: ' . $serialization );
		}
	}

	/** @return Snak[] */
	public function getOthers(): array {
		return $this->otherGroup;
	}

}
