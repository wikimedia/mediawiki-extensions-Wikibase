<?php

declare( strict_types = 1 );

namespace Wikibase\Lib\Store\Sql;

use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\Lib\Changes\EntityChange;
use Wikibase\Lib\Changes\EntityChangeFactory;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\SelectQueryBuilder;
use Wikimedia\Timestamp\ConvertibleTimestamp;

/**
 * Only for use in {@link EntityChangeLookup}. Use that class instead.
 *
 * @license GPL-2.0-or-later
 */
class EntityChangeSelectQueryBuilder extends SelectQueryBuilder {

	private EntityIdParser $entityIdParser;
	private EntityChangeFactory $entityChangeFactory;

	public function __construct(
		IDatabase $db,
		EntityIdParser $entityIdParser,
		EntityChangeFactory $entityChangeFactory
	) {
		parent::__construct( $db );
		$this->entityIdParser = $entityIdParser;
		$this->entityChangeFactory = $entityChangeFactory;

		$this->select( [
			'change_id', 'change_type', 'change_time', 'change_object_id',
			'change_revision_id', 'change_user_id', 'change_info',
		] )->from( 'wb_changes' );
	}

	/** @return EntityChange[] */
	public function fetchChanges(): array {
		$changes = [];

		foreach ( $this->fetchResultSet() as $row ) {
			$data = [
				'id' => (int)$row->change_id,
				'time' => ConvertibleTimestamp::convert( TS_MW, $row->change_time ),
				'info' => $row->change_info,
				'user_id' => $row->change_user_id,
				'revision_id' => $row->change_revision_id,
			];
			$entityId = $this->entityIdParser->parse( $row->change_object_id );
			$changes[] = $this->entityChangeFactory->newForChangeType( $row->change_type, $entityId, $data );
		}

		return $changes;
	}

}
