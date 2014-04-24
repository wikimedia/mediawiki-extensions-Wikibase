<?php

namespace Wikibase\Validators;

use SiteStore;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\EntityTitleLookup;
use Wikibase\LabelDescriptionDuplicateDetector;
use Wikibase\SiteLinkLookup;

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
	private $termDuplicateDetector;

	/**
	 * @var SiteLinkLookup
	 */
	private $siteLinkLookup;

	/**
	 * @var EntityTitleLookup
	 */
	private $titleLookup;

	/**
	 * @var SiteStore
	 */
	private $sites;

	/**
	 * @param LabelDescriptionDuplicateDetector $termDuplicateDetector
	 * @param EntityTitleLookup $titleLookup
	 * @param SiteLinkLookup $siteLinkLookup
	 * @param SiteStore $sites
	 */
	function __construct(
		LabelDescriptionDuplicateDetector $termDuplicateDetector,
		EntityTitleLookup $titleLookup,
		SiteLinkLookup $siteLinkLookup,
		SiteStore $sites
	) {
		$this->termDuplicateDetector = $termDuplicateDetector;
		$this->titleLookup = $titleLookup;
		$this->siteLinkLookup = $siteLinkLookup;
		$this->sites = $sites;
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
				$validators[] = new LabelUniquenessValidator( $this->termDuplicateDetector );
				break;

			case Item::ENTITY_TYPE:
				$validators[] = new SiteLinkUniquenessValidator(
					$this->titleLookup,
					$this->siteLinkLookup,
					$this->sites
				);
				break;
		}

		return count( $validators ) === 1
			? $validators[0]
			: new CompositeEntityValidator( $validators );
	}

}
 