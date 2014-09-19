<?php

namespace Wikibase\Client\Usage\Sql;

use DatabaseUpdater;

/**
 * Schema updater for SqlUsageTracker
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class SqlUsageTrackerSchemaUpdater {

	/**
	 * @var DatabaseUpdater
	 */
	private $dbUpdater;

	/**
	 * @param DatabaseUpdater $dbUpdater
	 */
	public function __construct( DatabaseUpdater $dbUpdater ) {
		$this->dbUpdater = $dbUpdater;
	}

	/**
	 * Static entry point for MediaWiki's LoadExtensionSchemaUpdates hook.
	 *
	 * @param DatabaseUpdater $dbUpdater
	 */
	public static function onSchemaUpdate( DatabaseUpdater $dbUpdater ) {
		$dbUpdater = new self( $dbUpdater );
		$dbUpdater->doSchemaUpdate();
	}

	/**
	 * Applies any schema updates
	 */
	public function doSchemaUpdate() {
		$db = $this->dbUpdater->getDB();
		$type = $db->getType();

		$script = $this->getUpdateScriptPath( 'entity_usage', $type );
		$this->dbUpdater->addExtensionTable( 'wbc_entity_usage', $script );
	}

	private function getUpdateScriptPath( $name, $type ) {
		$extensions = array(
			'.sql',
			'.' . $type . '.sql',
		);

		foreach ( $extensions as $ext ) {
			$path = __DIR__ . '/' . $name . $ext;

			if ( file_exists( $path ) ) {
				return $path;
			}
		}

		throw new \MWException( "Could not find schema update script '$name'." );
	}

}
