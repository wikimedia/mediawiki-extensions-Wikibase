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

		//TODO: Make validators configurable. Allow more types to register.
	}

	/**
	 * Returns validators for hard global constraints that should be enforced on every update
	 * of an entity of the given type (including creation).
	 *
	 * @param string $entityType
	 *
	 * @return EntityValidator[]
	 */
	public function getUpdateValidators( $entityType ) {
		$validators = array();

		switch ( $entityType ) {
			case Property::ENTITY_TYPE:
				$validators[] = new LabelUniquenessValidator( $this->duplicateDetector );
				break;

			case Item::ENTITY_TYPE:
				$validators[] = new SiteLinkUniquenessValidator( $this->siteLinkLookup );
				break;
		}

		return $validators;
	}

	/**
	 * Returns validators for soft global constraints that should be enforced only
	 * upon creation of an entity of the given type. This will include at least the
	 * validators returned by getUpdateValidators() for that type.
	 *
	 * @note: During updates, such soft constraints should be checked selectively by the
	 * respective ChangeOps, so not all such (potentially expensive) validators are applied
	 * for all updates.
	 *
	 * @param string $entityType
	 *
	 * @return EntityValidator[]
	 */
	public function getCreationValidators( $entityType ) {
		$validators = $this->getUpdateValidators( $entityType );

		switch ( $entityType ) {
			case Property::ENTITY_TYPE:
				break;

			case Item::ENTITY_TYPE:
				$validators[] = new LabelDescriptionUniquenessValidator( $this->duplicateDetector );
				break;
		}

		return $validators;
	}

}
