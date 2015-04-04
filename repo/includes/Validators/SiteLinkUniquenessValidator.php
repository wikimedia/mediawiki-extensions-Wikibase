<?php

namespace Wikibase\Validators;

use ValueValidators\Error;
use ValueValidators\Result;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;
use Wikibase\Lib\Store\SiteLinkConflictLookup;

/**
 * Validator for checking that site links are unique across all Items.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class SiteLinkUniquenessValidator implements EntityValidator {

	/**
	 * @var SiteLinkConflictLookup
	 */
	private $siteLinkConflictLookup;

	/**
	 * @param SiteLinkConflictLookup $siteLinkConflictLookup
	 */
	public function __construct( SiteLinkConflictLookup $siteLinkConflictLookup ) {
		$this->siteLinkConflictLookup = $siteLinkConflictLookup;
	}

	/**
	 * @see EntityValidator::validate()
	 *
	 * @param EntityDocument $entity
	 *
	 * @return Result
	 */
	public function validateEntity( EntityDocument $entity ) {
		$errors = array();

		if ( $entity instanceof Item ) {
			// TODO: do not use global state
			$db = wfGetDB( DB_MASTER );

			$conflicts = $this->siteLinkConflictLookup->getConflictsForItem( $entity, $db );

			/* @var ItemId $ignoreConflictsWith */
			foreach ( $conflicts as $conflict ) {
				$errors[] = $this->getConflictError( $conflict );
			}
		}

		return empty( $errors ) ? Result::newSuccess() : Result::newError( $errors );
	}

	/**
	 * Get Message for a conflict
	 *
	 * @param array $conflict A record as returned by SiteLinkConflictLookup::getConflictsForItem()
	 *
	 * @return Error
	 */
	private function getConflictError( array $conflict ) {
		$entityId = ItemId::newFromNumber( $conflict['itemId'] );

		return new UniquenessViolation(
			$entityId,
			'SiteLink conflict',
			'sitelink-conflict',
			array(
				new SiteLink( $conflict['siteId'], $conflict['sitePage'] ),
				$entityId,
			)
		);
	}

}
