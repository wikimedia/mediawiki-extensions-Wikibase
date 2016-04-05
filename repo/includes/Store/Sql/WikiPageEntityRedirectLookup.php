<?php

namespace Wikibase\Repo\Store\SQL;

use LoadBalancer;
use MWException;
use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Lookup\EntityRedirectLookup;
use Wikibase\DataModel\Services\Lookup\EntityRedirectLookupException;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Store\EntityIdLookup;

/**
 * @since 0.5
 *
 * @license GPL-2.0+
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
	 * @var LoadBalancer
	 */
	private $loadBalancer;

	/**
	 * @param EntityTitleLookup $entityTitleLookup
	 * @param EntityIdLookup $entityIdLookup
	 * @param LoadBalancer $loadBalancer
	 */
	public function __construct(
		EntityTitleLookup $entityTitleLookup,
		EntityIdLookup $entityIdLookup,
		LoadBalancer $loadBalancer
	) {
		$this->entityTitleLookup = $entityTitleLookup;
		$this->entityIdLookup = $entityIdLookup;
		$this->loadBalancer = $loadBalancer;
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
		try {
			$title = $this->entityTitleLookup->getTitleForId( $targetId );
		} catch ( \Exception $ex ) {
			// TODO: catch more specific type of exception once EntityTitleLookup contract is clarified
			throw new EntityRedirectLookupException( $targetId, null, $ex );
		}

		try {
			$dbr = $this->loadBalancer->getConnection( DB_SLAVE );
		} catch ( MWException $ex ) {
			throw new EntityRedirectLookupException( $targetId, null, $ex );
		}

		$res = $dbr->select(
			array( 'page', 'redirect' ),
			array( 'page_namespace', 'page_title' ),
			array(
				'rd_title' => $title->getDBkey(),
				'rd_namespace' => $title->getNamespace(),
				// Entity redirects are guaranteed to be in the same namespace
				'page_namespace' => $title->getNamespace(),
				'page_id = rd_from'
			),
			__METHOD__,
			array(
				'LIMIT' => 1000 // everything should have a hard limit
			)
		);

		try {
			$this->loadBalancer->reuseConnection( $dbr );
		} catch ( MWException $ex ) {
			throw new EntityRedirectLookupException( $targetId, null, $ex );
		}

		if ( !$res ) {
			return [];
		}

		$ids = [];
		foreach ( $res as $row ) {
			$title = Title::makeTitle( $row->page_namespace, $row->page_title );

			$ids[] = $this->entityIdLookup->getEntityIdForTitle( $title );
		}

		return $ids;
	}

	/**
	 * @see EntityRedirectLookup::getRedirectForEntityId
	 *
	 * @since 0.5
	 *
	 * @param EntityId $entityId
	 * @param string $forUpdate
	 *
	 * @return EntityId|null The ID of the redirect target, or null if $entityId
	 *         does not refer to a redirect
	 * @throws EntityRedirectLookupException
	 */
	public function getRedirectForEntityId( EntityId $entityId, $forUpdate = '' ) {
		try {
			$title = $this->entityTitleLookup->getTitleForId( $entityId );
		} catch ( \Exception $ex ) {
			// TODO: catch more specific type of exception once EntityTitleLookup contract is clarified
			throw new EntityRedirectLookupException( $entityId, null, $ex );
		}

		$forUpdate = $forUpdate === 'for update' ? DB_MASTER : DB_SLAVE;

		try {
			$db = $this->loadBalancer->getConnection( $forUpdate );
		} catch ( MWException $ex ) {
			throw new EntityRedirectLookupException( $entityId, null, $ex );
		}

		$row = $db->selectRow(
			array( 'page', 'redirect' ),
			array( 'page_id', 'rd_namespace', 'rd_title' ),
			array(
				'page_title' => $title->getDBkey(),
				'page_namespace' => $title->getNamespace()
			),
			__METHOD__,
			[],
			array(
				'redirect' => array( 'LEFT JOIN', 'rd_from=page_id' )
			)
		);

		try {
			$this->loadBalancer->reuseConnection( $db );
		} catch ( MWException $ex ) {
			throw new EntityRedirectLookupException( $entityId, null, $ex );
		}

		if ( !$row ) {
			throw new EntityRedirectLookupException( $entityId );
		}

		if ( $row->rd_namespace === null ) {
			return null;
		}

		$title = Title::makeTitle( $row->rd_namespace, $row->rd_title );

		return $this->entityIdLookup->getEntityIdForTitle( $title );
	}

}
