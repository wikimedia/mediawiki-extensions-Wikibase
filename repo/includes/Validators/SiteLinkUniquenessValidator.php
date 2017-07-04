<?php

namespace Wikibase\Repo\Validators;

use ValueValidators\Error;
use ValueValidators\Result;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\SiteLink;
use Wikibase\Repo\Store\SiteLinkConflictLookup;

/**
 * Validator for checking that site links are unique across all Items.
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class SiteLinkUniquenessValidator implements EntityValidator {

	/**
	 * @var SiteLinkConflictLookup
	 */
	private $siteLinkConflictLookup;

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
		$errors = [];

		if ( $entity instanceof Item ) {
			// TODO: do not use global state
			$db = wfGetDB( DB_MASTER );

			$conflicts = $this->siteLinkConflictLookup->getConflictsForItem( $entity, $db );

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
		return new UniquenessViolation(
			$conflict['itemId'],
			'SiteLink conflict',
			'sitelink-conflict',
			[
				new SiteLink( $conflict['siteId'], $conflict['sitePage'] ),
				$conflict['itemId'],
			]
		);
	}

}
