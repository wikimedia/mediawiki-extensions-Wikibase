<?php

declare( strict_types = 1 );

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
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class SiteLinkUniquenessValidator implements EntityValidator {

	private SiteLinkConflictLookup $siteLinkConflictLookup;

	/** @var string[] */
	private array $redirectBadgeItems;

	public function __construct(
		SiteLinkConflictLookup $siteLinkConflictLookup,
		array $redirectBadgeItems
	) {
		$this->siteLinkConflictLookup = $siteLinkConflictLookup;
		$this->redirectBadgeItems = $redirectBadgeItems;
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
			$conflicts = $this->siteLinkConflictLookup->getConflictsForItem( $entity, DB_PRIMARY );

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
	 */
	private function getConflictError( array $conflict ): Error {
		$siteLink = new SiteLink( $conflict['siteId'], $conflict['sitePage'] );
		if ( $conflict['itemId'] !== null ) {
			$code = $this->redirectBadgeItems !== [] ?
				'sitelink-conflict-redirects-supported' :
				'sitelink-conflict';
			return new UniquenessViolation(
				$conflict['itemId'],
				'SiteLink conflict',
				$code,
				[ $siteLink, $conflict['itemId'] ]
			);
		} else {
			return new UniquenessViolation(
				null,
				'SiteLink conflict with unknown item',
				'sitelink-conflict-unknown',
				[ $siteLink ]
			);
		}
	}

}
