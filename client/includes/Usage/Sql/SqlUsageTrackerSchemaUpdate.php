<?php

namespace Wikibase\Client\Usage\Sql;

use DatabaseUpdater;
use MWException;
use Wikibase\Client\WikibaseClient;

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
	private $databaseUpdater;

	/**
	 * @param DatabaseUpdater $databaseUpdater
	 */
	public function __construct( DatabaseUpdater $databaseUpdater ) {
		$this->databaseUpdater = $databaseUpdater;
	}

	/**
	 * Static entry point for MediaWiki's LoadExtensionSchemaUpdates hook.
	 *
	 * @param DatabaseUpdater $databaseUpdater
	 *
	 * @return bool
	 */
	public static function onSchemaUpdate( DatabaseUpdater $databaseUpdater ) {
		if ( WikibaseClient::getDefaultInstance()->getSettings()->getSetting( 'useLegacyUsageIndex' ) ) {
			return true;
		}

		$sqlUsageTrackerSchemaUpdater = new self( $databaseUpdater );
		$sqlUsageTrackerSchemaUpdater->doSchemaUpdate();

		return true;
	}

	/**
	 * Applies any schema updates
	 */
	public function doSchemaUpdate() {
		$db = $this->databaseUpdater->getDB();
		$type = $db->getType();

		$script = $this->getUpdateScriptPath( 'entity_usage', $type );
		$this->databaseUpdater->addExtensionTable( 'wbc_entity_usage', $script );
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

		throw new MWException( "Could not find schema update script '$name'." );
	}

}
