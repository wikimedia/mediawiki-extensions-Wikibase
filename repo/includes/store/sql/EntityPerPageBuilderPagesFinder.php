<?php

namespace Wikibase;

use DatabaseBase;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class EntityPerPageBuilderPagesFinder {

	/**
	 * @var DatabaseBase
	 */
	protected $dbw;

	/**
	 * @var array
	 */
	protected $entityNamespaces;

	/**
	 * @var boolean
	 */
	protected $rebuildAll;

	/**
	 * @param DatabaseBase $dbw
	 * @param string[] $entityNamspaces
	 * @param boolean $rebuildAll
	 */
	public function __construct( DatabaseBase $dbw, $entityNamespaces, $rebuildAll = false ) {
		$this->dbw = $dbw;
		$this->entityNamespaces = $entityNamespaces;
		$this->rebuildAll = $rebuildAll;
	}

	/**
	 * @param int $startAfterPage
	 * @param int $batchSize
	 *
	 * @return ResultWrapper
	 */
	public function getPages( $startAfterPage, $batchSize ) {
		$pages = $this->dbw->select(
			array( 'page', 'wb_entity_per_page' ),
			array( 'page_id', 'page_title', 'page_namespace', 'page_content_model' ),
			$this->getQueryConds( $startAfterPage ),
			__METHOD__,
			array( 'LIMIT' => $batchSize, 'ORDER BY' => 'page_id' ),
			array( 'wb_entity_per_page' => array( 'LEFT JOIN', 'page_id = epp_page_id' ) )
		);

		return $pages;
	}

	/**
	 * @param int $lastPageSeen
	 *
	 * @return array
	 */
	private function getQueryConds( $lastPageSeen ) {
		$conds = array(
			'page_namespace' => $this->entityNamespaces,
			'page_id > ' . $lastPageSeen
		);

		if ( $this->rebuildAll === false ) {
			$conds[] = 'epp_page_id IS NULL';
		}

		return $conds;
	}

}
