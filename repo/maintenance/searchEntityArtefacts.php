<?php

namespace Wikibase;
use Maintenance;

/**
 * Script that queries the database for entity artifacts
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @ingroup Maintenance
 *
 * @licence GNU GPL v2+
 * @author Jens Ohlig <jens.ohlig@wikimedia.de>
 */
$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../../..';
require_once $basePath . '/maintenance/Maintenance.php';

/**
 * Query the database for entity artifacts
 *
 * @ingroup Maintenance
 */
class SearchEntityArtefacts extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->mDescription = "";
	}

	public function execute() {
		if ( !defined( 'WB_VERSION' ) ) {
			$this->output( "You need to have Wikibase enabled in order to use this maintenance script!\n\n" );
			exit;
		}

		$force = $this->getOption( 'force', false );
		$pidfile = Utils::makePidFilename( 'WBsearchEntityArtefacts', wfWikiID() );

		if ( !Utils::getPidLock( $pidfile, $force ) ) {
			$this->output( date( 'H:i:s' ) . " already running, exiting\n" );
			exit( 5 );
		}

		$this->searchArtefacts();

		$this->output( date( 'H:i:s' ) . " done, exiting\n" );
		unlink( $pidfile ); // delete lockfile on normal exit
	}

	public function searchArtefacts() {
		$dbw = wfGetDB( DB_MASTER );
		$begin = 0;
		$entityContentFactory = EntityContentFactory::singleton();
		$pageArray = array();
		do {
			$pages = $dbw->select(
				array( 'page' ),
				array( 'page_title' ),
				array( 'page_namespace' => Utils::getEntityNamespaces() ),
				__METHOD__,
				array( 'LIMIT' => 1000, 'OFFSET' => $begin )
			);

			foreach ( $pages as $pageRow ) {
				$id = EntityId::newFromPrefixedId( $pageRow->page_title );

				if ( $id !== null ) {
					$entityContent = $entityContentFactory->getFromId( $id, \Revision::RAW );
					$pageArray[] = $entityContent;
				}
			}
			$begin += 1000;
		} while ( $pages->numRows() === 1000 );
		$rows = $dbw->select(
			array( 'wb_entity_per_page' ),
			array(
				'entity_id' => 'epp_entity_id',
				'entity_type' => 'epp_entity_type',
			),
			array(),
			__METHOD__,
			array(),
			array()
		);

		$entities = array();
		foreach ( $rows as $row ) {
			$entities[] = new EntityId( $row->entity_type, (int)$row->entity_id );
		}
		foreach ( $entities as $entity ) {
			$content = $entityContentFactory->getFromId( $entity, \Revision::RAW );
			if ( !in_array( $content, $pageArray ) ) {
				print_r( $content );
			}
		}
	}
}

$maintClass = 'Wikibase\SearchEntityArtefacts';
require_once( RUN_MAINTENANCE_IF_MAIN );
