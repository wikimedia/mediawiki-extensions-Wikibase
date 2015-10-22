<?php

namespace Wikibase\Repo\Maintenance;

use Maintenance;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Reporting\ObservableMessageReporter;
use Wikibase\Repo\Store\EntityPerPage;
use Wikibase\Repo\Store\SQL\EntityPerPageIdPager;
use Wikibase\Repo\WikibaseRepo;

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false
	? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../../..';

require_once $basePath . '/maintenance/Maintenance.php';

/**
 * Maintenance script for populating the geo_tags table
 * from Wikibase entities, along with the page_image page prop.
 *
 * This script is a somewhat temporary solution and alternative
 * to using refreshLinks.php which is has some bugs and is not
 * very flexible for performing only specific updates vs.
 * updating everything, or for updating only pages in specific
 * namespace.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class RebuildGeoData extends Maintenance {

	/**
	 * @var GeoDataBuilder
	 */
	private $geoDataBuilder;

	public function __construct() {
		parent::__construct();

		$this->mDescription = 'Updates GeoData and PageImages page prop.';

		$this->addOption( 'batch-size', 'Number of entities to process per batch. Default: 100.',
			false, true
		);

		$this->addOption( 'limit', 'Maximum number of entities to process. Default: Unlimited.',
			false, true
		);

		$this->addOption( 'start-entity', "Entity ID to start from", false, true );
	}

	/**
	 * Do the actual work.
	 */
	public function execute() {
		if ( !class_exists( 'GeoDataHooks' ) ) {
			$this->error( 'GeoData extension must be enabled for this script to run.', 1 );
		}

		$this->setServices();

		$this->geoDataBuilder->rebuild(
			$this->getStartEntityIdFromOption(),
			(int)$this->getOption( 'batch-size', 100 ),
			(int)$this->getOption( 'limit', 0 )
		);

		$this->output( "Done\n" );
	}

	private function setServices() {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();

		$entityIdPager = new EntityPerPageIdPager(
			$wikibaseRepo->getStore()->newEntityPerPage(),
			null, // any entity type, @todo could specify StatementListProvider types
			EntityPerPage::NO_REDIRECTS
		);

		$reporter = new ObservableMessageReporter();
		$reporter->registerReporterCallback(
			array( $this, 'report' )
		);

		$this->geoDataBuilder = new GeoDataBuilder(
			$entityIdPager,
			$wikibaseRepo->getEntityTitleLookup(),
			$wikibaseRepo->getPropertyDataTypeLookup(),
			$wikibaseRepo->getEntityStore(),
			$reporter
		);
	}

	private function getStartEntityIdFromOption() {
		if ( $this->hasOption( 'start-entity' ) ) {
			try {
				return $this->entityIdParser->parse( $this->getOption( 'start-entity' ) );
			} catch ( EntityIdParsingException $ex ) {
				$this->error( "Invalid start-entity. Expected an Entity ID, such as 'Q1'.", 1 );
			}
		}

		return new ItemId( 'Q1' );
	}

	/**
	 * Outputs a message vis the output() method.
	 *
	 * @param string $msg
	 */
	public function report( $msg ) {
		$this->output( date( 'H:i:s' ) . ": $msg\n" );
	}

}

$maintClass = 'Wikibase\Repo\Maintenance\RebuildGeoData';
require_once RUN_MAINTENANCE_IF_MAIN;
