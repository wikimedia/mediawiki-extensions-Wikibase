<?php

namespace Wikibase\Repo\Store\SQL;

use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Lookup\EntityRedirectLookup;
use Wikibase\DataModel\Services\Lookup\EntityRedirectLookupException;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Store\EntityIdLookup;

/**
 * @since 0.5
 *
 * @license GNU GPL v2+
 * @author Marius Hoch
 */
class WikiPageEntityRedirectLookup implements EntityRedirectLookup {

	/**
	 * @var EntityTitleLookup
	 */
	private $entityTitleLookup;

	/**
	 * @var EntityIdLookup
	 */
	private $entityIdLookup;

	/**
	 * @param EntityTitleLookup $entityTitleLookup
	 * @param EntityIdLookup $entityIdLookup
	 */
	public function __construct( EntityTitleLookup $entityTitleLookup, EntityIdLookup $entityIdLookup ) {
		$this->entityTitleLookup = $entityTitleLookup;
		$this->entityIdLookup = $entityIdLookup;
	}

	/**
	 * Returns the IDs that redirect to (are aliases of) the given target entity.
	 *
	 * @since 0.5
	 *
	 * @param EntityId $targetId
	 *
	 * @return EntityId[]
	 * @throws EntityRedirectLookupException
	 */
	public function getRedirectIds( EntityId $targetId ) {
		$title = $this->tryToGetTitleForId( $targetId );

		$dbr = wfGetDB( DB_SLAVE );

		$res = $dbr->select(
			array( 'page', 'redirect' ),
			array( 'page_namespace', 'page_title' ),
			array(
				'rd_title' => $title->getDBkey(),
				'rd_namespace' => $title->getNamespace(),
				'page_id = rd_from'
			),
			__METHOD__,
			array(
				'LIMIT' => 1000 // everything should have a hard limit
			)
		);

		if ( !$res ) {
			return array();
		}

		$ids = array();
		foreach ( $res as $row ) {
			$title = Title::makeTitle( $row->page_namespace, $row->page_title );

			$ids[] = $this->entityIdLookup->getEntityIdForTitle( $title );
		}

		return $ids;
	}

	private function tryToGetTitleForId( EntityId $entityId ) {
		try {
			return $this->entityTitleLookup->getTitleForId( $entityId );
		}
		catch ( \Exception $ex ) {
			// TODO: catch more specific exception as soon as getTitleForId stops throwing 3 types
			throw new EntityRedirectLookupException( $entityId, $ex->getMessage(), $ex );
		}
	}

	/**
	 * @see EntityRedirectLookup::getRedirectForEntityId
	 *
	 * @since 0.5
	 *
	 * @param EntityId $entityId
	 * @param string $forUpdate
	 *
	 * @return EntityId|null|false The ID of the redirect target, or null if $entityId
	 *         does not refer to a redirect, or false if $entityId is not known.
	 */
	public function getRedirectForEntityId( EntityId $entityId, $forUpdate = '' ) {
		$title = $this->tryToGetTitleForId( $entityId );

		$dbr = wfGetDB(
			$forUpdate === 'for update' ? DB_MASTER : DB_SLAVE
		);

		$row = $dbr->selectRow(
			array( 'page', 'redirect' ),
			array( 'page_id', 'rd_namespace', 'rd_title' ),
			array(
				'page_title' => $title->getDBkey(),
				'page_namespace' => $title->getNamespace()
			),
			__METHOD__,
			array(),
			array(
				'redirect' => array( 'LEFT JOIN', 'rd_from=page_id' )
			)
		);

		if ( !$row ) {
			throw new EntityRedirectLookupException(
				$entityId,
				'Cannot find the redirect target of a non existing entity'
			);
		}

		if ( !$row->rd_namespace ) {
			return null;
		}

		$title = Title::makeTitle( $row->rd_namespace, $row->rd_title );

		return $this->entityIdLookup->getEntityIdForTitle( $title );
	}

}
