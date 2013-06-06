<?php

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../../..';
require_once $basePath . '/maintenance/Maintenance.php';

/**
 * Maintenance script for populating the Sites table from another wiki that runs the
 * SiteMatrix extension.
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
 * @since 0.1
 * @note: this should move into core, together with \Wikibase\Utils::insertDefaultSites
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class PopulateSitesTable extends \Maintenance {

	public function __construct() {
		$this->mDescription = 'Populate the sites table from another wiki that runs the SiteMatrix extension';

		$this->addOption( 'strip-protocols', "Strip http/https from URLs to make them protocol relative." );
		$this->addOption( 'load-from', "Full URL to the API of the wiki to fetch the site info from. "
				. "Default is https://meta.wikimedia.org/w/api.php", false, true );

		parent::__construct();
	}

	public function execute() {
		if ( !defined( 'WBL_VERSION' ) ) {
			$this->output( "You need to have WikibaseLib enabled in order to use this maintenance script!\n\n" );
			exit;
		}

		$stripProtocols = $this->getOption( 'strip-protocols' ) ? "stripProtocol" : false;
		$wiki = $this->getOption( 'load-from', 'https://meta.wikimedia.org/w/api.php' );

		\Wikibase\Utils::insertSitesFrom( $wiki, $stripProtocols );

		SiteSQLStore::newInstance()->getSites( 'recache' );

		$this->output( "done.\n" );
	}

}

$maintClass = 'PopulateSitesTable';
require_once( RUN_MAINTENANCE_IF_MAIN );
