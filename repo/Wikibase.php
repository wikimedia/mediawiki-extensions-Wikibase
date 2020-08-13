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
 * out more at https://wikiba.se              \/        \/
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
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookup;
use Wikibase\Repo\Api\CreateClaim;
use Wikibase\Repo\Api\CreateRedirect;
use Wikibase\Repo\Api\EditEntity;
use Wikibase\Repo\Api\EditSummaryHelper;
use Wikibase\Repo\Api\FormatEntities;
use Wikibase\Repo\Api\FormatSnakValue;
use Wikibase\Repo\Api\GetClaims;
use Wikibase\Repo\Api\GetEntities;
use Wikibase\Repo\Api\LinkTitles;
use Wikibase\Repo\Api\MergeItems;
use Wikibase\Repo\Api\ParseValue;
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
use Wikibase\Repo\FederatedProperties\SpecialListFederatedProperties;
use Wikibase\Repo\SiteLinkTargetProvider;
use Wikibase\Repo\Specials\SpecialListProperties;
use Wikibase\Repo\Store\Store;
use Wikibase\Repo\WikibaseRepo;

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'Not an entry point.' );
}

// load parts already converted to extension registration
wfLoadExtension( 'WikibaseRepository', __DIR__ . '/../extension-repo.json' );

// Sub-extensions needed by WikibaseRepository
require_once __DIR__ . '/../view/WikibaseView.php';

call_user_func( function() {
	global $wgAPIModules,
		$wgExtensionMessagesFiles,
		$wgHooks,
		$wgMessagesDirs,
		$wgResourceModules,
		$wgSpecialPages,
		$wgWBRepoSettings;

	// i18n messages, kept for backward compatibility (T257442)
	$wgMessagesDirs['Wikibase'] = __DIR__ . '/i18n';
	$wgMessagesDirs['WikibaseApi'] = __DIR__ . '/i18n/api';
	$wgMessagesDirs['WikibaseLib'] = __DIR__ . '/../lib/i18n';
	$wgExtensionMessagesFiles['WikibaseAlias'] = __DIR__ . '/Wikibase.i18n.alias.php';
	$wgExtensionMessagesFiles['WikibaseNS'] = __DIR__ . '/Wikibase.i18n.namespaces.php';
	$wgExtensionMessagesFiles['wikibaserepomagic'] = __DIR__ . '/WikibaseRepo.i18n.magic.php';

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
			$wikibaseRepo = WikibaseRepo::getDefaultInstance();
			return new SetLabel(
				$mainModule,
				$moduleName,
				$wikibaseRepo->getChangeOpFactoryProvider()
				      ->getFingerprintChangeOpFactory(),
				$wikibaseRepo->inFederatedPropertyMode()
			);
		}
	];
	$wgAPIModules['wbsetdescription'] = [
		'class' => SetDescription::class,
		'factory' => function ( ApiMain $mainModule, $moduleName ) {
			$wikibaseRepo = WikibaseRepo::getDefaultInstance();
			return new SetDescription(
				$mainModule,
				$moduleName,
				$wikibaseRepo->getChangeOpFactoryProvider()
				      ->getFingerprintChangeOpFactory(),
				$wikibaseRepo->inFederatedPropertyMode()
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
			$apiHelperFactory = $repo->getApiHelperFactory( $mainModule->getContext() );

			return new SearchEntities(
				$mainModule,
				$moduleName,
				$entitySearchHelper,
				null,
				$repo->getTermsLanguages(),
				$repo->getEntitySourceDefinitions(),
				$repo->getEntityTitleTextLookup(),
				$repo->getEntityUrlLookup(),
				$repo->getEntityArticleIdLookup(),
				$apiHelperFactory->getErrorReporter( $mainModule )
			);
		},
	];
	$wgAPIModules['wbsetaliases'] = [
		'class' => SetAliases::class,
		'factory' => function ( ApiMain $mainModule, $moduleName ) {
			$wikibaseRepo = WikibaseRepo::getDefaultInstance();
			return new SetAliases(
				$mainModule,
				$moduleName,
				$wikibaseRepo->getChangeOpFactoryProvider()
				      ->getFingerprintChangeOpFactory(),
				$wikibaseRepo->inFederatedPropertyMode()
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
				),
				$wikibaseRepo->inFederatedPropertyMode()
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
				$wikibaseRepo->getSiteLinkBadgeChangeOpSerializationValidator(),
				$wikibaseRepo->inFederatedPropertyMode()
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
				},
				$wikibaseRepo->inFederatedPropertyMode()
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
				},
				$wikibaseRepo->inFederatedPropertyMode()
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

	// Special page registration
	$wgSpecialPages['ListProperties'] = function () {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();

		if ( $wikibaseRepo->getSettings()->getSetting( 'federatedPropertiesEnabled' ) ) {
			return new SpecialListFederatedProperties(
				$wikibaseRepo->getSettings()->getSetting( 'federatedPropertiesSourceScriptUrl' )
			);
		}

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

	$wgHooks['LoadExtensionSchemaUpdates'][] = 'Wikibase\Repo\Store\Sql\DatabaseSchemaUpdater::onSchemaUpdate';
	$wgHooks['HtmlPageLinkRendererEnd'][] = 'Wikibase\Repo\Hooks\HtmlPageLinkRendererEndHookHandler::onHtmlPageLinkRendererEnd';
	$wgHooks['ChangesListInitRows'][] = 'Wikibase\Repo\Hooks\LabelPrefetchHookHandlers::onChangesListInitRows';
	$wgHooks['ShowSearchHit'][] = 'Wikibase\Repo\Hooks\ShowSearchHitHandler::onShowSearchHit';
	$wgHooks['ShowSearchHitTitle'][] = 'Wikibase\Repo\Hooks\ShowSearchHitHandler::onShowSearchHitTitle';
	$wgHooks['OutputPageBeforeHTML'][] = 'Wikibase\Repo\Hooks\OutputPageBeforeHTMLHookHandler::onOutputPageBeforeHTML';

	// Resource Loader Modules:
	$wgResourceModules = array_merge(
		$wgResourceModules,
		require __DIR__ . '/resources/Resources.php'
	);

	$wgWBRepoSettings = array_merge(
		require __DIR__ . '/../lib/config/WikibaseLib.default.php',
		require __DIR__ . '/config/Wikibase.default.php'
	);

	// Tell client/config/WikibaseClient.example.php not to configure an example repo, because this wiki is the repo;
	// added in July 2020, this is hopefully just a fairly short-lived hack.
	define( 'WB_NO_CONFIGURE_EXAMPLE_REPO', 1 );
} );
