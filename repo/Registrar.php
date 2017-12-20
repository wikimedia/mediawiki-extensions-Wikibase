<?php

namespace Wikibase\Repo;

/**
 * GOAT
 */
class Registrar {

	public static function registerExtension() {
		global $wgAutoloadClasses,
			$wgAPIListModules,
			$wgAPIModules,
			$wgAvailableRights,
			$wgGrantPermissions,
			$wgGroupPermissions,
			$wgHooks,
			$wgJobClasses,
			$wgResourceModules,
			$wgSpecialPages,
			$wgValueParsers,
			$wgWBRepoDataTypes,
			$wgWBRepoSettings;

		if ( defined( 'WB_VERSION' ) ) {
			// Do not initialize more than once.
			return 1;
		}

		define( 'WB_VERSION', '0.5 alpha' );

		/**
		 * Registry of ValueParsers classes or factory callbacks, by datatype.
		 * @note: that parsers are also registered under their old names for backwards compatibility,
		 * for use with the deprecated 'parser' parameter of the wbparsevalue API module.
		 */
		$wgValueParsers = [];

		if ( !defined( 'WBL_VERSION' ) ) {
			throw new \Exception( 'Wikibase depends on the WikibaseLib extension.' );
		}

		if ( !defined( 'WIKIBASE_VIEW_VERSION' ) ) {
			include_once __DIR__ . '/../view/WikibaseView.php';
		}

		if ( !defined( 'WIKIBASE_VIEW_VERSION' ) ) {
			throw new \Exception( 'Wikibase depends on WikibaseView.' );
		}

		// Nasty hack: part of repo relies on classes defined in Client! load it if in repo-only mode
		if ( !defined( 'WBC_VERSION' ) ) {
			$wgAutoloadClasses['Wikibase\\Client\\Store\\TitleFactory'] = __DIR__ . '/../client/includes/Store/TitleFactory.php';
		}

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

		/**
		 * @var callable[] $wgValueParsers Defines parser factory callbacks by parser name (not data type name).
		 * @deprecated use $wgWBRepoDataTypes instead.
		 */
		$wgValueParsers['wikibase-entityid'] = $wgWBRepoDataTypes['VT:wikibase-entityid']['parser-factory-callback'];
		$wgValueParsers['globecoordinate'] = $wgWBRepoDataTypes['VT:globecoordinate']['parser-factory-callback'];

		// 'null' is not a datatype. Kept for backwards compatibility.
		$wgValueParsers['null'] = function() {
			return new \ValueParsers\NullParser();
		};

		// API module registration
		$wgAPIModules['wbgetentities'] = [
			'class' => Api\GetEntities::class,
			'factory' => function( \ApiMain $apiMain, $moduleName ) {
				$wikibaseRepo = WikibaseRepo::getDefaultInstance();
				$settings = $wikibaseRepo->getSettings();
				$apiHelperFactory = $wikibaseRepo->getApiHelperFactory( $apiMain->getContext() );

				$siteLinkTargetProvider = new SiteLinkTargetProvider(
					$wikibaseRepo->getSiteLookup(),
					$settings->getSetting( 'specialSiteLinkGroups' )
				);

				return new Api\GetEntities(
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
			'class' => Api\SetLabel::class,
			'factory' => function ( \ApiMain $mainModule, $moduleName ) {
				return new Api\SetLabel(
					$mainModule,
					$moduleName,
					WikibaseRepo::getDefaultInstance()->getChangeOpFactoryProvider()
						->getFingerprintChangeOpFactory()
				);
			}
		];
		$wgAPIModules['wbsetdescription'] = [
			'class' => Api\SetDescription::class,
			'factory' => function ( \ApiMain $mainModule, $moduleName ) {
				return new Api\SetDescription(
					$mainModule,
					$moduleName,
					WikibaseRepo::getDefaultInstance()->getChangeOpFactoryProvider()
						->getFingerprintChangeOpFactory()
				);
			}
		];
		$wgAPIModules['wbsearchentities'] = [
			'class' => Api\SearchEntities::class,
			'factory' => function( \ApiMain $mainModule, $moduleName ) {
				$repo = WikibaseRepo::getDefaultInstance();
				$settings = $repo->getSettings()->getSetting( 'entitySearch' );
				if ( $settings['useCirrus'] ) {
					$lang = $repo->getUserLanguage();
					$entitySearchHelper = new Search\Elastic\EntitySearchElastic(
						$repo->getLanguageFallbackChainFactory(),
						$repo->getEntityIdParser(),
						$lang,
						$repo->getContentModelMappings(),
						$settings
					);
					$entitySearchHelper->setRequest( $mainModule->getRequest() );
				} else {
					$entitySearchHelper = new Api\EntitySearchTermIndex(
						$repo->getEntityLookup(),
						$repo->getEntityIdParser(),
						$repo->newTermSearchInteractor( $repo->getUserLanguage()->getCode() ),
						new \Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookup(
							$repo->getTermLookup(),
							$repo->getLanguageFallbackChainFactory()->newFromLanguage( $repo->getUserLanguage() )
						),
						$repo->getEntityTypeToRepositoryMapping()
					);
				}

				return new Api\SearchEntities(
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
			'class' => Api\SetAliases::class,
			'factory' => function ( \ApiMain $mainModule, $moduleName ) {
				return new Api\SetAliases(
					$mainModule,
					$moduleName,
					WikibaseRepo::getDefaultInstance()->getChangeOpFactoryProvider()
						->getFingerprintChangeOpFactory()
				);
			}
		];
		$wgAPIModules['wbeditentity'] = [
			'class' => Api\EditEntity::class,
			'factory' => function ( \ApiMain $mainModule, $moduleName ) {
				$wikibaseRepo = WikibaseRepo::getDefaultInstance();
				$changeOpFactoryProvider = $wikibaseRepo->getChangeOpFactoryProvider();
				return new Api\EditEntity(
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
			'class' => Api\LinkTitles::class,
			'factory' => function ( \ApiMain $mainModule, $moduleName ) {
				$wikibaseRepo = WikibaseRepo::getDefaultInstance();
				$settings = $wikibaseRepo->getSettings();
				$apiHelperFactory = $wikibaseRepo->getApiHelperFactory( $mainModule->getContext() );

				$siteLinkTargetProvider = new SiteLinkTargetProvider(
					$wikibaseRepo->getSiteLookup(),
					$settings->getSetting( 'specialSiteLinkGroups' )
				);

				return new Api\LinkTitles(
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
			'class' => Api\SetSiteLink::class,
			'factory' => function ( \ApiMain $mainModule, $moduleName ) {
				$wikibaseRepo = WikibaseRepo::getDefaultInstance();

				return new Api\SetSiteLink(
					$mainModule,
					$moduleName,
					$wikibaseRepo->getChangeOpFactoryProvider()
						->getSiteLinkChangeOpFactory(),
					$wikibaseRepo->getSiteLinkBadgeChangeOpSerializationValidator()
				);
			}
		];
		$wgAPIModules['wbcreateclaim'] = [
			'class' => Api\CreateClaim::class,
			'factory' => function ( \ApiMain $mainModule, $moduleName ) {
				$wikibaseRepo = WikibaseRepo::getDefaultInstance();
				$changeOpFactoryProvider = $wikibaseRepo->getChangeOpFactoryProvider();
				$apiHelperFactory = $wikibaseRepo->getApiHelperFactory( $mainModule->getContext() );
				$errorReporter = $apiHelperFactory->getErrorReporter( $mainModule );

				$modificationHelper = new Api\StatementModificationHelper(
					$wikibaseRepo->getSnakFactory(),
					$wikibaseRepo->getEntityIdParser(),
					$wikibaseRepo->getStatementGuidValidator(),
					$errorReporter
				);

				return new Api\CreateClaim(
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
			'class' => Api\GetClaims::class,
			'factory' => function( \ApiMain $mainModule, $moduleName ) {
				$wikibaseRepo = WikibaseRepo::getDefaultInstance();
				$apiHelperFactory = $wikibaseRepo->getApiHelperFactory( $mainModule->getContext() );

				return new Api\GetClaims(
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
			'class' => Api\RemoveClaims::class,
			'factory' => function( \ApiMain $mainModule, $moduleName ) {
				$wikibaseRepo = WikibaseRepo::getDefaultInstance();
				$apiHelperFactory = $wikibaseRepo->getApiHelperFactory( $mainModule->getContext() );
				$changeOpFactoryProvider = $wikibaseRepo->getChangeOpFactoryProvider();

				$modificationHelper = new Api\StatementModificationHelper(
					$wikibaseRepo->getSnakFactory(),
					$wikibaseRepo->getEntityIdParser(),
					$wikibaseRepo->getStatementGuidValidator(),
					$apiHelperFactory->getErrorReporter( $mainModule )
				);

				return new Api\RemoveClaims(
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
			'class' => Api\SetClaimValue::class,
			'factory' => function( \ApiMain $mainModule, $moduleName ) {
				$wikibaseRepo = WikibaseRepo::getDefaultInstance();
				$apiHelperFactory = $wikibaseRepo->getApiHelperFactory( $mainModule->getContext() );
				$changeOpFactoryProvider = $wikibaseRepo->getChangeOpFactoryProvider();

				$modificationHelper = new Api\StatementModificationHelper(
					$wikibaseRepo->getSnakFactory(),
					$wikibaseRepo->getEntityIdParser(),
					$wikibaseRepo->getStatementGuidValidator(),
					$apiHelperFactory->getErrorReporter( $mainModule )
				);

				return new Api\SetClaimValue(
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
			'class' => Api\SetReference::class,
			'factory' => function( \ApiMain $mainModule, $moduleName ) {
				$wikibaseRepo = WikibaseRepo::getDefaultInstance();
				$apiHelperFactory = $wikibaseRepo->getApiHelperFactory( $mainModule->getContext() );
				$changeOpFactoryProvider = $wikibaseRepo->getChangeOpFactoryProvider();

				$modificationHelper = new Api\StatementModificationHelper(
					$wikibaseRepo->getSnakFactory(),
					$wikibaseRepo->getEntityIdParser(),
					$wikibaseRepo->getStatementGuidValidator(),
					$apiHelperFactory->getErrorReporter( $mainModule )
				);

				return new Api\SetReference(
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
			'class' => Api\RemoveReferences::class,
			'factory' => function( \ApiMain $mainModule, $moduleName ) {
				$wikibaseRepo = WikibaseRepo::getDefaultInstance();
				$apiHelperFactory = $wikibaseRepo->getApiHelperFactory( $mainModule->getContext() );
				$changeOpFactoryProvider = $wikibaseRepo->getChangeOpFactoryProvider();

				$modificationHelper = new Api\StatementModificationHelper(
					$wikibaseRepo->getSnakFactory(),
					$wikibaseRepo->getEntityIdParser(),
					$wikibaseRepo->getStatementGuidValidator(),
					$apiHelperFactory->getErrorReporter( $mainModule )
				);

				return new Api\RemoveReferences(
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
			'class' => Api\SetClaim::class,
			'factory' => function( \ApiMain $mainModule, $moduleName ) {
				$wikibaseRepo = WikibaseRepo::getDefaultInstance();
				$apiHelperFactory = $wikibaseRepo->getApiHelperFactory( $mainModule->getContext() );
				$changeOpFactoryProvider = $wikibaseRepo->getChangeOpFactoryProvider();

				$modificationHelper = new Api\StatementModificationHelper(
					$wikibaseRepo->getSnakFactory(),
					$wikibaseRepo->getEntityIdParser(),
					$wikibaseRepo->getStatementGuidValidator(),
					$apiHelperFactory->getErrorReporter( $mainModule )
				);

				return new Api\SetClaim(
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
			'class' => Api\RemoveQualifiers::class,
			'factory' => function( \ApiMain $mainModule, $moduleName ) {
				$wikibaseRepo = WikibaseRepo::getDefaultInstance();
				$apiHelperFactory = $wikibaseRepo->getApiHelperFactory( $mainModule->getContext() );
				$changeOpFactoryProvider = $wikibaseRepo->getChangeOpFactoryProvider();

				$modificationHelper = new Api\StatementModificationHelper(
					$wikibaseRepo->getSnakFactory(),
					$wikibaseRepo->getEntityIdParser(),
					$wikibaseRepo->getStatementGuidValidator(),
					$apiHelperFactory->getErrorReporter( $mainModule )
				);

				return new Api\RemoveQualifiers(
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
			'class' => Api\SetQualifier::class,
			'factory' => function( \ApiMain $mainModule, $moduleName ) {
				$wikibaseRepo = WikibaseRepo::getDefaultInstance();
				$apiHelperFactory = $wikibaseRepo->getApiHelperFactory( $mainModule->getContext() );
				$changeOpFactoryProvider = $wikibaseRepo->getChangeOpFactoryProvider();

				$modificationHelper = new Api\StatementModificationHelper(
					$wikibaseRepo->getSnakFactory(),
					$wikibaseRepo->getEntityIdParser(),
					$wikibaseRepo->getStatementGuidValidator(),
					$apiHelperFactory->getErrorReporter( $mainModule )
				);

				return new Api\SetQualifier(
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
			'class' => Api\MergeItems::class,
			'factory' => function( \ApiMain $mainModule, $moduleName ) {
				$wikibaseRepo = WikibaseRepo::getDefaultInstance();
				$apiHelperFactory = $wikibaseRepo->getApiHelperFactory( $mainModule->getContext() );

				return new Api\MergeItems(
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
			'class' => Api\FormatSnakValue::class,
			'factory' => function( \ApiMain $mainModule, $moduleName ) {
				$wikibaseRepo = WikibaseRepo::getDefaultInstance();
				$apiHelperFactory = $wikibaseRepo->getApiHelperFactory( $mainModule->getContext() );

				return new Api\FormatSnakValue(
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
			'class' => Api\ParseValue::class,
			'factory' => function( \ApiMain $mainModule, $moduleName ) {
				$wikibaseRepo = WikibaseRepo::getDefaultInstance();
				$apiHelperFactory = $wikibaseRepo->getApiHelperFactory( $mainModule->getContext() );

				return new Api\ParseValue(
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
		$wgAPIModules['wbavailablebadges'] = Api\AvailableBadges::class;
		$wgAPIModules['wbcreateredirect'] = [
			'class' => Api\CreateRedirect::class,
			'factory' => function( \ApiMain $apiMain, $moduleName ) {
				$wikibaseRepo = WikibaseRepo::getDefaultInstance();
				$apiHelperFactory = $wikibaseRepo->getApiHelperFactory( $apiMain->getContext() );
				return new Api\CreateRedirect(
					$apiMain,
					$moduleName,
					$wikibaseRepo->getEntityIdParser(),
					$apiHelperFactory->getErrorReporter( $apiMain ),
					$wikibaseRepo->newRedirectCreationInteractor( $apiMain->getUser(), $apiMain->getContext() )
				);
			}
		];
		$wgAPIListModules['wbsearch'] = [
			'class' => Api\QuerySearchEntities::class,
			'factory' => function( \ApiQuery $apiQuery, $moduleName ) {
				$repo = WikibaseRepo::getDefaultInstance();
				$entitySearchHelper = new Api\EntitySearchTermIndex(
					$repo->getEntityLookup(),
					$repo->getEntityIdParser(),
					$repo->newTermSearchInteractor( $apiQuery->getLanguage()->getCode() ),
					new \Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookup(
						$repo->getTermLookup(),
						$repo->getLanguageFallbackChainFactory()
							->newFromLanguage( $apiQuery->getLanguage() )
					),
					$repo->getEntityTypeToRepositoryMapping()
				);

				return new Api\QuerySearchEntities(
					$apiQuery,
					$moduleName,
					$entitySearchHelper,
					$repo->getEntityTitleLookup(),
					$repo->getTermsLanguages(),
					$repo->getEnabledEntityTypes()
				);
			}
		];
		$wgAPIListModules['wbsubscribers'] = [
			'class' => Api\ListSubscribers::class,
			'factory' => function( \ApiQuery $apiQuery, $moduleName ) {
				$wikibaseRepo = WikibaseRepo::getDefaultInstance();
				$mediaWikiServices = \MediaWiki\MediaWikiServices::getInstance();
				$apiHelper = $wikibaseRepo->getApiHelperFactory( $apiQuery->getContext() );
				return new Api\ListSubscribers(
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
			$copyrightView = new Specials\SpecialPageCopyrightView(
				new \Wikibase\CopyrightMessageBuilder(),
				$settings->getSetting( 'dataRightsUrl' ),
				$settings->getSetting( 'dataRightsText' )
			);

			return new Specials\SpecialNewItem(
				$copyrightView,
				$wikibaseRepo->getEntityNamespaceLookup(),
				$wikibaseRepo->getSummaryFormatter(),
				$wikibaseRepo->getEntityTitleLookup(),
				$wikibaseRepo->newEditEntityFactory(),
				$wikibaseRepo->getSiteLookup()
			);
		};
		$wgSpecialPages['NewProperty'] = function () {
			$wikibaseRepo = WikibaseRepo::getDefaultInstance();

			$settings = $wikibaseRepo->getSettings();
			$copyrightView = new Specials\SpecialPageCopyrightView(
				new \Wikibase\CopyrightMessageBuilder(),
				$settings->getSetting( 'dataRightsUrl' ),
				$settings->getSetting( 'dataRightsText' )
			);

			return new Specials\SpecialNewProperty(
				$copyrightView,
				$wikibaseRepo->getEntityNamespaceLookup(),
				$wikibaseRepo->getSummaryFormatter(),
				$wikibaseRepo->getEntityTitleLookup(),
				$wikibaseRepo->newEditEntityFactory()
			);
		};
		$wgSpecialPages['ItemByTitle'] = function () {
			$wikibaseRepo = WikibaseRepo::getDefaultInstance();

			$siteLinkTargetProvider = new SiteLinkTargetProvider(
				$wikibaseRepo->getSiteLookup(),
				$wikibaseRepo->getSettings()->getSetting( 'specialSiteLinkGroups' )
			);

			return new Specials\SpecialItemByTitle(
				$wikibaseRepo->getEntityTitleLookup(),
				new \Wikibase\Lib\LanguageNameLookup(),
				$wikibaseRepo->getSiteLookup(),
				$wikibaseRepo->getStore()->newSiteLinkStore(),
				$siteLinkTargetProvider,
				$wikibaseRepo->getSettings()->getSetting( 'siteLinkGroups' )
			);
		};
		$wgSpecialPages['GoToLinkedPage'] = function() {
			$wikibaseRepo = WikibaseRepo::getDefaultInstance();
			return new Specials\SpecialGoToLinkedPage(
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
			$languageNameLookup = new \Wikibase\Lib\LanguageNameLookup( $languageCode );
			$itemDisambiguation = new \Wikibase\ItemDisambiguation(
				$wikibaseRepo->getEntityTitleLookup(),
				$languageNameLookup,
				$languageCode
			);
			return new Specials\SpecialItemDisambiguation(
				new \Wikibase\Lib\MediaWikiContentLanguages(),
				$languageNameLookup,
				$itemDisambiguation,
				$wikibaseRepo->newTermSearchInteractor( $languageCode )
			);
		};
		$wgSpecialPages['ItemsWithoutSitelinks']
			= Specials\SpecialItemsWithoutSitelinks::class;
		$wgSpecialPages['SetLabel'] = function() {
			$wikibaseRepo = WikibaseRepo::getDefaultInstance();

			$settings = $wikibaseRepo->getSettings();
			$copyrightView = new Specials\SpecialPageCopyrightView(
				new \Wikibase\CopyrightMessageBuilder(),
				$settings->getSetting( 'dataRightsUrl' ),
				$settings->getSetting( 'dataRightsText' )
			);

			return new Specials\SpecialSetLabel(
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
			$copyrightView = new Specials\SpecialPageCopyrightView(
				new \Wikibase\CopyrightMessageBuilder(),
				$settings->getSetting( 'dataRightsUrl' ),
				$settings->getSetting( 'dataRightsText' )
			);

			return new Specials\SpecialSetDescription(
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
			$copyrightView = new Specials\SpecialPageCopyrightView(
				new \Wikibase\CopyrightMessageBuilder(),
				$settings->getSetting( 'dataRightsUrl' ),
				$settings->getSetting( 'dataRightsText' )
			);

			return new Specials\SpecialSetAliases(
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
			$copyrightView = new Specials\SpecialPageCopyrightView(
				new \Wikibase\CopyrightMessageBuilder(),
				$settings->getSetting( 'dataRightsUrl' ),
				$settings->getSetting( 'dataRightsText' )
			);

			return new Specials\SpecialSetLabelDescriptionAliases(
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

			$copyrightView = new Specials\SpecialPageCopyrightView(
				new \Wikibase\CopyrightMessageBuilder(),
				$settings->getSetting( 'dataRightsUrl' ),
				$settings->getSetting( 'dataRightsText' )
			);

			$labelDescriptionLookupFactory = $wikibaseRepo->getLanguageFallbackLabelDescriptionLookupFactory();
			return new Specials\SpecialSetSiteLink(
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
			Specials\SpecialEntitiesWithoutPageFactory::class,
			'newSpecialEntitiesWithoutLabel'
		];
		$wgSpecialPages['EntitiesWithoutDescription'] = [
			Specials\SpecialEntitiesWithoutPageFactory::class,
			'newSpecialEntitiesWithoutDescription'
		];
		$wgSpecialPages['ListDatatypes'] = Specials\SpecialListDatatypes::class;
		$wgSpecialPages['ListProperties'] = function () {
			$wikibaseRepo = WikibaseRepo::getDefaultInstance();
			$prefetchingTermLookup = $wikibaseRepo->getPrefetchingTermLookup();
			$labelDescriptionLookup = new \Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookup(
				$prefetchingTermLookup,
				$wikibaseRepo->getLanguageFallbackChainFactory()
					->newFromLanguage( $wikibaseRepo->getUserLanguage() )
			);
			$entityIdFormatter = $wikibaseRepo->getEntityIdHtmlLinkFormatterFactory()
				->getEntityIdFormatter( $labelDescriptionLookup );
			return new Specials\SpecialListProperties(
				$wikibaseRepo->getDataTypeFactory(),
				$wikibaseRepo->getStore()->getPropertyInfoLookup(),
				$labelDescriptionLookup,
				$entityIdFormatter,
				$wikibaseRepo->getEntityTitleLookup(),
				$prefetchingTermLookup
			);
		};
		$wgSpecialPages['DispatchStats'] = Specials\SpecialDispatchStats::class;
		$wgSpecialPages['EntityData'] = Specials\SpecialEntityData::class;
		$wgSpecialPages['EntityPage'] = function() {
			return new Specials\SpecialEntityPage(
				WikibaseRepo::getDefaultInstance()->getEntityIdParser(),
				WikibaseRepo::getDefaultInstance()->getEntityContentFactory()
			);
		};
		$wgSpecialPages['MyLanguageFallbackChain'] = function() {
			return new Specials\SpecialMyLanguageFallbackChain(
				WikibaseRepo::getDefaultInstance()->getLanguageFallbackChainFactory()
			);
		};
		$wgSpecialPages['MergeItems'] = function() {
			global $wgUser;

			$wikibaseRepo = WikibaseRepo::getDefaultInstance();

			return new Specials\SpecialMergeItems(
				$wikibaseRepo->getEntityIdParser(),
				$wikibaseRepo->getExceptionLocalizer(),
				new Interactors\TokenCheckInteractor( $wgUser ),
				$wikibaseRepo->newItemMergeInteractor( \RequestContext::getMain() ),
				$wikibaseRepo->getEntityTitleLookup()
			);
		};
		$wgSpecialPages['RedirectEntity'] = function() {
			global $wgUser;

			$wikibaseRepo = WikibaseRepo::getDefaultInstance();

			return new Specials\SpecialRedirectEntity(
				$wikibaseRepo->getEntityIdParser(),
				$wikibaseRepo->getExceptionLocalizer(),
				new Interactors\TokenCheckInteractor(
					$wgUser
				),
				$wikibaseRepo->newRedirectCreationInteractor(
					$wgUser,
					\RequestContext::getMain()
				)
			);
		};
		$wgSpecialPages['AvailableBadges'] = function() {
			$wikibaseRepo = WikibaseRepo::getDefaultInstance();

			return new Specials\SpecialAvailableBadges(
				$wikibaseRepo->getPrefetchingTermLookup(),
				$wikibaseRepo->getEntityTitleLookup(),
				$wikibaseRepo->getSettings()->getSetting( 'badgeItems' )
			);
		};

		// Jobs
		$wgJobClasses['UpdateRepoOnMove'] = UpdateRepo\UpdateRepoOnMoveJob::class;
		$wgJobClasses['UpdateRepoOnDelete'] = UpdateRepo\UpdateRepoOnDeleteJob::class;

		// Hooks
		$wgHooks['BeforePageDisplay'][] = 'Wikibase\RepoHooks::onBeforePageDisplay';
		$wgHooks['LoadExtensionSchemaUpdates'][] = 'Store\Sql\DatabaseSchemaUpdater::onSchemaUpdate';
		$wgHooks['UnitTestsList'][] = 'Wikibase\RepoHooks::registerUnitTests';
		$wgHooks['ResourceLoaderTestModules'][] = 'Wikibase\RepoHooks::registerQUnitTests';

		$wgHooks['NamespaceIsMovable'][] = 'Wikibase\RepoHooks::onNamespaceIsMovable';
		$wgHooks['NewRevisionFromEditComplete'][] = 'Wikibase\RepoHooks::onNewRevisionFromEditComplete';
		$wgHooks['SkinTemplateNavigation'][] = 'Wikibase\RepoHooks::onPageTabs';
		$wgHooks['RecentChange_save'][] = 'Wikibase\RepoHooks::onRecentChangeSave';
		$wgHooks['ArticleDeleteComplete'][] = 'Wikibase\RepoHooks::onArticleDeleteComplete';
		$wgHooks['ArticleUndelete'][] = 'Wikibase\RepoHooks::onArticleUndelete';
		$wgHooks['GetPreferences'][] = 'Wikibase\RepoHooks::onGetPreferences';
		$wgHooks['LinkBegin'][] = 'Wikibase\Repo\Hooks\LinkBeginHookHandler::onLinkBegin';
		$wgHooks['ChangesListInitRows'][] = 'Wikibase\Repo\Hooks\LabelPrefetchHookHandlers::onChangesListInitRows';
		$wgHooks['OutputPageBodyAttributes'][] = 'Wikibase\RepoHooks::onOutputPageBodyAttributes';
		$wgHooks['FormatAutocomments'][] = 'Wikibase\RepoHooks::onFormat';
		$wgHooks['PageHistoryLineEnding'][] = 'Wikibase\RepoHooks::onPageHistoryLineEnding';
		$wgHooks['ApiCheckCanExecute'][] = 'Wikibase\RepoHooks::onApiCheckCanExecute';
		$wgHooks['SetupAfterCache'][] = 'Wikibase\RepoHooks::onSetupAfterCache';
		$wgHooks['ShowSearchHit'][] = 'Hooks\ShowSearchHitHandler::onShowSearchHit';
		$wgHooks['ShowSearchHitTitle'][] = 'Wikibase\RepoHooks::onShowSearchHitTitle';
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
		$wgHooks['CirrusSearchScoreBuilder'][] = '\Wikibase\RepoHooks::onCirrusSearchScoreBuilder';

		// update hooks
		$wgHooks['LoadExtensionSchemaUpdates'][] = 'Wikibase\Repo\Store\Sql\ChangesSubscriptionSchemaUpdater::onSchemaUpdate';

		// Resource Loader Modules:
		$wgResourceModules = array_merge(
			$wgResourceModules,
			include __DIR__ . '/resources/Resources.php'
		);

		$wgWBRepoSettings = array_merge(
			require __DIR__ . '/../lib/config/WikibaseLib.default.php',
			require __DIR__ . '/config/Wikibase.default.php'
		);

		// Field weight profiles. These profiles specify relative weights
		// of label fields for different languages, e.g. exact language match
		// vs. fallback language match.
		$wgWBRepoSettings['entitySearch']['prefixSearchProfiles'] =
			require __DIR__ . '/config/EntityPrefixSearchProfiles.php';
		// Wikibase prefix search scoring profile for CirrusSearch.
		// This profile applies to the whole document.
		// These configurations define how the results are ordered.
		// The names should be distinct from other Cirrus rescoring profile, so
		// prefixing with 'wikibase' is recommended.
		$wgWBRepoSettings['entitySearch']['rescoreProfiles'] =
			require __DIR__ . '/config/ElasticSearchRescoreProfiles.php';
	}

}
