<?php

namespace Wikibase;
use LoggedUpdateMaintenance;

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../../..';

require_once $basePath . '/maintenance/Maintenance.php';

/**
 * Maintenance script for rebuilding the items_per_page table.
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
 * @since 0.3
 *
 * @file
 * @ingroup WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class RebuildEntityPerPage extends LoggedUpdateMaintenance {

	public function __construct() {
		parent::__construct();

		$this->mDescription = 'Rebuild the entites_per_page table';

		$this->addOption( 'only-missing', "Update only missing keys (per default, entire table is rebuilt)" );
		$this->addOption( 'batch-size', "Number of rows to update per batch (100 by default)", false, true );
	}

	/**
	 * @see LoggedUpdateMaintenance::doDBUpdates
	 *
	 * @return boolean
	 */
	public function doDBUpdates() {
		if ( !defined( 'WB_VERSION' ) ) {
			$this->output( "You need to have Wikibase enabled in order to use this maintenance script!\n\n" );
			exit;
		}

		$batchSize = intval( $this->getOption( 'batch-size', 100 ) );
		$onlyMissing = !$this->getOption( 'only-missing', false ) ;

		$reporter = new \ObservableMessageReporter();
		$reporter->registerReporterCallback(
			array( $this, 'report' )
		);

		$entityPerPageTable = StoreFactory::getStore( 'sqlstore' )->newEntityPerPage();

		$builder = new EntityPerPageBuilder( $entityPerPageTable );
		$builder->setReporter( $reporter );

		$builder->setBatchSize( $batchSize );
		$builder->setRebuildAll( $onlyMissing );

		$builder->rebuild();

		return true;
	}

	/**
	 * Outputs a message vis the output() method.
	 *
	 * @since 0.4
	 *
	 * @param $msg
	 */
	public function report( $msg ) {
		$this->output( "$msg\n" );
	}

	/**
	 * @see LoggedUpdateMaintenance::getUpdateKey
	 *
	 * @return string
	 */
	public function getUpdateKey() {
		return 'Wikibase\RebuildEntityPerPage';
	}

}

$maintClass = 'Wikibase\RebuildEntityPerPage';
require_once( RUN_MAINTENANCE_IF_MAIN );
