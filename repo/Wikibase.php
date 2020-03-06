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
 * out more at https://wikiba.se               \/        \/
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

use MediaWiki\MediaWikiServices;
use Wikibase\CopyrightMessageBuilder;
use Wikibase\ItemDisambiguation;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Lib\MediaWikiContentLanguages;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookup;
use Wikibase\Repo\Api\AvailableBadges;
use Wikibase\Repo\Api\CreateClaim;
use Wikibase\Repo\Api\CreateRedirect;
use Wikibase\Repo\Api\EditEntity;
use Wikibase\Repo\Api\EditSummaryHelper;
use Wikibase\Repo\Api\FormatEntities;
use Wikibase\Repo\Api\FormatSnakValue;
use Wikibase\Repo\Api\GetClaims;
use Wikibase\Repo\Api\GetEntities;
use Wikibase\Repo\Api\LinkTitles;
use Wikibase\Repo\Api\ListSubscribers;
use Wikibase\Repo\Api\MergeItems;
use Wikibase\Repo\Api\MetaContentLanguages;
use Wikibase\Repo\Api\ParseValue;
use Wikibase\Repo\Api\QuerySearchEntities;
use Wikibase\Repo\Api\RemoveClaims;
use Wikibase\Repo\Api\RemoveQualifiers;
use Wikibase\Repo\Api\RemoveReferences;
use Wikibase\Repo\Api\SearchEntities;
use Wikibase\Repo\Api\SetAliases;
use Wikibase\Repo\Api\SetClaim;
use Wikibase\Repo\Api\SetClaimValue;
use Wikibase\Repo\Api\SetDescription;
use Wikibase\Repo\Api\SetLabel;
use Wikibase\Repo\Api\SetQualifier;
use Wikibase\Repo\Api\SetReference;
use Wikibase\Repo\Api\SetSiteLink;
use Wikibase\Repo\Api\StatementModificationHelper;
use Wikibase\Repo\Api\TypeDispatchingEntitySearchHelper;
use Wikibase\Repo\ChangeOp\ChangedLanguagesCollector;
use Wikibase\Repo\ChangeOp\ChangedLanguagesCounter;
use Wikibase\Repo\ChangeOp\NonLanguageBoundChangesCounter;
use Wikibase\Repo\Interactors\TokenCheckInteractor;
use Wikibase\Repo\SiteLinkTargetProvider;
use Wikibase\Repo\Specials\SpecialAvailableBadges;
use Wikibase\Repo\Specials\SpecialDispatchStats;
use Wikibase\Repo\Specials\SpecialEntityData;
use Wikibase\Repo\Specials\SpecialEntityPage;
use Wikibase\Repo\Specials\SpecialGoToLinkedPage;
use Wikibase\Repo\Specials\SpecialItemByTitle;
use Wikibase\Repo\Specials\SpecialItemDisambiguation;
use Wikibase\Repo\Specials\SpecialItemsWithoutSitelinks;
use Wikibase\Repo\Specials\SpecialListDatatypes;
use Wikibase\Repo\Specials\SpecialListProperties;
use Wikibase\Repo\Specials\SpecialMyLanguageFallbackChain;
use Wikibase\Repo\Specials\SpecialNewItem;
use Wikibase\Repo\Specials\SpecialNewProperty;
use Wikibase\Repo\Specials\SpecialPageCopyrightView;
use Wikibase\Repo\Specials\SpecialRedirectEntity;
use Wikibase\Repo\Specials\SpecialSetAliases;
use Wikibase\Repo\Specials\SpecialSetDescription;
use Wikibase\Repo\Specials\SpecialSetLabel;
use Wikibase\Repo\Specials\SpecialSetLabelDescriptionAliases;
use Wikibase\Repo\Specials\SpecialSetSiteLink;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Store;

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'Not an entry point.' );
}

define( 'WB_VERSION', '0.5 alpha' );

// load parts already converted to extension registration
wfLoadExtension( 'WikibaseRepository', __DIR__ . '/../extension-repo-wip.json' );

/**
 * Registry of ValueParsers classes or factory callbacks, by datatype.
 * @note: that parsers are also registered under their old names for backwards compatibility,
 * for use with the deprecated 'parser' parameter of the wbparsevalue API module.
 */
$GLOBALS['wgValueParsers'] = [];

// Sub-extensions needed by WikibaseRepository
require_once __DIR__ . '/../lib/WikibaseLib.php';
require_once __DIR__ . '/../view/WikibaseView.php';

// Load autoload info as long as extension classes are not PSR-4-autoloaded
require_once __DIR__ . '/autoload.php';

// Nasty hack: part of repo relies on classes defined in Client! load it if in repo-only mode
if ( !defined( 'WBC_VERSION' ) ) {
	global $wgAutoloadClasses;
	$wgAutoloadClasses['Wikibase\\Client\\Store\\TitleFactory'] = __DIR__ . '/../client/includes/Store/TitleFactory.php';
}

call_user_func( function() {
	global $wgAPIMetaModules,
		$wgAPIListModules,
		$wgAPIModules,
		$wgAvailableRights,
		$wgEventLoggingSchemas,
		$wgExtensionMessagesFiles,
		$wgGrantPermissions,
		$wgGroupPermissions,
		$wgHooks,
		$wgMessagesDirs,
		$wgResourceModules,
		$wgSpecialPages,
		$wgValueParsers,
		$wgWBRepoDataTypes,
		$wgWBRepoSettings;

	// Registry and definition of data types
	$wgWBRepoDataTypes = require __DIR__ . '/../lib/WikibaseLib.datatypes.php';

	$repoDataTypes = require __DIR__ . '/WikibaseRepo.datatypes.php';

	// merge WikibaseRepo.datatypes.php into $wgWBRepoDataTypes
	foreach ( $repoDataTypes as $type => $repoDef ) {
		$baseDef = $wgWBRepoDataTypes[$type] ?? [];
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
	$wgMessagesDirs['WikibaseApi'] = __DIR__ . '/i18n/api';
	$wgExtensionMessagesFiles['WikibaseAlias'] = __DIR__ . '/Wikibase.i18n.alias.php';
	$wgExtensionMessagesFiles['WikibaseNS'] = __DIR__ . '/Wikibase.i18n.namespaces.php';
	$wgExtensionMessagesFiles['wikibaserepomagic'] = __DIR__ . '/WikibaseRepo.i18n.magic.php';

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
		'class' => GetEntities::class,
		'factory' => function( ApiMain $apiMain, $moduleName ) {
			$wikibaseRepo = WikibaseRepo::getDefaultInstance();
			$settings = $wikibaseRepo->getSettings();
			$apiHelperFactory = $wikibaseRepo->getApiHelperFactory( $apiMain->getContext() );

			$siteLinkTargetProvider = new SiteLinkTargetProvider(
				$wikibaseRepo->getSiteLookup(),
				$settings->getSetting( 'specialSiteLinkGroups' )
			);

			return new GetEntities(
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
		'class' => SetLabel::class,
		'factory' => function ( ApiMain $mainModule, $moduleName ) {
			return new SetLabel(
				$mainModule,
				$moduleName,
				WikibaseRepo::getDefaultInstance()->getChangeOpFactoryProvider()
					->getFingerprintChangeOpFactory()
			);
		}
	];
	$wgAPIModules['wbsetdescription'] = [
		'class' => SetDescription::class,
		'factory' => function ( ApiMain $mainModule, $moduleName ) {
			return new SetDescription(
				$mainModule,
				$moduleName,
				WikibaseRepo::getDefaultInstance()->getChangeOpFactoryProvider()
					->getFingerprintChangeOpFactory()
			);
		}
	];
	$wgAPIModules['wbsearchentities'] = [
		'class' => SearchEntities::class,
		'factory' => function( ApiMain $mainModule, $moduleName ) {
			$repo = WikibaseRepo::getDefaultInstance();
			$entitySearchHelper = new Wikibase\Repo\Api\TypeDispatchingEntitySearchHelper(
				$repo->getEntitySearchHelperCallbacks(),
				$mainModule->getRequest()
			);

			return new SearchEntities(
				$mainModule,
				$moduleName,
				$entitySearchHelper,
				$repo->getEntityTitleLookup(),
				$repo->getTermsLanguages(),
				$repo->getEntitySourceDefinitions()
			);
		},
	];
	$wgAPIModules['wbsetaliases'] = [
		'class' => SetAliases::class,
		'factory' => function ( ApiMain $mainModule, $moduleName ) {
			return new SetAliases(
				$mainModule,
				$moduleName,
				WikibaseRepo::getDefaultInstance()->getChangeOpFactoryProvider()
					->getFingerprintChangeOpFactory()
			);
		}
	];
	$wgAPIModules['wbeditentity'] = [
		'class' => EditEntity::class,
		'factory' => function ( ApiMain $mainModule, $moduleName ) {
			$wikibaseRepo = WikibaseRepo::getDefaultInstance();
			$changeOpFactoryProvider = $wikibaseRepo->getChangeOpFactoryProvider();
			return new EditEntity(
				$mainModule,
				$moduleName,
				$wikibaseRepo->getTermsLanguages(),
				$wikibaseRepo->getEntityRevisionLookup( Store::LOOKUP_CACHING_DISABLED ),
				$wikibaseRepo->getEntityIdParser(),
				$wikibaseRepo->getEntityFactory(),
				$wikibaseRepo->getExternalFormatStatementDeserializer(),
				$wikibaseRepo->getDataTypeDefinitions()->getTypeIds(),
				$changeOpFactoryProvider->getFingerprintChangeOpFactory(),
				$changeOpFactoryProvider->getStatementChangeOpFactory(),
				$changeOpFactoryProvider->getSiteLinkChangeOpFactory(),
				$wikibaseRepo->getEntityChangeOpProvider(),
				new EditSummaryHelper(
					new ChangedLanguagesCollector(),
					new ChangedLanguagesCounter(),
					new NonLanguageBoundChangesCounter()
				)
			);
		}
	];
	$wgAPIModules['wblinktitles'] = [
		'class' => LinkTitles::class,
		'factory' => function ( ApiMain $mainModule, $moduleName ) {
			$wikibaseRepo = WikibaseRepo::getDefaultInstance();
			$settings = $wikibaseRepo->getSettings();
			$apiHelperFactory = $wikibaseRepo->getApiHelperFactory( $mainModule->getContext() );

			$siteLinkTargetProvider = new SiteLinkTargetProvider(
				$wikibaseRepo->getSiteLookup(),
				$settings->getSetting( 'specialSiteLinkGroups' )
			);

			return new LinkTitles(
				$mainModule,
				$moduleName,
				$siteLinkTargetProvider,
				$apiHelperFactory->getErrorReporter( $mainModule ),
				$settings->getSetting( 'siteLinkGroups' ),
				$wikibaseRepo->getEntityRevisionLookup( Store::LOOKUP_CACHING_DISABLED ),
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
		'class' => SetSiteLink::class,
		'factory' => function ( ApiMain $mainModule, $moduleName ) {
			$wikibaseRepo = WikibaseRepo::getDefaultInstance();

			return new SetSiteLink(
				$mainModule,
				$moduleName,
				$wikibaseRepo->getChangeOpFactoryProvider()
					->getSiteLinkChangeOpFactory(),
				$wikibaseRepo->getSiteLinkBadgeChangeOpSerializationValidator()
			);
		}
	];
	$wgAPIModules['wbcreateclaim'] = [
		'class' => CreateClaim::class,
		'factory' => function ( ApiMain $mainModule, $moduleName ) {
			$wikibaseRepo = WikibaseRepo::getDefaultInstance();
			$changeOpFactoryProvider = $wikibaseRepo->getChangeOpFactoryProvider();
			$apiHelperFactory = $wikibaseRepo->getApiHelperFactory( $mainModule->getContext() );
			$errorReporter = $apiHelperFactory->getErrorReporter( $mainModule );

			$modificationHelper = new StatementModificationHelper(
				$wikibaseRepo->getSnakFactory(),
				$wikibaseRepo->getEntityIdParser(),
				$wikibaseRepo->getStatementGuidValidator(),
				$errorReporter
			);

			return new CreateClaim(
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
		'class' => GetClaims::class,
		'factory' => function( ApiMain $mainModule, $moduleName ) {
			$wikibaseRepo = WikibaseRepo::getDefaultInstance();
			$apiHelperFactory = $wikibaseRepo->getApiHelperFactory( $mainModule->getContext() );

			return new GetClaims(
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
		'class' => RemoveClaims::class,
		'factory' => function( ApiMain $mainModule, $moduleName ) {
			$wikibaseRepo = WikibaseRepo::getDefaultInstance();
			$apiHelperFactory = $wikibaseRepo->getApiHelperFactory( $mainModule->getContext() );
			$changeOpFactoryProvider = $wikibaseRepo->getChangeOpFactoryProvider();

			$modificationHelper = new StatementModificationHelper(
				$wikibaseRepo->getSnakFactory(),
				$wikibaseRepo->getEntityIdParser(),
				$wikibaseRepo->getStatementGuidValidator(),
				$apiHelperFactory->getErrorReporter( $mainModule )
			);

			return new RemoveClaims(
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
		'class' => SetClaimValue::class,
		'factory' => function( ApiMain $mainModule, $moduleName ) {
			$wikibaseRepo = WikibaseRepo::getDefaultInstance();
			$apiHelperFactory = $wikibaseRepo->getApiHelperFactory( $mainModule->getContext() );
			$changeOpFactoryProvider = $wikibaseRepo->getChangeOpFactoryProvider();

			$modificationHelper = new StatementModificationHelper(
				$wikibaseRepo->getSnakFactory(),
				$wikibaseRepo->getEntityIdParser(),
				$wikibaseRepo->getStatementGuidValidator(),
				$apiHelperFactory->getErrorReporter( $mainModule )
			);

			return new SetClaimValue(
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
		'class' => SetReference::class,
		'factory' => function( ApiMain $mainModule, $moduleName ) {
			$wikibaseRepo = WikibaseRepo::getDefaultInstance();
			$apiHelperFactory = $wikibaseRepo->getApiHelperFactory( $mainModule->getContext() );
			$changeOpFactoryProvider = $wikibaseRepo->getChangeOpFactoryProvider();

			$modificationHelper = new StatementModificationHelper(
				$wikibaseRepo->getSnakFactory(),
				$wikibaseRepo->getEntityIdParser(),
				$wikibaseRepo->getStatementGuidValidator(),
				$apiHelperFactory->getErrorReporter( $mainModule )
			);

			return new SetReference(
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
		'class' => RemoveReferences::class,
		'factory' => function( ApiMain $mainModule, $moduleName ) {
			$wikibaseRepo = WikibaseRepo::getDefaultInstance();
			$apiHelperFactory = $wikibaseRepo->getApiHelperFactory( $mainModule->getContext() );
			$changeOpFactoryProvider = $wikibaseRepo->getChangeOpFactoryProvider();

			$modificationHelper = new StatementModificationHelper(
				$wikibaseRepo->getSnakFactory(),
				$wikibaseRepo->getEntityIdParser(),
				$wikibaseRepo->getStatementGuidValidator(),
				$apiHelperFactory->getErrorReporter( $mainModule )
			);

			return new RemoveReferences(
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
		'class' => SetClaim::class,
		'factory' => function( ApiMain $mainModule, $moduleName ) {
			$wikibaseRepo = WikibaseRepo::getDefaultInstance();
			$apiHelperFactory = $wikibaseRepo->getApiHelperFactory( $mainModule->getContext() );
			$changeOpFactoryProvider = $wikibaseRepo->getChangeOpFactoryProvider();

			$modificationHelper = new StatementModificationHelper(
				$wikibaseRepo->getSnakFactory(),
				$wikibaseRepo->getEntityIdParser(),
				$wikibaseRepo->getStatementGuidValidator(),
				$apiHelperFactory->getErrorReporter( $mainModule )
			);

			return new SetClaim(
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
		'class' => RemoveQualifiers::class,
		'factory' => function( ApiMain $mainModule, $moduleName ) {
			$wikibaseRepo = WikibaseRepo::getDefaultInstance();
			$apiHelperFactory = $wikibaseRepo->getApiHelperFactory( $mainModule->getContext() );
			$changeOpFactoryProvider = $wikibaseRepo->getChangeOpFactoryProvider();

			$modificationHelper = new StatementModificationHelper(
				$wikibaseRepo->getSnakFactory(),
				$wikibaseRepo->getEntityIdParser(),
				$wikibaseRepo->getStatementGuidValidator(),
				$apiHelperFactory->getErrorReporter( $mainModule )
			);

			return new RemoveQualifiers(
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
		'class' => SetQualifier::class,
		'factory' => function( ApiMain $mainModule, $moduleName ) {
			$wikibaseRepo = WikibaseRepo::getDefaultInstance();
			$apiHelperFactory = $wikibaseRepo->getApiHelperFactory( $mainModule->getContext() );
			$changeOpFactoryProvider = $wikibaseRepo->getChangeOpFactoryProvider();

			$modificationHelper = new StatementModificationHelper(
				$wikibaseRepo->getSnakFactory(),
				$wikibaseRepo->getEntityIdParser(),
				$wikibaseRepo->getStatementGuidValidator(),
				$apiHelperFactory->getErrorReporter( $mainModule )
			);

			return new SetQualifier(
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
		'class' => MergeItems::class,
		'factory' => function( ApiMain $mainModule, $moduleName ) {
			$wikibaseRepo = WikibaseRepo::getDefaultInstance();
			$apiHelperFactory = $wikibaseRepo->getApiHelperFactory( $mainModule->getContext() );

			return new MergeItems(
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
		'class' => FormatSnakValue::class,
		'factory' => function( ApiMain $mainModule, $moduleName ) {
			$wikibaseRepo = WikibaseRepo::getDefaultInstance();
			$apiHelperFactory = $wikibaseRepo->getApiHelperFactory( $mainModule->getContext() );

			return new FormatSnakValue(
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
		'class' => ParseValue::class,
		'factory' => function( ApiMain $mainModule, $moduleName ) {
			$wikibaseRepo = WikibaseRepo::getDefaultInstance();
			$apiHelperFactory = $wikibaseRepo->getApiHelperFactory( $mainModule->getContext() );

			return new ParseValue(
				$mainModule,
				$moduleName,
				$wikibaseRepo->getDataTypeFactory(),
				$wikibaseRepo->getValueParserFactory(),
				$wikibaseRepo->getDataTypeValidatorFactory(),
				$wikibaseRepo->getExceptionLocalizer(),
				$wikibaseRepo->getValidatorErrorLocalizer(),
				$wikibaseRepo->getPropertyDataTypeLookup(),
				$apiHelperFactory->getErrorReporter( $mainModule )
			);
		}
	];
	$wgAPIModules['wbavailablebadges'] = AvailableBadges::class;
	$wgAPIModules['wbcreateredirect'] = [
		'class' => CreateRedirect::class,
		'factory' => function( ApiMain $apiMain, $moduleName ) {
			$wikibaseRepo = WikibaseRepo::getDefaultInstance();
			$apiHelperFactory = $wikibaseRepo->getApiHelperFactory( $apiMain->getContext() );
			return new CreateRedirect(
				$apiMain,
				$moduleName,
				$wikibaseRepo->getEntityIdParser(),
				$apiHelperFactory->getErrorReporter( $apiMain ),
				$wikibaseRepo->newItemRedirectCreationInteractor( $apiMain->getUser(), $apiMain->getContext() ),
				MediaWikiServices::getInstance()->getPermissionManager()
			);
		}
	];
	$wgAPIModules['wbformatentities'] = [
		'class' => FormatEntities::class,
		'factory' => function( ApiMain $apiMain, $moduleName ) {
			$wikibaseRepo = WikibaseRepo::getDefaultInstance();
			$apiHelperFactory = $wikibaseRepo->getApiHelperFactory( $apiMain->getContext() );
			return new FormatEntities(
				$apiMain,
				$moduleName,
				$wikibaseRepo->getEntityIdParser(),
				$wikibaseRepo->getEntityIdHtmlLinkFormatterFactory(),
				$apiHelperFactory->getResultBuilder( $apiMain ),
				$apiHelperFactory->getErrorReporter( $apiMain ),
				MediaWikiServices::getInstance()->getStatsdDataFactory()
			);
		}
	];

	$wgAPIMetaModules['wbcontentlanguages'] = [
		'class' => MetaContentLanguages::class,
		'factory' => function( ApiQuery $apiQuery, $moduleName ) {
			$repo = WikibaseRepo::getDefaultInstance();

			// if CLDR is available, we expect to have some language name
			// (falling back to English if necessary) for any content language
			$expectKnownLanguageNames = ExtensionRegistry::getInstance()->isLoaded( 'cldr' );

			return new MetaContentLanguages(
				$repo->getWikibaseContentLanguages(),
				$expectKnownLanguageNames,
				$apiQuery,
				$moduleName
			);
		},
	];

	$wgAPIListModules['wbsearch'] = [
		'class' => QuerySearchEntities::class,
		'factory' => function( ApiQuery $apiQuery, $moduleName ) {
			$repo = WikibaseRepo::getDefaultInstance();

			return new QuerySearchEntities(
				$apiQuery,
				$moduleName,
				new TypeDispatchingEntitySearchHelper(
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
		'class' => ListSubscribers::class,
		'factory' => function( ApiQuery $apiQuery, $moduleName ) {
			$wikibaseRepo = WikibaseRepo::getDefaultInstance();
			$mediaWikiServices = MediaWikiServices::getInstance();
			$apiHelper = $wikibaseRepo->getApiHelperFactory( $apiQuery->getContext() );
			return new ListSubscribers(
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
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();

		$settings = $wikibaseRepo->getSettings();
		$copyrightView = new SpecialPageCopyrightView(
			new CopyrightMessageBuilder(),
			$settings->getSetting( 'dataRightsUrl' ),
			$settings->getSetting( 'dataRightsText' )
		);

		return new SpecialNewItem(
			$copyrightView,
			$wikibaseRepo->getEntityNamespaceLookup(),
			$wikibaseRepo->getSummaryFormatter(),
			$wikibaseRepo->getEntityTitleLookup(),
			$wikibaseRepo->newEditEntityFactory(),
			$wikibaseRepo->getSiteLookup(),
			$wikibaseRepo->getTermValidatorFactory(),
			$wikibaseRepo->getItemTermsCollisionDetector()
		);
	};
	$wgSpecialPages['NewProperty'] = function () {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();

		$settings = $wikibaseRepo->getSettings();
		$copyrightView = new SpecialPageCopyrightView(
			new CopyrightMessageBuilder(),
			$settings->getSetting( 'dataRightsUrl' ),
			$settings->getSetting( 'dataRightsText' )
		);

		return new SpecialNewProperty(
			$copyrightView,
			$wikibaseRepo->getEntityNamespaceLookup(),
			$wikibaseRepo->getSummaryFormatter(),
			$wikibaseRepo->getEntityTitleLookup(),
			$wikibaseRepo->newEditEntityFactory(),
			$wikibaseRepo->getPropertyTermsCollisionDetector()
		);
	};
	$wgSpecialPages['ItemByTitle'] = function () {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();

		$siteLinkTargetProvider = new SiteLinkTargetProvider(
			$wikibaseRepo->getSiteLookup(),
			$wikibaseRepo->getSettings()->getSetting( 'specialSiteLinkGroups' )
		);

		return new SpecialItemByTitle(
			$wikibaseRepo->getEntityTitleLookup(),
			new LanguageNameLookup(),
			$wikibaseRepo->getSiteLookup(),
			$wikibaseRepo->getStore()->newSiteLinkStore(),
			$siteLinkTargetProvider,
			$wikibaseRepo->getLogger(),
			$wikibaseRepo->getSettings()->getSetting( 'siteLinkGroups' )
		);
	};
	$wgSpecialPages['GoToLinkedPage'] = function() {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		return new SpecialGoToLinkedPage(
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
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$languageNameLookup = new LanguageNameLookup( $languageCode );
		$itemDisambiguation = new ItemDisambiguation(
			$wikibaseRepo->getEntityTitleLookup(),
			$languageNameLookup,
			$languageCode
		);
		return new SpecialItemDisambiguation(
			new MediaWikiContentLanguages(),
			$languageNameLookup,
			$itemDisambiguation,
			new TypeDispatchingEntitySearchHelper(
				$wikibaseRepo->getEntitySearchHelperCallbacks(),
				RequestContext::getMain()->getRequest()
			)
		);
	};
	$wgSpecialPages['ItemsWithoutSitelinks']
		= SpecialItemsWithoutSitelinks::class;
	$wgSpecialPages['SetLabel'] = function() {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();

		$settings = $wikibaseRepo->getSettings();
		$copyrightView = new SpecialPageCopyrightView(
			new CopyrightMessageBuilder(),
			$settings->getSetting( 'dataRightsUrl' ),
			$settings->getSetting( 'dataRightsText' )
		);

		return new SpecialSetLabel(
			$copyrightView,
			$wikibaseRepo->getSummaryFormatter(),
			$wikibaseRepo->getEntityTitleLookup(),
			$wikibaseRepo->newEditEntityFactory(),
			$wikibaseRepo->getEntityPermissionChecker()
		);
	};
	$wgSpecialPages['SetDescription'] = function() {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();

		$settings = $wikibaseRepo->getSettings();
		$copyrightView = new SpecialPageCopyrightView(
			new CopyrightMessageBuilder(),
			$settings->getSetting( 'dataRightsUrl' ),
			$settings->getSetting( 'dataRightsText' )
		);

		return new SpecialSetDescription(
			$copyrightView,
			$wikibaseRepo->getSummaryFormatter(),
			$wikibaseRepo->getEntityTitleLookup(),
			$wikibaseRepo->newEditEntityFactory(),
			$wikibaseRepo->getEntityPermissionChecker()
		);
	};
	$wgSpecialPages['SetAliases'] = function() {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();

		$settings = $wikibaseRepo->getSettings();
		$copyrightView = new SpecialPageCopyrightView(
			new CopyrightMessageBuilder(),
			$settings->getSetting( 'dataRightsUrl' ),
			$settings->getSetting( 'dataRightsText' )
		);

		return new SpecialSetAliases(
			$copyrightView,
			$wikibaseRepo->getSummaryFormatter(),
			$wikibaseRepo->getEntityTitleLookup(),
			$wikibaseRepo->newEditEntityFactory(),
			$wikibaseRepo->getEntityPermissionChecker()
		);
	};
	$wgSpecialPages['SetLabelDescriptionAliases'] = function() {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();

		$settings = $wikibaseRepo->getSettings();
		$copyrightView = new SpecialPageCopyrightView(
			new CopyrightMessageBuilder(),
			$settings->getSetting( 'dataRightsUrl' ),
			$settings->getSetting( 'dataRightsText' )
		);

		return new SpecialSetLabelDescriptionAliases(
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
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$siteLookup = $wikibaseRepo->getSiteLookup();
		$settings = $wikibaseRepo->getSettings();

		$siteLinkChangeOpFactory = $wikibaseRepo->getChangeOpFactoryProvider()->getSiteLinkChangeOpFactory();
		$siteLinkTargetProvider = new SiteLinkTargetProvider(
			$siteLookup,
			$settings->getSetting( 'specialSiteLinkGroups' )
		);

		$copyrightView = new SpecialPageCopyrightView(
			new CopyrightMessageBuilder(),
			$settings->getSetting( 'dataRightsUrl' ),
			$settings->getSetting( 'dataRightsText' )
		);

		$labelDescriptionLookupFactory = $wikibaseRepo->getLanguageFallbackLabelDescriptionLookupFactory();
		return new SpecialSetSiteLink(
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
	$wgSpecialPages['ListDatatypes'] = SpecialListDatatypes::class;
	$wgSpecialPages['ListProperties'] = function () {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$prefetchingTermLookup = $wikibaseRepo->getPrefetchingTermLookup();
		$labelDescriptionLookup = new LanguageFallbackLabelDescriptionLookup(
			$prefetchingTermLookup,
			$wikibaseRepo->getLanguageFallbackChainFactory()
				->newFromLanguage( $wikibaseRepo->getUserLanguage() )
		);
		$entityIdFormatter = $wikibaseRepo->getEntityIdHtmlLinkFormatterFactory()
			->getEntityIdFormatter( $wikibaseRepo->getUserLanguage() );
		return new SpecialListProperties(
			$wikibaseRepo->getDataTypeFactory(),
			$wikibaseRepo->getStore()->getPropertyInfoLookup(),
			$labelDescriptionLookup,
			$entityIdFormatter,
			$wikibaseRepo->getEntityTitleLookup(),
			$prefetchingTermLookup,
			$wikibaseRepo->getLanguageFallbackChainFactory()
		);
	};
	$wgSpecialPages['DispatchStats'] = SpecialDispatchStats::class;
	$wgSpecialPages['EntityData'] = SpecialEntityData::class;
	$wgSpecialPages['EntityPage'] = function() {
		return new SpecialEntityPage(
			WikibaseRepo::getDefaultInstance()->getEntityIdParser(),
			WikibaseRepo::getDefaultInstance()->getEntityTitleLookup()
		);
	};
	$wgSpecialPages['MyLanguageFallbackChain'] = function() {
		return new SpecialMyLanguageFallbackChain(
			WikibaseRepo::getDefaultInstance()->getLanguageFallbackChainFactory()
		);
	};
	$wgSpecialPages['MergeItems'] = function() {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();

		return new Wikibase\Repo\Specials\SpecialMergeItems(
			$wikibaseRepo->getEntityIdParser(),
			$wikibaseRepo->getExceptionLocalizer(),
			new TokenCheckInteractor( RequestContext::getMain()->getUser() ),
			$wikibaseRepo->newItemMergeInteractor( RequestContext::getMain() ),
			$wikibaseRepo->getEntityTitleLookup()
		);
	};
	$wgSpecialPages['RedirectEntity'] = function() {
		$user = RequestContext::getMain()->getUser();
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();

		return new SpecialRedirectEntity(
			$wikibaseRepo->getEntityIdParser(),
			$wikibaseRepo->getExceptionLocalizer(),
			new TokenCheckInteractor(
				$user
			),
			$wikibaseRepo->newItemRedirectCreationInteractor(
				$user,
				RequestContext::getMain()
			)
		);
	};
	$wgSpecialPages['AvailableBadges'] = function() {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();

		return new SpecialAvailableBadges(
			$wikibaseRepo->getPrefetchingTermLookup(),
			$wikibaseRepo->getEntityTitleLookup(),
			$wikibaseRepo->getLanguageFallbackChainFactory(),
			$wikibaseRepo->getSettings()->getSetting( 'badgeItems' )
		);
	};

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
	$wgHooks['BeforePageDisplayMobile'][] = '\Wikibase\RepoHooks::onBeforePageDisplayMobile';
	$wgHooks['ApiMaxLagInfo'][] = '\Wikibase\RepoHooks::onApiMaxLagInfo';
	$wgHooks['ParserOptionsRegister'][] = '\Wikibase\RepoHooks::onParserOptionsRegister';
	$wgHooks['RejectParserCacheValue'][] = '\Wikibase\RepoHooks::onRejectParserCacheValue';
	$wgHooks['ApiQuery::moduleManager'][] = '\Wikibase\RepoHooks::onApiQueryModuleManager';
	$wgHooks['ParserFirstCallInit'][] = '\Wikibase\RepoHooks::onParserFirstCallInit';

	// update hooks
	$wgHooks['LoadExtensionSchemaUpdates'][] = '\Wikibase\Repo\Store\Sql\ChangesSubscriptionSchemaUpdater::onSchemaUpdate';

	// test hooks
	$wgHooks['MediaWikiPHPUnitTest::startTest'][] = '\Wikibase\RepoHooks::onMediaWikiPHPUnitTestStartTest';

	// Resource Loader Modules:
	$wgResourceModules = array_merge(
		$wgResourceModules,
		require __DIR__ . '/resources/Resources.php'
	);

	$wgWBRepoSettings = array_merge(
		require __DIR__ . '/../lib/config/WikibaseLib.default.php',
		require __DIR__ . '/config/Wikibase.default.php'
	);

	$wgEventLoggingSchemas['WikibaseTermboxInteraction'] = 18726648;
} );
