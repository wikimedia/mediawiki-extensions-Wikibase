<?php

namespace Wikibase\Repo\Maintenance;

use Exception;
use InvalidArgumentException;
use Maintenance;
use User;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\Lib\WikibaseSettings;
use Wikibase\Repo\PropertyDataTypeChanger;
use Wikibase\Repo\WikibaseRepo;

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../../..';
require_once $basePath . '/maintenance/Maintenance.php';

/**
 * @license GPL-2.0-or-later
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
		$this->addOption(
			'summary',
			'Edit summary (will be appended to an automatic edit summary).',
			false,
			true
		);
	}

	public function execute() {
		if ( !WikibaseSettings::isRepoEnabled() ) {
			$this->fatalError( "You need to have Wikibase enabled in order to use this maintenance script!\n" );
		}
		if ( !in_array( Property::ENTITY_TYPE, WikibaseRepo::getLocalEntitySource()->getEntityTypes() ) ) {
			$this->fatalError(
				"You can't run this maintenance script on foreign properties!",
				1
			);
		}
		$propertyIdSerialization = $this->getOption( 'property-id' );
		$newDataType = $this->getOption( 'new-data-type' );
		try {
			$propertyId = new NumericPropertyId( $propertyIdSerialization );
		} catch ( InvalidArgumentException $e ) {
			$this->fatalError( "Invalid property id: " . $propertyIdSerialization );
		}

		$propertyDataTypeChanger = new PropertyDataTypeChanger(
			WikibaseRepo::getEntityRevisionLookup(),
			WikibaseRepo::getEntityStore(),
			WikibaseRepo::getDataTypeFactory()
		);

		$user = User::newSystemUser( User::MAINTENANCE_SCRIPT_USER, [ 'steal' => true ] );
		$summary = $this->getOption( 'summary', '' );
		try {
			$propertyDataTypeChanger->changeDataType( $propertyId, $user, $newDataType, $summary );
		} catch ( Exception $e ) {
			$this->fatalError( "An error occurred: " . $e->getMessage() );
		}

		$this->output( "Successfully updated the property data type to $newDataType.\n" );
	}

}

$maintClass = ChangePropertyDataType::class;
require_once RUN_MAINTENANCE_IF_MAIN;
