<?php

declare( strict_types = 1 );
namespace Wikibase\Lib\Store\Sql;

use Wikibase\Lib\Changes\Change;
use Wikibase\Lib\Changes\ChangeRow;
use Wikibase\Lib\Changes\ChangeStore;
use Wikibase\Lib\Rdbms\RepoDomainDb;
use Wikimedia\Assert\Assert;
use Wikimedia\Rdbms\DBQueryError;
use Wikimedia\Rdbms\IDatabase;

/**
 * @license GPL-2.0-or-later
 * @author Marius Hoch
 */
class SqlChangeStore implements ChangeStore {

	/**
	 * @var RepoDomainDb
	 */
	private $repoDomainDb;

	public function __construct( RepoDomainDb $repoDomainDb ) {
		$this->repoDomainDb = $repoDomainDb;
	}

	/**
	 * Saves the change to a database table and ensures it has a change-id.
	 *
	 * @note Only supports Change objects that are derived from ChangeRow.
	 *
	 * @param Change $change
	 *
	 * @throws DBQueryError
	 */
	public function saveChange( Change $change ) {
		Assert::parameterType( ChangeRow::class, $change, '$change' );
		'@phan-var ChangeRow $change';

		if ( $change->getId() === null ) {
			$this->insertChange( $change );
		} else {
			$this->updateChange( $change );
		}
	}

	public function deleteChangesByChangeIds( array $changeIds ): void {
		Assert::parameterElementType( 'integer', $changeIds, '$changeIds' );

		$dbw = $this->repoDomainDb->connections()->getWriteConnection();
		$dbw->delete( 'wb_changes', [ 'change_id' => $changeIds ], __METHOD__ );
	}

	private function updateChange( ChangeRow $change ) {
		$dbw = $this->repoDomainDb->connections()->getWriteConnection();
		$values = $this->getValues( $change, $dbw );

		$dbw->update(
			'wb_changes',
			$values,
			[ 'change_id' => $change->getId() ],
			__METHOD__
		);
	}

	private function insertChange( ChangeRow $change ) {
		$dbw = $this->repoDomainDb->connections()->getWriteConnection();
		$values = $this->getValues( $change, $dbw );

		$dbw->insert( 'wb_changes', $values, __METHOD__ );
		$change->setField( ChangeRow::ID, $dbw->insertId() );
	}

	/**
	 * @param ChangeRow $change
	 *
	 * @return array
	 */
	private function getValues( ChangeRow $change, IDatabase $db ) {
		$type = $change->getType();
		// TODO: Avoid depending on hasField here.
		$time = $change->hasField( ChangeRow::TIME ) ? $change->getTime() : wfTimestampNow();
		$objectId = $change->hasField( ChangeRow::OBJECT_ID ) ? $change->getObjectId() : '';
		// TODO: Introduce dedicated getter for revision ID.
		$revisionId = $change->hasField( ChangeRow::REVISION_ID ) ?
			$change->getField( ChangeRow::REVISION_ID ) : '0';
		$userId = $change->getUserId();
		$serializedInfo = $change->getSerializedInfo();

		return [
			'change_type' => $type,
			'change_time' => $db->timestamp( $time ),
			'change_object_id' => $objectId,
			'change_revision_id' => $revisionId,
			'change_user_id' => $userId,
			'change_info' => $serializedInfo,
		];
	}

}
