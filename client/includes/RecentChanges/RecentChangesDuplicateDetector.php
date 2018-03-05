<?php

namespace Wikibase\Client\RecentChanges;

use MWException;
use RecentChange;
use Wikimedia\Rdbms\SessionConsistentConnectionManager;

/**
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Daniel Kinzler
 */
class RecentChangesDuplicateDetector {

	/**
	 * @var SessionConsistentConnectionManager
	 */
	private $connectionManager;

	public function __construct( SessionConsistentConnectionManager $connectionManager ) {
		$this->connectionManager = $connectionManager;
	}

	/**
	 * Checks if a recent change entry already exists in the recentchanges table
	 *
	 * @param RecentChange $change
	 *
	 * @throws MWException
	 * @return bool
	 */
	public function changeExists( RecentChange $change ) {
		$attribs = $change->getAttributes();

		//XXX: need to check master?
		$db = $this->connectionManager->getReadConnectionRef();

		$res = $db->select(
			'recentchanges',
			[ 'rc_id', 'rc_timestamp', 'rc_source', 'rc_params' ],
			[
				'rc_namespace' => $attribs['rc_namespace'],
				'rc_title' => $attribs['rc_title'],
				'rc_timestamp' => $attribs['rc_timestamp'],
				'rc_source' => RecentChangeFactory::SRC_WIKIBASE
			],
			__METHOD__
		);

		if ( $res->numRows() === 0 ) {
			return false;
		}

		$changeMetadata = $this->getMetadata( $attribs['rc_params'] );

		$changeRevId = $changeMetadata[ 'rev_id' ];
		$changeParentId = $changeMetadata[ 'parent_id' ];

		foreach ( $res as $rc ) {
			$metadata = $this->getMetadata( $rc->rc_params );

			$parent_id = $metadata[ 'parent_id' ];
			$rev_id = $metadata[ 'rev_id' ];

			if ( $rev_id === $changeRevId
				&& $parent_id === $changeParentId ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Extracts the metadata array from the value of an rc_params field.
	 *
	 * @param array|string $rc_params
	 *
	 * @return array
	 */
	private function getMetadata( $rc_params ) {
		if ( is_string( $rc_params ) ) {
			$rc_params = unserialize( $rc_params );
		}

		if ( is_array( $rc_params ) && array_key_exists( 'wikibase-repo-change', $rc_params ) ) {
			$metadata = $rc_params['wikibase-repo-change'];
		} else {
			$metadata = [];
		}

		$metadata = array_merge( [ 'parent_id' => 0, 'rev_id' => 0 ], $metadata );
		return $metadata;
	}

}
