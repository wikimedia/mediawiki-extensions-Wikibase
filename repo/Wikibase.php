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

use Wikibase\Repo\Api\CreateClaim;
use Wikibase\Repo\Api\RemoveClaims;
use Wikibase\Repo\Api\RemoveQualifiers;
use Wikibase\Repo\Api\RemoveReferences;
use Wikibase\Repo\Api\SetClaim;
use Wikibase\Repo\Api\SetClaimValue;
use Wikibase\Repo\Api\SetQualifier;
use Wikibase\Repo\Api\SetReference;
use Wikibase\Repo\Api\StatementModificationHelper;
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
		$wgWBRepoSettings;

	// i18n messages, kept for backward compatibility (T257442)
	$wgMessagesDirs['Wikibase'] = __DIR__ . '/i18n';
	$wgMessagesDirs['WikibaseApi'] = __DIR__ . '/i18n/api';
	$wgMessagesDirs['WikibaseLib'] = __DIR__ . '/../lib/i18n';
	$wgExtensionMessagesFiles['WikibaseAlias'] = __DIR__ . '/Wikibase.i18n.alias.php';
	$wgExtensionMessagesFiles['WikibaseNS'] = __DIR__ . '/Wikibase.i18n.namespaces.php';
	$wgExtensionMessagesFiles['wikibaserepomagic'] = __DIR__ . '/WikibaseRepo.i18n.magic.php';

	// API module registration
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

	$wgHooks['HtmlPageLinkRendererEnd'][] = 'Wikibase\Repo\Hooks\HtmlPageLinkRendererEndHookHandler::onHtmlPageLinkRendererEnd';
	$wgHooks['ShowSearchHit'][] = 'Wikibase\Repo\Hooks\ShowSearchHitHandler::onShowSearchHit';
	$wgHooks['ShowSearchHitTitle'][] = 'Wikibase\Repo\Hooks\ShowSearchHitHandler::onShowSearchHitTitle';

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
