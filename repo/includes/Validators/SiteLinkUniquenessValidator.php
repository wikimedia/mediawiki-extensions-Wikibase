<?php

namespace Wikibase\Validators;

use ValueValidators\Error;
use ValueValidators\Result;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;
use Wikibase\Lib\Store\SiteLinkLookup;

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
	 * @var SiteLinkLookup
	 */
	private $siteLinkLookup;

	/**
	 * @param SiteLinkLookup $siteLinkLookup
	 */
	function __construct( SiteLinkLookup $siteLinkLookup ) {
		$this->siteLinkLookup = $siteLinkLookup;
	}

	/**
	 * @see EntityValidator::validate()
	 *
	 * @param Entity $entity
	 *
	 * @return Result
	 */
	public function validateEntity( Entity $entity ) {
		wfProfileIn( __METHOD__ );
		$dbw = wfGetDB( DB_MASTER );

		$conflicts = $this->siteLinkLookup->getConflictsForItem( $entity, $dbw );
		$errors = array();

		/* @var ItemId $ignoreConflictsWith */
		foreach ( $conflicts as $conflict ) {
			$errors[] = $this->getConflictError( $conflict );
		}

		if ( empty( $errors ) ) {
			$result = Result::newSuccess();
		} else {
			$result = Result::newError( $errors );
		}

		wfProfileOut( __METHOD__ );
		return $result;
	}

	/**
	 * Get Message for a conflict
	 *
	 * @param array $conflict A record as returned by SiteLinkLookup::getConflictsForItem()
	 *
	 * @return Error
	 */
	protected function getConflictError( array $conflict ) {
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