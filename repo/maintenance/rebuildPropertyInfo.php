<?php

namespace Wikibase\Repo\Maintenance;

use LoggedUpdateMaintenance;
use Onoi\MessageReporter\ObservableMessageReporter;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Services\Lookup\LegacyAdapterPropertyLookup;
use Wikibase\Lib\Store\Sql\PropertyInfoTable;
use Wikibase\Lib\WikibaseSettings;
use Wikibase\Repo\Store\Sql\PropertyInfoTableBuilder;
use Wikibase\Repo\WikibaseRepo;

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../../..';

require_once $basePath . '/maintenance/Maintenance.php';

/**
 * Maintenance script for rebuilding the property info table.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class RebuildPropertyInfo extends LoggedUpdateMaintenance {

	public function __construct() {
		parent::__construct();

		$this->addDescription( 'Rebuild the property info table.' );

		$this->addOption( 'rebuild-all', "Update property info for all properties (per default, only missing entries are created)" );
		$this->addOption( 'batch-size', "Number of rows to update per database transaction (100 per default)", false, true );
	}

	/**
	 * @see LoggedUpdateMaintenance::doDBUpdates
	 *
	 * @return bool
	 */
	public function doDBUpdates() {
		if ( !WikibaseSettings::isRepoEnabled() ) {
			$this->output( "You need to have Wikibase enabled in order to use this maintenance script!\n\n" );
			exit;
		}
		if ( !in_array( Property::ENTITY_TYPE, WikibaseRepo::getLocalEntitySource()->getEntityTypes() ) ) {
			$this->fatalError(
				"You can't run this maintenance script on foreign properties!",
				1
			);
		}

		$reporter = new ObservableMessageReporter();
		$reporter->registerReporterCallback(
			[ $this, 'report' ]
		);

		$propertySource = WikibaseRepo::getEntitySourceDefinitions()
			->getDatabaseSourceForEntityType( 'property' );

		$builder = new PropertyInfoTableBuilder(
			new PropertyInfoTable(
				WikibaseRepo::getEntityIdComposer(),
				WikibaseRepo::getRepoDomainDbFactory()->newForEntitySource( $propertySource ),
				true
			),
			new LegacyAdapterPropertyLookup( WikibaseRepo::getEntityLookup() ),
			WikibaseRepo::getPropertyInfoBuilder(),
			WikibaseRepo::getEntityNamespaceLookup()
		);
		$builder->setReporter( $reporter );

		$builder->setBatchSize( (int)$this->getOption( 'batch-size', 100 ) );
		$builder->setRebuildAll( $this->getOption( 'rebuild-all', false ) );

		$n = $builder->rebuildPropertyInfo();

		$this->output( "Done. Updated $n property info entries.\n" );

		return true;
	}

	/**
	 * @see LoggedUpdateMaintenance::getUpdateKey
	 *
	 * @return string
	 */
	public function getUpdateKey() {
		return 'Wikibase\RebuildPropertyInfo';
	}

	/**
	 * Outputs a message vis the output() method.
	 *
	 * @param string $msg
	 */
	public function report( $msg ) {
		$this->output( "$msg\n" );
	}

}

$maintClass = RebuildPropertyInfo::class;
require_once RUN_MAINTENANCE_IF_MAIN;
