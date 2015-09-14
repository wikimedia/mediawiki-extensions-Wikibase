<?php

namespace Wikibase\Client\RecentChanges;

use MWException;
use RecentChange;
use Wikibase\Client\Store\Sql\ConsistentReadConnectionManager;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Daniel Kinzler
 */
class RecentChangesDuplicateDetector {

	/**
	 * @var ConsistentReadConnectionManager
	 */
	private $connectionManager;

	public function __construct( ConsistentReadConnectionManager $connectionManager ) {
		$this->connectionManager = $connectionManager;
	}

	/**
	 * Checks if a recent change entry already exists in the recentchanges table
	 *
	 * @since 0.4
	 *
	 * @throws MWException
	 *
	 * @return bool
	 */
	public function changeExists( RecentChange $change ) {
		$attribs = $change->getAttributes();

		//XXX: need to check master?
		$db = $this->connectionManager->getReadConnection();

		$res = $db->select(
			'recentchanges',
			array( 'rc_id', 'rc_timestamp', 'rc_type', 'rc_params' ),
			array(
				'rc_namespace' => $attribs['rc_namespace'],
				'rc_title' => $attribs['rc_title'],
				'rc_timestamp' => $attribs['rc_timestamp'],
				'rc_type' => RC_EXTERNAL
			),
			__METHOD__
		);

		if ( $res->numRows() == 0 ) {
			return false;
		}

		$changeRevId = $this->getParam( 'rev_id', $attribs['rc_params'] );
		$changeParentId = $this->getParam( 'parent_id', $attribs['rc_params'] );

		foreach ( $res as $rc ) {
			$parent_id = $this->getParam( 'parent_id', $rc->rc_params );
			$rev_id = $this->getParam( 'rev_id', $rc->rc_params );

			if ( $rev_id === $changeRevId
				&& $parent_id === $changeParentId ) {
				return true;
			}
		}

		$this->connectionManager->releaseConnection( $db );
		return false;
	}

	/**
	 * Get a param from wikibase-repo-change array in rc_params
	 *
	 * @since 0.4
	 *
	 * @param string $param metadata array key
	 * @param array|string $rc_params
	 *
	 * @return mixed|bool
	 */
	private function getParam( $param, $rc_params ) {
		if ( is_string( $rc_params ) ) {
			$rc_params = unserialize( $rc_params );
		}

		if ( is_array( $rc_params ) && array_key_exists( 'wikibase-repo-change', $rc_params ) ) {
			$metadata = $rc_params['wikibase-repo-change'];
			if ( is_array( $metadata ) && array_key_exists( $param, $metadata ) ) {
				return $metadata[$param];
			}
		}
		return false;
	}

}
