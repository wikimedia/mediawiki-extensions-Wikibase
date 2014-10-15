<?php

namespace Wikibase\Validators;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\LabelDescriptionDuplicateDetector;
use Wikibase\Lib\Store\SiteLinkLookup;

/**
 * Provides constraints for each entity type.
 * Used to enforce global constraints upon save.
 *
 * @see docs/constraints.wiki
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class EntityConstraintProvider {

	/**
	 * @var LabelDescriptionDuplicateDetector
	 */
	private $duplicateDetector;

	/**
	 * @var SiteLinkLookup
	 */
	private $siteLinkLookup;

	/**
	 * @param LabelDescriptionDuplicateDetector $duplicateDetector
	 * @param SiteLinkLookup $siteLinkLookup
	 */
	public function __construct(
		LabelDescriptionDuplicateDetector $duplicateDetector,
		SiteLinkLookup $siteLinkLookup
	) {
		$this->duplicateDetector = $duplicateDetector;
		$this->siteLinkLookup = $siteLinkLookup;
	}

	/**
	 * Returns a validator for enforcing the appropriate constraints on the given type of entity.
	 *
	 * @param string $entityType
	 *
	 * @return EntityValidator
	 */
	public function getConstraints( $entityType ) {
		$validators = array();

		//TODO: Make this configurable. Use a builder. Allow more types to register.

		switch ( $entityType ) {
			case Property::ENTITY_TYPE:
				$validators[] = new LabelUniquenessValidator( $this->duplicateDetector );
				break;

			case Item::ENTITY_TYPE:
				$validators[] = new LabelDescriptionUniquenessValidator( $this->duplicateDetector );
				$validators[] = new SiteLinkUniquenessValidator( $this->siteLinkLookup );
				break;
		}

		return count( $validators ) === 1
			? $validators[0]
			: new CompositeEntityValidator( $validators );
	}

}
