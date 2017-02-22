<?php

namespace Wikibase\Repo\Store\Sql;

use DBQueryError;
use LoadBalancer;
use Wikibase\Change;
use Wikibase\ChangeRow;
use Wikibase\Repo\Store\ChangeStore;
use Wikimedia\Assert\Assert;

/**
 * @license GPL-2.0+
 * @author Marius Hoch
 */
class SqlChangeStore implements ChangeStore {

	/**
	 * @var LoadBalancer
	 */
	private $loadBalancer;

	/**
	 * @param LoadBalancer $loadBalancer
	 */
	public function __construct( LoadBalancer $loadBalancer ) {
		$this->loadBalancer = $loadBalancer;
	}

	/**
	 * Saves the change to a database table.
	 *
	 * @note Only supports Change objects that are derived from ChangeRow.
	 *
	 * @param Change $change
	 *
	 * @throws DBQueryError
	 */
	public function saveChange( Change $change ) {
		Assert::parameterType( ChangeRow::class, $change, '$change' );

		if ( $change->getId() === null ) {
			$this->insertChange( $change );
		} else {
			$this->updateChange( $change );
		}
	}

	private function updateChange( ChangeRow $change ) {
		$values = $this->getValues( $change );

		$dbw = $this->loadBalancer->getConnection( DB_MASTER );

		$dbw->update(
			'wb_changes',
			$values,
			array( 'change_id' => $change->getId() ),
			__METHOD__
		);

		$this->loadBalancer->reuseConnection( $dbw );
	}

	private function insertChange( ChangeRow $change ) {
		$values = $this->getValues( $change );

		$dbw = $this->loadBalancer->getConnection( DB_MASTER );

		$dbw->insert( 'wb_changes', $values, __METHOD__ );
		$change->setField( 'id', $dbw->insertId() );

		$this->loadBalancer->reuseConnection( $dbw );
	}

	/**
	 * @param ChangeRow $change
	 *
	 * @return array
	 */
	private function getValues( ChangeRow $change ) {
		$time = $change->hasField( 'time' ) ? $change->getTime() : wfTimestampNow();
		$objectId = $change->getObjectId();
		$revisionId = $change->hasField( 'revision_id' ) ? $change->getField( 'revision_id' ) : 0;
		$userId = $change->hasField( 'user_id' ) ? $change->getField( 'user_id' ) : 0;

		return array(
			'change_type' => $change->getType(),
			'change_time' => $time,
			'change_object_id' => $objectId,
			'change_revision_id' => $revisionId,
			'change_user_id' => $userId,
			'change_info' => $change->serializeInfo( $change->getField( 'info' ) )
		);
	}

}
