<?php

namespace Wikibase\Repo\Store\Sql;

use Wikibase\Change;
use Wikibase\ChangeRow;
use Wikibase\Repo\Store\ChangeStore;
use Wikimedia\Assert\Assert;
use Wikimedia\Rdbms\DBQueryError;
use Wikimedia\Rdbms\LoadBalancer;

/**
 * @license GPL-2.0+
 * @author Marius Hoch
 */
class SqlChangeStore implements ChangeStore {

	/**
	 * @var LoadBalancer
	 */
	private $loadBalancer;

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
			[ 'change_id' => $change->getId() ],
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
		$type = $change->getType();
		// TODO: Avoid depending on hasField here.
		$time = $change->hasField( 'time' ) ? $change->getTime() : wfTimestampNow();
		$objectId = $change->hasField( 'object_id' ) ? $change->getObjectId() : '';
		// TODO: Introduce dedicated getter for revision ID.
		$revisionId = $change->hasField( 'revision_id' ) ? $change->getField( 'revision_id' ) : '0';
		$userId = $change->getUserId();
		$serializedInfo = $change->getSerializedInfo();

		return [
			'change_type' => $type,
			'change_time' => $time,
			'change_object_id' => $objectId,
			'change_revision_id' => $revisionId,
			'change_user_id' => $userId,
			'change_info' => $serializedInfo,
		];
	}

}
