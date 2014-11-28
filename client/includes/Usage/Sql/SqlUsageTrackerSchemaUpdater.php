<?php

namespace Wikibase\Client\Usage\Sql;

use DatabaseUpdater;
use MWException;
use Wikibase\Client\Usage\UsageTracker;
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
	 *
	 * @return bool
	 */
	public static function onSchemaUpdate( DatabaseUpdater $dbUpdater ) {
		if ( WikibaseClient::getDefaultInstance()->getSettings()->getSetting( 'useLegacyUsageIndex' ) ) {
			return true;
		}

		$usageTrackerSchemaUpdater = new self( $dbUpdater );
		$usageTrackerSchemaUpdater->doSchemaUpdate();

		return true;
	}

	/**
	 * Applies any schema updates
	 */
	public function doSchemaUpdate() {
		if ( !$this->dbUpdater->tableExists( UsageTracker::TABLE_NAME ) ) {
			$db = $this->dbUpdater->getDB();
			$script = $this->getUpdateScriptPath( 'entity_usage', $db->getType() );
			$this->dbUpdater->addExtensionTable( UsageTracker::TABLE_NAME, $script );

			// Register function for populating the table.
			$this->dbUpdater->addExtensionUpdate( array( array( $this, 'fillUsageTable' ) ) );
		}
	}

	/**
	 * Static wrapper for SqlUsageTrackerSchemaUpdater::doSchemaUpdate
	 *
	 * @param DatabaseUpdater $dbUpdater Unused.
	 */
	public function fillUsageTable( DatabaseUpdater $dbUpdater ) {
		$idParser = WikibaseClient::getDefaultInstance()->getEntityIdParser();

		$primer = new EntityUsageTableBuilder(
			$idParser,
			wfGetLB(), // would be nice to pass in $dbUpdater->getDB().
			1000
		);

		$primer->fillUsageTable();
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
