<?php

namespace Wikibase\Validators;

use ValueValidators\Error;
use ValueValidators\Result;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;
use Wikibase\SiteLinkLookup;

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
	 * @param EntityId $ignoreConflictsWith
	 *
	 * @return Result
	 */
	public function validateEntity( Entity $entity, EntityId $ignoreConflictsWith = null ) {
		wfProfileIn( __METHOD__ );
		$result = Result::newSuccess();
		$dbw = wfGetDB( DB_MASTER );

		$conflicts = $this->siteLinkLookup->getConflictsForItem( $entity, $dbw );

		/* @var ItemId $ignoreConflictsWith */
		foreach ( $conflicts as $conflict ) {
			if ( $ignoreConflictsWith && $conflict['itemId']
				=== $ignoreConflictsWith->getNumericId() ) {

				continue;
			}

			$error = $this->getConflictError( $conflict );

			$result = Result::newError( array_merge(
				$result->getErrors(),
				array( $error )
			) );
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

		return Error::newError(
			'SiteLink conflict',
			'sitelink',
			'sitelink-conflict',
			array(
				new SiteLink( $conflict['siteId'], $conflict['sitePage'] ),
				$entityId,
			)
		);
	}

}