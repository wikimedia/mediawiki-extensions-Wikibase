<?php

declare( strict_types = 1 );

use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use Psr\Log\LoggerInterface;
use Wikibase\Client\EntitySourceDefinitionsLegacyClientSettingsParser;
use Wikibase\Client\RepoLinker;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataAccess\EntitySourceDefinitionsConfigParser;
use Wikibase\DataModel\Entity\DispatchingEntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Services\EntityId\EntityIdComposer;
use Wikibase\Lib\DataTypeDefinitions;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\Store\CachingPropertyOrderProvider;
use Wikibase\Lib\Store\FallbackPropertyOrderProvider;
use Wikibase\Lib\Store\HttpUrlPropertyOrderProvider;
use Wikibase\Lib\Store\WikiPagePropertyOrderProvider;
use Wikibase\Lib\TermFallbackCache\TermFallbackCacheFacade;
use Wikibase\Lib\TermFallbackCache\TermFallbackCacheServiceFactory;
use Wikibase\Lib\TermFallbackCacheFactory;
use Wikibase\Lib\WikibaseSettings;

/** @phpcs-require-sorted-array */
return [

	'WikibaseClient.DataTypeDefinitions' => function ( MediaWikiServices $services ): DataTypeDefinitions {
		$baseDataTypes = require __DIR__ . '/../lib/WikibaseLib.datatypes.php';
		$clientDataTypes = require __DIR__ . '/WikibaseClient.datatypes.php';

		$dataTypes = array_merge_recursive( $baseDataTypes, $clientDataTypes );

		$services->getHookContainer()->run( 'WikibaseClientDataTypes', [ &$dataTypes ] );

		// TODO get $settings from $services
		$settings = WikibaseSettings::getClientSettings();

		return new DataTypeDefinitions(
			$dataTypes,
			$settings->getSetting( 'disabledDataTypes' )
		);
	},

	'WikibaseClient.EntityIdComposer' => function ( MediaWikiServices $services ): EntityIdComposer {
		return new EntityIdComposer(
			WikibaseClient::getEntityTypeDefinitions( $services )
				->get( EntityTypeDefinitions::ENTITY_ID_COMPOSER_CALLBACK )
		);
	},

	'WikibaseClient.EntityIdParser' => function ( MediaWikiServices $services ): EntityIdParser {
		return new DispatchingEntityIdParser(
			WikibaseClient::getEntityTypeDefinitions( $services )->getEntityIdBuilders()
		);
	},

	// TODO: current settings (especially (foreign) repositories blob) might be quite confusing
	// Having a "entitySources" or so setting might be better, and would also allow unifying
	// the way these are configured in Repo and in Client parts
	'WikibaseClient.EntitySourceDefinitions' => function ( MediaWikiServices $services ): EntitySourceDefinitions {
		$settings = WikibaseClient::getSettings( $services );
		$entityTypeDefinitions = WikibaseClient::getEntityTypeDefinitions( $services );

		if ( $settings->hasSetting( 'entitySources' ) && !empty( $settings->getSetting( 'entitySources' ) ) ) {
			$configParser = new EntitySourceDefinitionsConfigParser();

			return $configParser->newDefinitionsFromConfigArray( $settings->getSetting( 'entitySources' ), $entityTypeDefinitions );
		}

		$parser = new EntitySourceDefinitionsLegacyClientSettingsParser();
		return $parser->newDefinitionsFromSettings( $settings, $entityTypeDefinitions );
	},

	'WikibaseClient.EntityTypeDefinitions' => function ( MediaWikiServices $services ): EntityTypeDefinitions {
		$entityTypes = require __DIR__ . '/../lib/WikibaseLib.entitytypes.php';

		$services->getHookContainer()->run( 'WikibaseClientEntityTypes', [ &$entityTypes ] );

		return new EntityTypeDefinitions( $entityTypes );
	},

	'WikibaseClient.Logger' => function ( MediaWikiServices $services ): LoggerInterface {
		return LoggerFactory::getInstance( 'Wikibase' );
	},

	'WikibaseClient.PropertyOrderProvider' => function ( MediaWikiServices $services ): CachingPropertyOrderProvider {
		$title = $services->getTitleFactory()->newFromTextThrow( 'MediaWiki:Wikibase-SortedProperties' );
		$innerProvider = new WikiPagePropertyOrderProvider( $title );

		$url = WikibaseClient::getSettings( $services )->getSetting( 'propertyOrderUrl' );

		if ( $url !== null ) {
			$innerProvider = new FallbackPropertyOrderProvider(
				$innerProvider,
				new HttpUrlPropertyOrderProvider(
					$url,
					$services->getHttpRequestFactory(),
					WikibaseClient::getLogger( $services )
				)
			);
		}

		return new CachingPropertyOrderProvider(
			$innerProvider,
			ObjectCache::getLocalClusterInstance()
		);
	},

	'WikibaseClient.RepoLinker' => function ( MediaWikiServices $services ): RepoLinker {
		$settings = WikibaseClient::getSettings( $services );

		return new RepoLinker(
			WikibaseClient::getEntitySourceDefinitions( $services ),
			$settings->getSetting( 'repoUrl' ),
			$settings->getSetting( 'repoArticlePath' ),
			$settings->getSetting( 'repoScriptPath' )
		);
	},

	'WikibaseClient.Settings' => function ( MediaWikiServices $services ): SettingsArray {
		return WikibaseSettings::getClientSettings();
	},

	'WikibaseClient.TermFallbackCache' => function ( MediaWikiServices $services ): TermFallbackCacheFacade {
		return new TermFallbackCacheFacade(
			WikibaseClient::getTermFallbackCacheFactory( $services )->getTermFallbackCache(),
			WikibaseClient::getSettings( $services )->getSetting( 'sharedCacheDuration' )
		);
	},

	'WikibaseClient.TermFallbackCacheFactory' => function ( MediaWikiServices $services ): TermFallbackCacheFactory {
		$settings = WikibaseClient::getSettings( $services );
		return new TermFallbackCacheFactory(
			$settings->getSetting( 'sharedCacheType' ),
			WikibaseClient::getLogger( $services ),
			$services->getStatsdDataFactory(),
			hash( 'sha256', $services->getMainConfig()->get( 'SecretKey' ) ),
			new TermFallbackCacheServiceFactory(),
			$settings->getSetting( 'termFallbackCacheVersion' )
		);
	},

];
