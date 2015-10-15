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
 * @license GNU GPL v2+
 *
 * @author Marius Hoch
 */
class ChangePropertyDataType extends Maintenance {

	public function __construct() {
		parent::__construct();
		$this->mDescription = "Change the data type of a given Property.\n"
				. "Please note: This is extremely dangerous, make sure you know what you're doing.";

		$this->addOption( 'property-id', 'Id of the property to change.', true, true );
		$this->addOption(
			'data-type-id',
			'New data type id (this data type needs to have the same data value type the old data type had).',
			true,
			true
		);
	}

	public function execute() {
		if ( !defined( 'WB_VERSION' ) ) {
			$this->output( "You need to have Wikibase enabled in order to use this maintenance script!\n\n" );
			exit;
		}

		$repo = WikibaseRepo::getDefaultInstance();

		$propertyIdSerialization = $this->getOption( 'property-id' );
		$dataTypeId = $this->getOption( 'data-type-id' );
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
			$propertyDataTypeChanger->changeDataType( $propertyId, $user, $dataTypeId );
		} catch ( Exception $e ) {
			$this->error( "An error occured: " . $e->getMessage(), 1 );
		}

		$this->output( "Successfully updated the property data type to $dataTypeId.\n" );
	}

}

$maintClass = 'Wikibase\ChangePropertyDataType';
require_once RUN_MAINTENANCE_IF_MAIN;
