<?php

namespace Wikibase\content;

use Message;
use SiteStore;
use Status;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\EntityTitleLookup;
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
	 * @var SiteStore
	 */
	protected $siteStore;

	/**
	 * @var SiteLinkLookup
	 */
	protected $siteLinkLookup;

	/**
	 * @var EntityTitleLookup
	 */
	protected $entityTitleLookup;

	/**
	 * @param EntityTitleLookup $entityTitleLookup
	 * @param SiteLinkLookup $siteLinkLookup
	 * @param SiteStore $siteStore
	 */
	function __construct( EntityTitleLookup $entityTitleLookup, SiteLinkLookup $siteLinkLookup, SiteStore $siteStore ) {
		$this->entityTitleLookup = $entityTitleLookup;
		$this->siteLinkLookup = $siteLinkLookup;
		$this->siteStore = $siteStore;
	}

	/**
	 * @see OnSaveValidator::validate()
	 *
	 * @param Entity $entity
	 *
	 * @return Status
	 */
	public function validateEntity( Entity $entity ) {
		wfProfileIn( __METHOD__ );
		$status = Status::newGood();
		$dbw = wfGetDB( DB_MASTER );

		$conflicts = $this->siteLinkLookup->getConflictsForItem( $entity, $dbw );

		foreach ( $conflicts as $conflict ) {
			$msg = $this->getConflictMessage( $conflict );

			$status->fatal( $msg );
		}

		wfProfileOut( __METHOD__ );
		return $status;
	}

	/**
	 * Get Message for a conflict
	 *
	 * @param array $conflict
	 *
	 * @return \Message
	 */
	protected function getConflictMessage( array $conflict ) {
		$entityId = ItemId::newFromNumber( $conflict['itemId'] );
		$conflictingTitle = $this->entityTitleLookup->getTitleForId( $entityId );

		$site = $this->siteStore->getSite( $conflict['siteId'] );
		$pageUrl = $site->getPageUrl( $conflict['sitePage'] );

		// $pageUrl shouldn't be a raw param (it's causing the link not to be parsed)
		return new Message(
			'wikibase-error-sitelink-already-used',
			array(
				$pageUrl,
				$conflict['sitePage'],
				$conflictingTitle->getFullText(),
				$conflict['siteId'],
			)
		);
	}

}