<?php

/**
 * Welcome to the inside of Wikibase,              <>
 * the software that powers                   /\        /\
 * Wikidata and other                       <{  }>    <{  }>
 * structured data websites.        <>   /\   \/   /\   \/   /\   <>
 *                                     //  \\    //  \\    //  \\
 * It is Free Software.              <{{    }}><{{    }}><{{    }}>
 *                                /\   \\  //    \\  //    \\  //   /\
 *                              <{  }>   ><        \/        ><   <{  }>
 *                                \/   //  \\              //  \\   \/
 *                            <>     <{{    }}>     +--------------------------+
 *                                /\   \\  //       |                          |
 *                              <{  }>   ><        /|  W  I  K  I  B  A  S  E  |
 *                                \/   //  \\    // |                          |
 * We are                            <{{    }}><{{  +--------------------------+
 * looking for people                  \\  //    \\  //    \\  //
 * like you to join us in           <>   \/   /\   \/   /\   \/   <>
 * developing it further. Find              <{  }>    <{  }>
 * out more at http://wikiba.se               \/        \/
 * and join the open data revolution.              <>
 */

/**
 * Entry point for the Wikibase Repository extension.
 *
 * @see README.md
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase_Repository
 *
 * @license GPL-2.0-or-later
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'Not an entry point.' );
}

if ( defined( 'WB_VERSION' ) ) {
	// Do not initialize more than once.
	return 1;
}

define( 'WB_VERSION', '0.5 alpha' );

// Needs to be 1.31c because version_compare() works in confusing ways.
if ( version_compare( $GLOBALS['wgVersion'], '1.31c', '<' ) ) {
	die( "<b>Error:</b> Wikibase requires MediaWiki 1.31 or above.\n" );
}

/**
 * Registry of ValueParsers classes or factory callbacks, by datatype.
 * @note: that parsers are also registered under their old names for backwards compatibility,
 * for use with the deprecated 'parser' parameter of the wbparsevalue API module.
 */
$GLOBALS['wgValueParsers'] = [];

// Include the WikibaseLib extension if that hasn't been done yet, since it's required for Wikibase to work.
if ( !defined( 'WBL_VERSION' ) ) {
	include_once __DIR__ . '/../lib/WikibaseLib.php';
}

if ( !defined( 'WBL_VERSION' ) ) {
	throw new Exception( 'Wikibase depends on the WikibaseLib extension.' );
}

if ( !defined( 'WIKIBASE_VIEW_VERSION' ) ) {
	include_once __DIR__ . '/../view/WikibaseView.php';
}

if ( !defined( 'WIKIBASE_VIEW_VERSION' ) ) {
	throw new Exception( 'Wikibase depends on WikibaseView.' );
}

// Load autoload info as long as extension classes are not PSR-4-autoloaded
require_once __DIR__  . '/autoload.php';
// Nasty hack: part of repo relies on classes defined in Client! load it if in repo-only mode
if ( !defined( 'WBC_VERSION' ) ) {
	global $wgAutoloadClasses;
	$wgAutoloadClasses['Wikibase\\Client\\Store\\TitleFactory'] = __DIR__ . '/../client/includes/Store/TitleFactory.php';
}

call_user_func( function() {
	global $wgAPIListModules,
		$wgAPIModules,
		$wgAvailableRights,
		$wgExtensionCredits,
		$wgExtensionMessagesFiles,
		$wgGrantPermissions,
		$wgGroupPermissions,
		$wgHooks,
		$wgJobClasses,
		$wgMessagesDirs,
		$wgResourceModules,
		$wgSpecialPages,
		$wgValueParsers,
		$wgWBRepoDataTypes,
		$wgWBRepoSettings;

	$wgExtensionCredits['wikibase'][] = [
		'path' => __DIR__ . '/../README.md',
		'name' => 'WikibaseRepository',
		'author' => [
			'The Wikidata team',
		],
		'url' => 'https://www.mediawiki.org/wiki/Extension:Wikibase_Repository',
		'descriptionmsg' => 'wikibase-desc',
		'license-name' => 'GPL-2.0-or-later'
	];

	// Registry and definition of data types
	$wgWBRepoDataTypes = require __DIR__ . '/../lib/WikibaseLib.datatypes.php';

	$repoDataTypes = require __DIR__ . '/WikibaseRepo.datatypes.php';

	// merge WikibaseRepo.datatypes.php into $wgWBRepoDataTypes
	foreach ( $repoDataTypes as $type => $repoDef ) {
		$baseDef = isset( $wgWBRepoDataTypes[$type] ) ? $wgWBRepoDataTypes[$type] : [];
		$wgWBRepoDataTypes[$type] = array_merge( $baseDef, $repoDef );
	}

	// constants
	define( 'CONTENT_MODEL_WIKIBASE_ITEM', "wikibase-item" );
	define( 'CONTENT_MODEL_WIKIBASE_PROPERTY', "wikibase-property" );

	// rights
	// names should be according to other naming scheme
	$wgGroupPermissions['*']['item-term'] = true;
	$wgGroupPermissions['*']['property-term'] = true;
	$wgGroupPermissions['*']['item-merge'] = true;
	$wgGroupPermissions['*']['item-redirect'] = true;
	$wgGroupPermissions['*']['property-create'] = true;

	$wgAvailableRights[] = 'item-term';
	$wgAvailableRights[] = 'property-term';
	$wgAvailableRights[] = 'item-merge';
	$wgAvailableRights[] = 'item-redirect';
	$wgAvailableRights[] = 'property-create';

	$wgGrantPermissions['editpage']['item-term'] = true;
	$wgGrantPermissions['editpage']['item-redirect'] = true;
	$wgGrantPermissions['editpage']['item-merge'] = true;
	$wgGrantPermissions['editpage']['property-term'] = true;
	$wgGrantPermissions['createeditmovepage']['property-create'] = true;

	// i18n
	$wgMessagesDirs['Wikibase'] = __DIR__ . '/i18n';
	$wgExtensionMessagesFiles['WikibaseAlias'] = __DIR__ . '/Wikibase.i18n.alias.php';
	$wgExtensionMessagesFiles['WikibaseNS'] = __DIR__ . '/Wikibase.i18n.namespaces.php';

	/**
	 * @var callable[] $wgValueParsers Defines parser factory callbacks by parser name (not data type name).
	 * @deprecated use $wgWBRepoDataTypes instead.
	 */
	$wgValueParsers['wikibase-entityid'] = $wgWBRepoDataTypes['VT:wikibase-entityid']['parser-factory-callback'];
	$wgValueParsers['globecoordinate'] = $wgWBRepoDataTypes['VT:globecoordinate']['parser-factory-callback'];

	// 'null' is not a datatype. Kept for backwards compatibility.
	$wgValueParsers['null'] = function() {
		return new ValueParsers\NullParser();
	};

	// API module registration
	$wgAPIModules['wbgetentities'] = [
		'class' => Wikibase\Repo\Api\GetEntities::class,
		'factory' => function( ApiMain $apiMain, $moduleName ) {
			$wikibaseRepo = Wikibase\Repo\WikibaseRepo::getDefaultInstance();
			$settings = $wikibaseRepo->getSettings();
			$apiHelperFactory = $wikibaseRepo->getApiHelperFactory( $apiMain->getContext() );

			$siteLinkTargetProvider = new Wikibase\Repo\SiteLinkTargetProvider(
				$wikibaseRepo->getSiteLookup(),
				$settings->getSetting( 'specialSiteLinkGroups' )
			);

			return new Wikibase\Repo\Api\GetEntities(
				$apiMain,
				$moduleName,
				$wikibaseRepo->getStringNormalizer(),
				$wikibaseRepo->getLanguageFallbackChainFactory(),
				$siteLinkTargetProvider,
				$wikibaseRepo->getStore()->getEntityPrefetcher(),
				$settings->getSetting( 'siteLinkGroups' ),
				$apiHelperFactory->getErrorReporter( $apiMain ),
				$apiHelperFactory->getResultBuilder( $apiMain ),
				$wikibaseRepo->getEntityRevisionLookup(),
				$wikibaseRepo->getEntityIdParser()
			);
		}
	];
	$wgAPIModules['wbsetlabel'] = [
		'class' => Wikibase\Repo\Api\SetLabel::class,
		'factory' => function ( ApiMain $mainModule, $moduleName ) {
			return new Wikibase\Repo\Api\SetLabel(
				$mainModule,
				$moduleName,
				Wikibase\Repo\WikibaseRepo::getDefaultInstance()->getChangeOpFactoryProvider()
					->getFingerprintChangeOpFactory()
			);
		}
	];
	$wgAPIModules['wbsetdescription'] = [
		'class' => Wikibase\Repo\Api\SetDescription::class,
		'factory' => function ( ApiMain $mainModule, $moduleName ) {
			return new Wikibase\Repo\Api\SetDescription(
				$mainModule,
				$moduleName,
				Wikibase\Repo\WikibaseRepo::getDefaultInstance()->getChangeOpFactoryProvider()
					->getFingerprintChangeOpFactory()
			);
		}
	];
	$wgAPIModules['wbsearchentities'] = [
		'class' => Wikibase\Repo\Api\SearchEntities::class,
		'factory' => function( ApiMain $mainModule, $moduleName ) {
			$repo = Wikibase\Repo\WikibaseRepo::getDefaultInstance();
			$entitySearchHelper = new Wikibase\Repo\Api\TypeDispatchingEntitySearchHelper(
				$repo->getEntitySearchHelperCallbacks(),
				$mainModule->getRequest()
			);

			return new Wikibase\Repo\Api\SearchEntities(
				$mainModule,
				$moduleName,
				$entitySearchHelper,
				$repo->getEntityTitleLookup(),
				$repo->getPropertyDataTypeLookup(),
				$repo->getTermsLanguages(),
				$repo->getEnabledEntityTypes(),
				$repo->getConceptBaseUris()
			);
		},
	];
	$wgAPIModules['wbsetaliases'] = [
		'class' => Wikibase\Repo\Api\SetAliases::class,
		'factory' => function ( ApiMain $mainModule, $moduleName ) {
			return new Wikibase\Repo\Api\SetAliases(
				$mainModule,
				$moduleName,
				Wikibase\Repo\WikibaseRepo::getDefaultInstance()->getChangeOpFactoryProvider()
					->getFingerprintChangeOpFactory()
			);
		}
	];
	$wgAPIModules['wbeditentity'] = [
		'class' => Wikibase\Repo\Api\EditEntity::class,
		'factory' => function ( ApiMain $mainModule, $moduleName ) {
			$wikibaseRepo = Wikibase\Repo\WikibaseRepo::getDefaultInstance();
			$changeOpFactoryProvider = $wikibaseRepo->getChangeOpFactoryProvider();
			return new Wikibase\Repo\Api\EditEntity(
				$mainModule,
				$moduleName,
				$wikibaseRepo->getTermsLanguages(),
				$wikibaseRepo->getEntityRevisionLookup( 'uncached' ),
				$wikibaseRepo->getEntityIdParser(),
				$wikibaseRepo->getEntityFactory(),
				$wikibaseRepo->getExternalFormatStatementDeserializer(),
				$wikibaseRepo->getDataTypeDefinitions()->getTypeIds(),
				$changeOpFactoryProvider->getFingerprintChangeOpFactory(),
				$changeOpFactoryProvider->getStatementChangeOpFactory(),
				$changeOpFactoryProvider->getSiteLinkChangeOpFactory(),
				$wikibaseRepo->getEntityChangeOpProvider()
			);
		}
	];
	$wgAPIModules['wblinktitles'] = [
		'class' => Wikibase\Repo\Api\LinkTitles::class,
		'factory' => function ( ApiMain $mainModule, $moduleName ) {
			$wikibaseRepo = Wikibase\Repo\WikibaseRepo::getDefaultInstance();
			$settings = $wikibaseRepo->getSettings();
			$apiHelperFactory = $wikibaseRepo->getApiHelperFactory( $mainModule->getContext() );

			$siteLinkTargetProvider = new \Wikibase\Repo\SiteLinkTargetProvider(
				$wikibaseRepo->getSiteLookup(),
				$settings->getSetting( 'specialSiteLinkGroups' )
			);

			return new Wikibase\Repo\Api\LinkTitles(
				$mainModule,
				$moduleName,
				$siteLinkTargetProvider,
				$apiHelperFactory->getErrorReporter( $mainModule ),
				$settings->getSetting( 'siteLinkGroups' ),
				$wikibaseRepo->getEntityRevisionLookup( 'uncached' ),
				function ( $module ) use ( $apiHelperFactory ) {
					return $apiHelperFactory->getResultBuilder( $module );
				},
				function ( $module ) use ( $apiHelperFactory ) {
					return $apiHelperFactory->getEntitySavingHelper( $module );
				}
			);
		}
	];
	$wgAPIModules['wbsetsitelink'] = [
		'class' => Wikibase\Repo\Api\SetSiteLink::class,
		'factory' => function ( ApiMain $mainModule, $moduleName ) {
			$wikibaseRepo = Wikibase\Repo\WikibaseRepo::getDefaultInstance();

			return new Wikibase\Repo\Api\SetSiteLink(
				$mainModule,
				$moduleName,
				$wikibaseRepo->getChangeOpFactoryProvider()
					->getSiteLinkChangeOpFactory(),
				$wikibaseRepo->getSiteLinkBadgeChangeOpSerializationValidator()
			);
		}
	];
	$wgAPIModules['wbcreateclaim'] = [
		'class' => Wikibase\Repo\Api\CreateClaim::class,
		'factory' => function ( ApiMain $mainModule, $moduleName ) {
			$wikibaseRepo = Wikibase\Repo\WikibaseRepo::getDefaultInstance();
			$changeOpFactoryProvider = $wikibaseRepo->getChangeOpFactoryProvider();
			$apiHelperFactory = $wikibaseRepo->getApiHelperFactory( $mainModule->getContext() );
			$errorReporter = $apiHelperFactory->getErrorReporter( $mainModule );

			$modificationHelper = new Wikibase\Repo\Api\StatementModificationHelper(
				$wikibaseRepo->getSnakFactory(),
				$wikibaseRepo->getEntityIdParser(),
				$wikibaseRepo->getStatementGuidValidator(),
				$errorReporter
			);

			return new Wikibase\Repo\Api\CreateClaim(
				$mainModule,
				$moduleName,
				$changeOpFactoryProvider->getStatementChangeOpFactory(),
				$errorReporter,
				$modificationHelper,
				function ( $module ) use ( $apiHelperFactory ) {
					return $apiHelperFactory->getResultBuilder( $module );
				},
				function ( $module ) use ( $apiHelperFactory ) {
					return $apiHelperFactory->getEntitySavingHelper( $module );
				}
			);
		}
	];
	$wgAPIModules['wbgetclaims'] = [
		'class' => Wikibase\Repo\Api\GetClaims::class,
		'factory' => function( ApiMain $mainModule, $moduleName ) {
			$wikibaseRepo = Wikibase\Repo\WikibaseRepo::getDefaultInstance();
			$apiHelperFactory = $wikibaseRepo->getApiHelperFactory( $mainModule->getContext() );

			return new Wikibase\Repo\Api\GetClaims(
				$mainModule,
				$moduleName,
				$wikibaseRepo->getStatementGuidValidator(),
				$wikibaseRepo->getStatementGuidParser(),
				$wikibaseRepo->getEntityIdParser(),
				$apiHelperFactory->getErrorReporter( $mainModule ),
				function ( $module ) use ( $apiHelperFactory ) {
					return $apiHelperFactory->getResultBuilder( $module );
				},
				function ( $module ) use ( $apiHelperFactory ) {
					return $apiHelperFactory->getEntityLoadingHelper( $module );
				}
			);
		}
	];
	$wgAPIModules['wbremoveclaims'] = [
		'class' => Wikibase\Repo\Api\RemoveClaims::class,
		'factory' => function( ApiMain $mainModule, $moduleName ) {
			$wikibaseRepo = Wikibase\Repo\WikibaseRepo::getDefaultInstance();
			$apiHelperFactory = $wikibaseRepo->getApiHelperFactory( $mainModule->getContext() );
			$changeOpFactoryProvider = $wikibaseRepo->getChangeOpFactoryProvider();

			$modificationHelper = new Wikibase\Repo\Api\StatementModificationHelper(
				$wikibaseRepo->getSnakFactory(),
				$wikibaseRepo->getEntityIdParser(),
				$wikibaseRepo->getStatementGuidValidator(),
				$apiHelperFactory->getErrorReporter( $mainModule )
			);

			return new Wikibase\Repo\Api\RemoveClaims(
				$mainModule,
				$moduleName,
				$apiHelperFactory->getErrorReporter( $mainModule ),
				$changeOpFactoryProvider->getStatementChangeOpFactory(),
				$modificationHelper,
				$wikibaseRepo->getStatementGuidParser(),
				function ( $module ) use ( $apiHelperFactory ) {
					return $apiHelperFactory->getResultBuilder( $module );
				},
				function ( $module ) use ( $apiHelperFactory ) {
					return $apiHelperFactory->getEntitySavingHelper( $module );
				}
			);
		}
	];
	$wgAPIModules['wbsetclaimvalue'] = [
		'class' => Wikibase\Repo\Api\SetClaimValue::class,
		'factory' => function( ApiMain $mainModule, $moduleName ) {
			$wikibaseRepo = Wikibase\Repo\WikibaseRepo::getDefaultInstance();
			$apiHelperFactory = $wikibaseRepo->getApiHelperFactory( $mainModule->getContext() );
			$changeOpFactoryProvider = $wikibaseRepo->getChangeOpFactoryProvider();

			$modificationHelper = new Wikibase\Repo\Api\StatementModificationHelper(
				$wikibaseRepo->getSnakFactory(),
				$wikibaseRepo->getEntityIdParser(),
				$wikibaseRepo->getStatementGuidValidator(),
				$apiHelperFactory->getErrorReporter( $mainModule )
			);

			return new Wikibase\Repo\Api\SetClaimValue(
				$mainModule,
				$moduleName,
				$apiHelperFactory->getErrorReporter( $mainModule ),
				$changeOpFactoryProvider->getStatementChangeOpFactory(),
				$modificationHelper,
				$wikibaseRepo->getStatementGuidParser(),
				function ( $module ) use ( $apiHelperFactory ) {
					return $apiHelperFactory->getResultBuilder( $module );
				},
				function ( $module ) use ( $apiHelperFactory ) {
					return $apiHelperFactory->getEntitySavingHelper( $module );
				}
			);
		}
	];
	$wgAPIModules['wbsetreference'] = [
		'class' => Wikibase\Repo\Api\SetReference::class,
		'factory' => function( ApiMain $mainModule, $moduleName ) {
			$wikibaseRepo = Wikibase\Repo\WikibaseRepo::getDefaultInstance();
			$apiHelperFactory = $wikibaseRepo->getApiHelperFactory( $mainModule->getContext() );
			$changeOpFactoryProvider = $wikibaseRepo->getChangeOpFactoryProvider();

			$modificationHelper = new Wikibase\Repo\Api\StatementModificationHelper(
				$wikibaseRepo->getSnakFactory(),
				$wikibaseRepo->getEntityIdParser(),
				$wikibaseRepo->getStatementGuidValidator(),
				$apiHelperFactory->getErrorReporter( $mainModule )
			);

			return new Wikibase\Repo\Api\SetReference(
				$mainModule,
				$moduleName,
				$wikibaseRepo->getBaseDataModelDeserializerFactory(),
				$apiHelperFactory->getErrorReporter( $mainModule ),
				$changeOpFactoryProvider->getStatementChangeOpFactory(),
				$modificationHelper,
				$wikibaseRepo->getStatementGuidParser(),
				function ( $module ) use ( $apiHelperFactory ) {
					return $apiHelperFactory->getResultBuilder( $module );
				},
				function ( $module ) use ( $apiHelperFactory ) {
					return $apiHelperFactory->getEntitySavingHelper( $module );
				}
			);
		}
	];
	$wgAPIModules['wbremovereferences'] = [
		'class' => Wikibase\Repo\Api\RemoveReferences::class,
		'factory' => function( ApiMain $mainModule, $moduleName ) {
			$wikibaseRepo = Wikibase\Repo\WikibaseRepo::getDefaultInstance();
			$apiHelperFactory = $wikibaseRepo->getApiHelperFactory( $mainModule->getContext() );
			$changeOpFactoryProvider = $wikibaseRepo->getChangeOpFactoryProvider();

			$modificationHelper = new Wikibase\Repo\Api\StatementModificationHelper(
				$wikibaseRepo->getSnakFactory(),
				$wikibaseRepo->getEntityIdParser(),
				$wikibaseRepo->getStatementGuidValidator(),
				$apiHelperFactory->getErrorReporter( $mainModule )
			);

			return new Wikibase\Repo\Api\RemoveReferences(
				$mainModule,
				$moduleName,
				$apiHelperFactory->getErrorReporter( $mainModule ),
				$changeOpFactoryProvider->getStatementChangeOpFactory(),
				$modificationHelper,
				$wikibaseRepo->getStatementGuidParser(),
				function ( $module ) use ( $apiHelperFactory ) {
					return $apiHelperFactory->getResultBuilder( $module );
				},
				function ( $module ) use ( $apiHelperFactory ) {
					return $apiHelperFactory->getEntitySavingHelper( $module );
				}
			);
		}
	];
	$wgAPIModules['wbsetclaim'] = [
		'class' => Wikibase\Repo\Api\SetClaim::class,
		'factory' => function( ApiMain $mainModule, $moduleName ) {
			$wikibaseRepo = Wikibase\Repo\WikibaseRepo::getDefaultInstance();
			$apiHelperFactory = $wikibaseRepo->getApiHelperFactory( $mainModule->getContext() );
			$changeOpFactoryProvider = $wikibaseRepo->getChangeOpFactoryProvider();

			$modificationHelper = new Wikibase\Repo\Api\StatementModificationHelper(
				$wikibaseRepo->getSnakFactory(),
				$wikibaseRepo->getEntityIdParser(),
				$wikibaseRepo->getStatementGuidValidator(),
				$apiHelperFactory->getErrorReporter( $mainModule )
			);

			return new Wikibase\Repo\Api\SetClaim(
				$mainModule,
				$moduleName,
				$apiHelperFactory->getErrorReporter( $mainModule ),
				$wikibaseRepo->getExternalFormatStatementDeserializer(),
				$changeOpFactoryProvider->getStatementChangeOpFactory(),
				$modificationHelper,
				$wikibaseRepo->getStatementGuidParser(),
				function ( $module ) use ( $apiHelperFactory ) {
					return $apiHelperFactory->getResultBuilder( $module );
				},
				function ( $module ) use ( $apiHelperFactory ) {
					return $apiHelperFactory->getEntitySavingHelper( $module );
				}
			);
		}
	];
	$wgAPIModules['wbremovequalifiers'] = [
		'class' => Wikibase\Repo\Api\RemoveQualifiers::class,
		'factory' => function( ApiMain $mainModule, $moduleName ) {
			$wikibaseRepo = Wikibase\Repo\WikibaseRepo::getDefaultInstance();
			$apiHelperFactory = $wikibaseRepo->getApiHelperFactory( $mainModule->getContext() );
			$changeOpFactoryProvider = $wikibaseRepo->getChangeOpFactoryProvider();

			$modificationHelper = new Wikibase\Repo\Api\StatementModificationHelper(
				$wikibaseRepo->getSnakFactory(),
				$wikibaseRepo->getEntityIdParser(),
				$wikibaseRepo->getStatementGuidValidator(),
				$apiHelperFactory->getErrorReporter( $mainModule )
			);

			return new Wikibase\Repo\Api\RemoveQualifiers(
				$mainModule,
				$moduleName,
				$apiHelperFactory->getErrorReporter( $mainModule ),
				$changeOpFactoryProvider->getStatementChangeOpFactory(),
				$modificationHelper,
				$wikibaseRepo->getStatementGuidParser(),
				function ( $module ) use ( $apiHelperFactory ) {
					return $apiHelperFactory->getResultBuilder( $module );
				},
				function ( $module ) use ( $apiHelperFactory ) {
					return $apiHelperFactory->getEntitySavingHelper( $module );
				}
			);
		}
	];
	$wgAPIModules['wbsetqualifier'] = [
		'class' => Wikibase\Repo\Api\SetQualifier::class,
		'factory' => function( ApiMain $mainModule, $moduleName ) {
			$wikibaseRepo = \Wikibase\Repo\WikibaseRepo::getDefaultInstance();
			$apiHelperFactory = $wikibaseRepo->getApiHelperFactory( $mainModule->getContext() );
			$changeOpFactoryProvider = $wikibaseRepo->getChangeOpFactoryProvider();

			$modificationHelper = new \Wikibase\Repo\Api\StatementModificationHelper(
				$wikibaseRepo->getSnakFactory(),
				$wikibaseRepo->getEntityIdParser(),
				$wikibaseRepo->getStatementGuidValidator(),
				$apiHelperFactory->getErrorReporter( $mainModule )
			);

			return new Wikibase\Repo\Api\SetQualifier(
				$mainModule,
				$moduleName,
				function ( $module ) use ( $apiHelperFactory ) {
					return $apiHelperFactory->getErrorReporter( $module );
				},
				$changeOpFactoryProvider->getStatementChangeOpFactory(),
				$modificationHelper,
				$wikibaseRepo->getStatementGuidParser(),
				function ( $module ) use ( $apiHelperFactory ) {
					return $apiHelperFactory->getResultBuilder( $module );
				},
				function ( $module ) use ( $apiHelperFactory ) {
					return $apiHelperFactory->getEntitySavingHelper( $module );
				}
			);
		}
	];
	$wgAPIModules['wbmergeitems'] = [
		'class' => Wikibase\Repo\Api\MergeItems::class,
		'factory' => function( ApiMain $mainModule, $moduleName ) {
			$wikibaseRepo = \Wikibase\Repo\WikibaseRepo::getDefaultInstance();
			$apiHelperFactory = $wikibaseRepo->getApiHelperFactory( $mainModule->getContext() );

			return new Wikibase\Repo\Api\MergeItems(
				$mainModule,
				$moduleName,
				$wikibaseRepo->getEntityIdParser(),
				$wikibaseRepo->newItemMergeInteractor( $mainModule->getContext() ),
				$apiHelperFactory->getErrorReporter( $mainModule ),
				function ( $module ) use ( $apiHelperFactory ) {
					return $apiHelperFactory->getResultBuilder( $module );
				}
			);
		}
	];
	$wgAPIModules['wbformatvalue'] = [
		'class' => Wikibase\Repo\Api\FormatSnakValue::class,
		'factory' => function( ApiMain $mainModule, $moduleName ) {
			$wikibaseRepo = Wikibase\Repo\WikibaseRepo::getDefaultInstance();
			$apiHelperFactory = $wikibaseRepo->getApiHelperFactory( $mainModule->getContext() );

			return new Wikibase\Repo\Api\FormatSnakValue(
				$mainModule,
				$moduleName,
				$wikibaseRepo->getValueFormatterFactory(),
				$wikibaseRepo->getSnakFormatterFactory(),
				$wikibaseRepo->getDataValueFactory(),
				$apiHelperFactory->getErrorReporter( $mainModule )
			);
		}
	];
	$wgAPIModules['wbparsevalue'] = [
		'class' => Wikibase\Repo\Api\ParseValue::class,
		'factory' => function( ApiMain $mainModule, $moduleName ) {
			$wikibaseRepo = Wikibase\Repo\WikibaseRepo::getDefaultInstance();
			$apiHelperFactory = $wikibaseRepo->getApiHelperFactory( $mainModule->getContext() );

			return new Wikibase\Repo\Api\ParseValue(
				$mainModule,
				$moduleName,
				$wikibaseRepo->getDataTypeFactory(),
				$wikibaseRepo->getValueParserFactory(),
				$wikibaseRepo->getDataTypeValidatorFactory(),
				$wikibaseRepo->getExceptionLocalizer(),
				$wikibaseRepo->getValidatorErrorLocalizer(),
				$apiHelperFactory->getErrorReporter( $mainModule )
			);
		}
	];
	$wgAPIModules['wbavailablebadges'] = Wikibase\Repo\Api\AvailableBadges::class;
	$wgAPIModules['wbcreateredirect'] = [
		'class' => Wikibase\Repo\Api\CreateRedirect::class,
		'factory' => function( ApiMain $apiMain, $moduleName ) {
			$wikibaseRepo = \Wikibase\Repo\WikibaseRepo::getDefaultInstance();
			$apiHelperFactory = $wikibaseRepo->getApiHelperFactory( $apiMain->getContext() );
			return new Wikibase\Repo\Api\CreateRedirect(
				$apiMain,
				$moduleName,
				$wikibaseRepo->getEntityIdParser(),
				$apiHelperFactory->getErrorReporter( $apiMain ),
				$wikibaseRepo->newRedirectCreationInteractor( $apiMain->getUser(), $apiMain->getContext() )
			);
		}
	];
	$wgAPIListModules['wbsearch'] = [
		'class' => Wikibase\Repo\Api\QuerySearchEntities::class,
		'factory' => function( ApiQuery $apiQuery, $moduleName ) {
			$repo = Wikibase\Repo\WikibaseRepo::getDefaultInstance();

			return new Wikibase\Repo\Api\QuerySearchEntities(
				$apiQuery,
				$moduleName,
				new Wikibase\Repo\Api\TypeDispatchingEntitySearchHelper(
					$repo->getEntitySearchHelperCallbacks(),
					$apiQuery->getRequest()
				),
				$repo->getEntityTitleLookup(),
				$repo->getTermsLanguages(),
				$repo->getEnabledEntityTypes()
			);
		}
	];
	$wgAPIListModules['wbsubscribers'] = [
		'class' => Wikibase\Repo\Api\ListSubscribers::class,
		'factory' => function( ApiQuery $apiQuery, $moduleName ) {
			$wikibaseRepo = Wikibase\Repo\WikibaseRepo::getDefaultInstance();
			$mediaWikiServices = MediaWiki\MediaWikiServices::getInstance();
			$apiHelper = $wikibaseRepo->getApiHelperFactory( $apiQuery->getContext() );
			return new Wikibase\Repo\Api\ListSubscribers(
				$apiQuery,
				$moduleName,
				$apiHelper->getErrorReporter( $apiQuery ),
				$wikibaseRepo->getEntityIdParser(),
				$mediaWikiServices->getSiteLookup()
			);
		}
	];

	// Special page registration
	$wgSpecialPages['NewItem'] = function () {
		$wikibaseRepo = Wikibase\Repo\WikibaseRepo::getDefaultInstance();

		$settings = $wikibaseRepo->getSettings();
		$copyrightView = new Wikibase\Repo\Specials\SpecialPageCopyrightView(
			new Wikibase\CopyrightMessageBuilder(),
			$settings->getSetting( 'dataRightsUrl' ),
			$settings->getSetting( 'dataRightsText' )
		);

		return new Wikibase\Repo\Specials\SpecialNewItem(
			$copyrightView,
			$wikibaseRepo->getEntityNamespaceLookup(),
			$wikibaseRepo->getSummaryFormatter(),
			$wikibaseRepo->getEntityTitleLookup(),
			$wikibaseRepo->newEditEntityFactory(),
			$wikibaseRepo->getSiteLookup(),
			$wikibaseRepo->getTermValidatorFactory()
		);
	};
	$wgSpecialPages['NewProperty'] = function () {
		$wikibaseRepo = Wikibase\Repo\WikibaseRepo::getDefaultInstance();

		$settings = $wikibaseRepo->getSettings();
		$copyrightView = new Wikibase\Repo\Specials\SpecialPageCopyrightView(
			new Wikibase\CopyrightMessageBuilder(),
			$settings->getSetting( 'dataRightsUrl' ),
			$settings->getSetting( 'dataRightsText' )
		);

		return new Wikibase\Repo\Specials\SpecialNewProperty(
			$copyrightView,
			$wikibaseRepo->getEntityNamespaceLookup(),
			$wikibaseRepo->getSummaryFormatter(),
			$wikibaseRepo->getEntityTitleLookup(),
			$wikibaseRepo->newEditEntityFactory()
		);
	};
	$wgSpecialPages['ItemByTitle'] = function () {
		$wikibaseRepo = Wikibase\Repo\WikibaseRepo::getDefaultInstance();

		$siteLinkTargetProvider = new Wikibase\Repo\SiteLinkTargetProvider(
			$wikibaseRepo->getSiteLookup(),
			$wikibaseRepo->getSettings()->getSetting( 'specialSiteLinkGroups' )
		);

		return new Wikibase\Repo\Specials\SpecialItemByTitle(
			$wikibaseRepo->getEntityTitleLookup(),
			new Wikibase\Lib\LanguageNameLookup(),
			$wikibaseRepo->getSiteLookup(),
			$wikibaseRepo->getStore()->newSiteLinkStore(),
			$siteLinkTargetProvider,
			$wikibaseRepo->getSettings()->getSetting( 'siteLinkGroups' )
		);
	};
	$wgSpecialPages['GoToLinkedPage'] = function() {
		$wikibaseRepo = Wikibase\Repo\WikibaseRepo::getDefaultInstance();
		return new Wikibase\Repo\Specials\SpecialGoToLinkedPage(
			$wikibaseRepo->getSiteLookup(),
			$wikibaseRepo->getStore()->newSiteLinkStore(),
			$wikibaseRepo->getStore()->getEntityRedirectLookup(),
			$wikibaseRepo->getEntityIdParser(),
			$wikibaseRepo->getStore()->getEntityLookup()
		);
	};
	$wgSpecialPages['ItemDisambiguation'] = function() {
		global $wgLang;

		$languageCode = $wgLang->getCode();
		$wikibaseRepo = Wikibase\Repo\WikibaseRepo::getDefaultInstance();
		$languageNameLookup = new Wikibase\Lib\LanguageNameLookup( $languageCode );
		$itemDisambiguation = new Wikibase\ItemDisambiguation(
			$wikibaseRepo->getEntityTitleLookup(),
			$languageNameLookup,
			$languageCode
		);
		return new Wikibase\Repo\Specials\SpecialItemDisambiguation(
			new Wikibase\Lib\MediaWikiContentLanguages(),
			$languageNameLookup,
			$itemDisambiguation,
			new Wikibase\Repo\Api\TypeDispatchingEntitySearchHelper(
				$wikibaseRepo->getEntitySearchHelperCallbacks(),
				RequestContext::getMain()->getRequest()
			)
		);
	};
	$wgSpecialPages['ItemsWithoutSitelinks']
		= Wikibase\Repo\Specials\SpecialItemsWithoutSitelinks::class;
	$wgSpecialPages['SetLabel'] = function() {
		$wikibaseRepo = Wikibase\Repo\WikibaseRepo::getDefaultInstance();

		$settings = $wikibaseRepo->getSettings();
		$copyrightView = new Wikibase\Repo\Specials\SpecialPageCopyrightView(
			new Wikibase\CopyrightMessageBuilder(),
			$settings->getSetting( 'dataRightsUrl' ),
			$settings->getSetting( 'dataRightsText' )
		);

		return new Wikibase\Repo\Specials\SpecialSetLabel(
			$copyrightView,
			$wikibaseRepo->getSummaryFormatter(),
			$wikibaseRepo->getEntityTitleLookup(),
			$wikibaseRepo->newEditEntityFactory(),
			$wikibaseRepo->getEntityPermissionChecker()
		);
	};
	$wgSpecialPages['SetDescription'] = function() {
		$wikibaseRepo = Wikibase\Repo\WikibaseRepo::getDefaultInstance();

		$settings = $wikibaseRepo->getSettings();
		$copyrightView = new Wikibase\Repo\Specials\SpecialPageCopyrightView(
			new Wikibase\CopyrightMessageBuilder(),
			$settings->getSetting( 'dataRightsUrl' ),
			$settings->getSetting( 'dataRightsText' )
		);

		return new Wikibase\Repo\Specials\SpecialSetDescription(
			$copyrightView,
			$wikibaseRepo->getSummaryFormatter(),
			$wikibaseRepo->getEntityTitleLookup(),
			$wikibaseRepo->newEditEntityFactory(),
			$wikibaseRepo->getEntityPermissionChecker()
		);
	};
	$wgSpecialPages['SetAliases'] = function() {
		$wikibaseRepo = Wikibase\Repo\WikibaseRepo::getDefaultInstance();

		$settings = $wikibaseRepo->getSettings();
		$copyrightView = new Wikibase\Repo\Specials\SpecialPageCopyrightView(
			new Wikibase\CopyrightMessageBuilder(),
			$settings->getSetting( 'dataRightsUrl' ),
			$settings->getSetting( 'dataRightsText' )
		);

		return new Wikibase\Repo\Specials\SpecialSetAliases(
			$copyrightView,
			$wikibaseRepo->getSummaryFormatter(),
			$wikibaseRepo->getEntityTitleLookup(),
			$wikibaseRepo->newEditEntityFactory(),
			$wikibaseRepo->getEntityPermissionChecker()
		);
	};
	$wgSpecialPages['SetLabelDescriptionAliases'] = function() {
		$wikibaseRepo = Wikibase\Repo\WikibaseRepo::getDefaultInstance();

		$settings = $wikibaseRepo->getSettings();
		$copyrightView = new Wikibase\Repo\Specials\SpecialPageCopyrightView(
			new Wikibase\CopyrightMessageBuilder(),
			$settings->getSetting( 'dataRightsUrl' ),
			$settings->getSetting( 'dataRightsText' )
		);

		return new Wikibase\Repo\Specials\SpecialSetLabelDescriptionAliases(
			$copyrightView,
			$wikibaseRepo->getSummaryFormatter(),
			$wikibaseRepo->getEntityTitleLookup(),
			$wikibaseRepo->newEditEntityFactory(),
			$wikibaseRepo->getChangeOpFactoryProvider()->getFingerprintChangeOpFactory(),
			$wikibaseRepo->getTermsLanguages(),
			$wikibaseRepo->getEntityPermissionChecker()
		);
	};
	$wgSpecialPages['SetSiteLink'] = function() {
		$wikibaseRepo = Wikibase\Repo\WikibaseRepo::getDefaultInstance();
		$siteLookup = $wikibaseRepo->getSiteLookup();
		$settings = $wikibaseRepo->getSettings();

		$siteLinkChangeOpFactory = $wikibaseRepo->getChangeOpFactoryProvider()->getSiteLinkChangeOpFactory();
		$siteLinkTargetProvider = new Wikibase\Repo\SiteLinkTargetProvider(
			$siteLookup,
			$settings->getSetting( 'specialSiteLinkGroups' )
		);

		$copyrightView = new Wikibase\Repo\Specials\SpecialPageCopyrightView(
			new Wikibase\CopyrightMessageBuilder(),
			$settings->getSetting( 'dataRightsUrl' ),
			$settings->getSetting( 'dataRightsText' )
		);

		$labelDescriptionLookupFactory = $wikibaseRepo->getLanguageFallbackLabelDescriptionLookupFactory();
		return new Wikibase\Repo\Specials\SpecialSetSiteLink(
			$copyrightView,
			$wikibaseRepo->getSummaryFormatter(),
			$wikibaseRepo->getEntityTitleLookup(),
			$wikibaseRepo->newEditEntityFactory(),
			$siteLookup,
			$siteLinkTargetProvider,
			$settings->getSetting( 'siteLinkGroups' ),
			$settings->getSetting( 'badgeItems' ),
			$labelDescriptionLookupFactory,
			$siteLinkChangeOpFactory
		);
	};
	$wgSpecialPages['EntitiesWithoutLabel'] = [
		Wikibase\Repo\Specials\SpecialEntitiesWithoutPageFactory::class,
		'newSpecialEntitiesWithoutLabel'
	];
	$wgSpecialPages['EntitiesWithoutDescription'] = [
		Wikibase\Repo\Specials\SpecialEntitiesWithoutPageFactory::class,
		'newSpecialEntitiesWithoutDescription'
	];
	$wgSpecialPages['ListDatatypes'] = Wikibase\Repo\Specials\SpecialListDatatypes::class;
	$wgSpecialPages['ListProperties'] = function () {
		$wikibaseRepo = Wikibase\Repo\WikibaseRepo::getDefaultInstance();
		$prefetchingTermLookup = $wikibaseRepo->getPrefetchingTermLookup();
		$labelDescriptionLookup = new Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookup(
			$prefetchingTermLookup,
			$wikibaseRepo->getLanguageFallbackChainFactory()
				->newFromLanguage( $wikibaseRepo->getUserLanguage() )
		);
		$entityIdFormatter = $wikibaseRepo->getEntityIdHtmlLinkFormatterFactory()
			->getEntityIdFormatter( $labelDescriptionLookup );
		return new Wikibase\Repo\Specials\SpecialListProperties(
			$wikibaseRepo->getDataTypeFactory(),
			$wikibaseRepo->getStore()->getPropertyInfoLookup(),
			$labelDescriptionLookup,
			$entityIdFormatter,
			$wikibaseRepo->getEntityTitleLookup(),
			$prefetchingTermLookup
		);
	};
	$wgSpecialPages['DispatchStats'] = Wikibase\Repo\Specials\SpecialDispatchStats::class;
	$wgSpecialPages['EntityData'] = Wikibase\Repo\Specials\SpecialEntityData::class;
	$wgSpecialPages['EntityPage'] = function() {
		return new Wikibase\Repo\Specials\SpecialEntityPage(
			Wikibase\Repo\WikibaseRepo::getDefaultInstance()->getEntityIdParser(),
			Wikibase\Repo\WikibaseRepo::getDefaultInstance()->getEntityContentFactory()
		);
	};
	$wgSpecialPages['MyLanguageFallbackChain'] = function() {
		return new Wikibase\Repo\Specials\SpecialMyLanguageFallbackChain(
			\Wikibase\Repo\WikibaseRepo::getDefaultInstance()->getLanguageFallbackChainFactory()
		);
	};
	$wgSpecialPages['MergeItems'] = function() {
		global $wgUser;

		$wikibaseRepo = \Wikibase\Repo\WikibaseRepo::getDefaultInstance();

		return new Wikibase\Repo\Specials\SpecialMergeItems(
			$wikibaseRepo->getEntityIdParser(),
			$wikibaseRepo->getExceptionLocalizer(),
			new \Wikibase\Repo\Interactors\TokenCheckInteractor( $wgUser ),
			$wikibaseRepo->newItemMergeInteractor( RequestContext::getMain() ),
			$wikibaseRepo->getEntityTitleLookup()
		);
	};
	$wgSpecialPages['RedirectEntity'] = function() {
		global $wgUser;

		$wikibaseRepo = Wikibase\Repo\WikibaseRepo::getDefaultInstance();

		return new Wikibase\Repo\Specials\SpecialRedirectEntity(
			$wikibaseRepo->getEntityIdParser(),
			$wikibaseRepo->getExceptionLocalizer(),
			new Wikibase\Repo\Interactors\TokenCheckInteractor(
				$wgUser
			),
			$wikibaseRepo->newRedirectCreationInteractor(
				$wgUser,
				RequestContext::getMain()
			)
		);
	};
	$wgSpecialPages['AvailableBadges'] = function() {
		$wikibaseRepo = Wikibase\Repo\WikibaseRepo::getDefaultInstance();

		return new Wikibase\Repo\Specials\SpecialAvailableBadges(
			$wikibaseRepo->getPrefetchingTermLookup(),
			$wikibaseRepo->getEntityTitleLookup(),
			$wikibaseRepo->getSettings()->getSetting( 'badgeItems' )
		);
	};

	// Jobs
	$wgJobClasses['UpdateRepoOnMove'] = Wikibase\Repo\UpdateRepo\UpdateRepoOnMoveJob::class;
	$wgJobClasses['UpdateRepoOnDelete'] = Wikibase\Repo\UpdateRepo\UpdateRepoOnDeleteJob::class;

	// Hooks
	$wgHooks['BeforePageDisplay'][] = 'Wikibase\RepoHooks::onBeforePageDisplay';
	$wgHooks['LoadExtensionSchemaUpdates'][] = 'Wikibase\Repo\Store\Sql\DatabaseSchemaUpdater::onSchemaUpdate';
	$wgHooks['UnitTestsList'][] = 'Wikibase\RepoHooks::registerUnitTests';
	$wgHooks['ResourceLoaderTestModules'][] = 'Wikibase\RepoHooks::registerQUnitTests';

	$wgHooks['NamespaceIsMovable'][] = 'Wikibase\RepoHooks::onNamespaceIsMovable';
	$wgHooks['NewRevisionFromEditComplete'][] = 'Wikibase\RepoHooks::onNewRevisionFromEditComplete';
	$wgHooks['SkinTemplateNavigation'][] = 'Wikibase\RepoHooks::onPageTabs';
	$wgHooks['RecentChange_save'][] = 'Wikibase\RepoHooks::onRecentChangeSave';
	$wgHooks['ArticleDeleteComplete'][] = 'Wikibase\RepoHooks::onArticleDeleteComplete';
	$wgHooks['ArticleUndelete'][] = 'Wikibase\RepoHooks::onArticleUndelete';
	$wgHooks['GetPreferences'][] = 'Wikibase\RepoHooks::onGetPreferences';
	$wgHooks['HtmlPageLinkRendererBegin'][] = 'Wikibase\Repo\Hooks\HtmlPageLinkRendererBeginHookHandler::onHtmlPageLinkRendererBegin';
	$wgHooks['ChangesListInitRows'][] = 'Wikibase\Repo\Hooks\LabelPrefetchHookHandlers::onChangesListInitRows';
	$wgHooks['OutputPageBodyAttributes'][] = 'Wikibase\RepoHooks::onOutputPageBodyAttributes';
	$wgHooks['FormatAutocomments'][] = 'Wikibase\RepoHooks::onFormat';
	$wgHooks['PageHistoryLineEnding'][] = 'Wikibase\RepoHooks::onPageHistoryLineEnding';
	$wgHooks['ApiCheckCanExecute'][] = 'Wikibase\RepoHooks::onApiCheckCanExecute';
	$wgHooks['SetupAfterCache'][] = 'Wikibase\RepoHooks::onSetupAfterCache';
	$wgHooks['ShowSearchHit'][] = 'Wikibase\Repo\Hooks\ShowSearchHitHandler::onShowSearchHit';
	$wgHooks['ShowSearchHitTitle'][] = 'Wikibase\Repo\Hooks\ShowSearchHitHandler::onShowSearchHitTitle';
	$wgHooks['TitleGetRestrictionTypes'][] = 'Wikibase\RepoHooks::onTitleGetRestrictionTypes';
	$wgHooks['AbuseFilter-contentToString'][] = 'Wikibase\RepoHooks::onAbuseFilterContentToString';
	$wgHooks['SpecialPage_reorderPages'][] = 'Wikibase\RepoHooks::onSpecialPageReorderPages';
	$wgHooks['OutputPageParserOutput'][] = 'Wikibase\RepoHooks::onOutputPageParserOutput';
	$wgHooks['ContentModelCanBeUsedOn'][] = 'Wikibase\RepoHooks::onContentModelCanBeUsedOn';
	$wgHooks['OutputPageBeforeHTML'][] = 'Wikibase\Repo\Hooks\OutputPageBeforeHTMLHookHandler::onOutputPageBeforeHTML';
	$wgHooks['OutputPageBeforeHTML'][] = 'Wikibase\Repo\Hooks\OutputPageJsConfigHookHandler::onOutputPageBeforeHtmlRegisterConfig';
	$wgHooks['APIQuerySiteInfoGeneralInfo'][] = 'Wikibase\RepoHooks::onAPIQuerySiteInfoGeneralInfo';
	$wgHooks['APIQuerySiteInfoStatisticsInfo'][] = 'Wikibase\RepoHooks::onAPIQuerySiteInfoStatisticsInfo';
	$wgHooks['ImportHandleRevisionXMLTag'][] = 'Wikibase\RepoHooks::onImportHandleRevisionXMLTag';
	$wgHooks['BaseTemplateToolbox'][] = 'Wikibase\RepoHooks::onBaseTemplateToolbox';
	$wgHooks['SkinTemplateBuildNavUrlsNav_urlsAfterPermalink'][] = 'Wikibase\RepoHooks::onSkinTemplateBuildNavUrlsNavUrlsAfterPermalink';
	$wgHooks['ResourceLoaderRegisterModules'][] = 'Wikibase\RepoHooks::onResourceLoaderRegisterModules';
	$wgHooks['BeforeDisplayNoArticleText'][] = 'Wikibase\ViewEntityAction::onBeforeDisplayNoArticleText';
	$wgHooks['InfoAction'][] = '\Wikibase\RepoHooks::onInfoAction';
	$wgHooks['ParserOutputPostCacheTransform'][] = '\Wikibase\RepoHooks::onParserOutputPostCacheTransform';
	$wgHooks['BeforePageDisplayMobile'][] = '\Wikibase\RepoHooks::onBeforePageDisplayMobile';
	$wgHooks['CirrusSearchAnalysisConfig'][] = '\Wikibase\RepoHooks::onCirrusSearchAnalysisConfig';
	$wgHooks['CirrusSearchProfileService'][] = '\Wikibase\RepoHooks::onCirrusSearchProfileService';
	$wgHooks['CirrusSearchFulltextQueryBuilderComplete'][] = '\Wikibase\RepoHooks::onCirrusSearchFulltextQueryBuilderComplete';
	$wgHooks['CirrusSearchAddQueryFeatures'][] = '\Wikibase\RepoHooks::onCirrusSearchAddQueryFeatures';
	$wgHooks['ApiMaxLagInfo'][] = '\Wikibase\RepoHooks::onApiMaxLagInfo';
	$wgHooks['ParserOptionsRegister'][] = '\Wikibase\RepoHooks::onParserOptionsRegister';

	// update hooks
	$wgHooks['LoadExtensionSchemaUpdates'][] = '\Wikibase\Repo\Store\Sql\ChangesSubscriptionSchemaUpdater::onSchemaUpdate';

	// Resource Loader Modules:
	$wgResourceModules = array_merge(
		$wgResourceModules,
		include __DIR__ . '/resources/Resources.php'
	);

	$wgWBRepoSettings = array_merge(
		require __DIR__ . '/../lib/config/WikibaseLib.default.php',
		require __DIR__ . '/config/Wikibase.default.php'
	);
} );
