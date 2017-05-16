<?php

namespace Wikibase;

use Exception;
use InvalidArgumentException;
use Maintenance;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Repo\PropertyDataTypeChanger;
use Wikibase\Repo\WikibaseRepo;
use User;

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../../..';
require_once $basePath . '/maintenance/Maintenance.php';

/**
 * @license GPL-2.0+
 * @author Marius Hoch
 */
class ChangePropertyDataType extends Maintenance {

	public function __construct() {
		parent::__construct();
		$this->addDescription( "Change the data type of a given Property.\n"
				. "Please note: You probably don't want to use this, as this will likely"
				. " break consumers of your data. Statements with the changed property will appear"
				. " to have changed without there being an edit to the entity containing the statement."
				. " Also derived information based on the old type will disappear and derived information"
				. " based on the new type will appear. In almost all cases you"
				. " should rather add a new property and make user of that." );

		$this->addOption( 'property-id', 'Id of the property to change.', true, true );
		$this->addOption(
			'new-data-type',
			'New data type id (this data type needs to have the same data value type the old data type had).',
			true,
			true
		);
	}

	public function execute() {
		if ( !WikibaseSettings::isRepoEnabled() ) {
			$this->error( "You need to have Wikibase enabled in order to use this maintenance script!\n", 1 );
		}

		$repo = WikibaseRepo::getDefaultInstance();

		$propertyIdSerialization = $this->getOption( 'property-id' );
		$newDataType = $this->getOption( 'new-data-type' );
		try {
			$propertyId = new PropertyId( $propertyIdSerialization );
		} catch ( InvalidArgumentException $e ) {
			$this->error( "Invalid property id: " . $propertyIdSerialization, 1 );
		}

		$propertyDataTypeChanger = new PropertyDataTypeChanger(
			$repo->getEntityRevisionLookup(),
			$repo->getEntityStore(),
			$repo->getDataTypeFactory()
		);

		// "Maintenance script" is in MediaWiki's $wgReservedUsernames
		$user = User::newFromName( 'Maintenance script' );
		try {
			$propertyDataTypeChanger->changeDataType( $propertyId, $user, $newDataType );
		} catch ( Exception $e ) {
			$this->error( "An error occured: " . $e->getMessage(), 1 );
		}

		$this->output( "Successfully updated the property data type to $newDataType.\n" );
	}

}

$maintClass = ChangePropertyDataType::class;
require_once RUN_MAINTENANCE_IF_MAIN;
