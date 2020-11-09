<?php

declare( strict_types = 1 );

use MediaWiki\MediaWikiServices;
use Wikibase\Lib\DataTypeDefinitions;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Lib\WikibaseSettings;

return [
	/** Do not (yet) use this service directly. Get it from the WikibaseRepo instance instead. */
	'WikibaseRepo.DataTypeDefinitions' => function ( MediaWikiServices $services ): DataTypeDefinitions {
		$baseDataTypes = require __DIR__ . '/../lib/WikibaseLib.datatypes.php';
		$repoDataTypes = require __DIR__ . '/WikibaseRepo.datatypes.php';

		$dataTypes = array_merge_recursive( $baseDataTypes, $repoDataTypes );

		$services->getHookContainer()->run( 'WikibaseRepoDataTypes', [ &$dataTypes ] );

		// TODO get $settings from $services
		$settings = WikibaseSettings::getRepoSettings();

		return new DataTypeDefinitions(
			$dataTypes,
			$settings->getSetting( 'disabledDataTypes' )
		);
	},

	/** Do not (yet) use this service directly. Get it from the WikibaseRepo instance instead. */
	'WikibaseRepo.EntityTypeDefinitions' => function ( MediaWikiServices $services ): EntityTypeDefinitions {
		$baseEntityTypes = require __DIR__ . '/../lib/WikibaseLib.entitytypes.php';
		$repoEntityTypes = require __DIR__ . '/WikibaseRepo.entitytypes.php';

		$entityTypes = array_merge_recursive( $baseEntityTypes, $repoEntityTypes );

		$services->getHookContainer()->run( 'WikibaseRepoEntityTypes', [ &$entityTypes ] );

		return new EntityTypeDefinitions( $entityTypes );
	},
];
