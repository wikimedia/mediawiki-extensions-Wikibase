<?php

namespace Wikibase\Repo;

use GraphQL\GraphQL;
use MediaWiki\Api\ApiEditPage;
use MediaWiki\Api\ApiQuery;
use MediaWiki\Api\Hook\ApiCheckCanExecuteHook;
use MediaWiki\Api\Hook\ApiMain__onExceptionHook;
use MediaWiki\Api\Hook\ApiQuery__moduleManagerHook;
use MediaWiki\Api\Hook\APIQuerySiteInfoGeneralInfoHook;
use MediaWiki\Content\Content;
use MediaWiki\Content\Hook\ContentModelCanBeUsedOnHook;
use MediaWiki\Hook\BeforePageDisplayHook;
use MediaWiki\Hook\FormatAutocommentsHook;
use MediaWiki\Hook\ImportHandleRevisionXMLTagHook;
use MediaWiki\Hook\InfoActionHook;
use MediaWiki\Hook\MaintenanceShellStartHook;
use MediaWiki\Hook\MediaWikiServicesHook;
use MediaWiki\Hook\NamespaceIsMovableHook;
use MediaWiki\Hook\OutputPageBodyAttributesHook;
use MediaWiki\Hook\OutputPageParserOutputHook;
use MediaWiki\Hook\PageHistoryLineEndingHook;
use MediaWiki\Hook\ParserFirstCallInitHook;
use MediaWiki\Hook\ParserOptionsRegisterHook;
use MediaWiki\Hook\SidebarBeforeOutputHook;
use MediaWiki\Hook\SkinTemplateNavigation__UniversalHook;
use MediaWiki\Hook\TitleGetRestrictionTypesHook;
use MediaWiki\Hook\UnitTestsListHook;
use MediaWiki\MediaWikiServices;
use MediaWiki\Output\OutputPage;
use MediaWiki\Page\Hook\BeforeDisplayNoArticleTextHook;
use MediaWiki\Page\Hook\RevisionFromEditCompleteHook;
use MediaWiki\Preferences\Hook\GetPreferencesHook;
use MediaWiki\Registration\ExtensionRegistry;
use MediaWiki\ResourceLoader\CodexModule;
use MediaWiki\ResourceLoader\Context;
use MediaWiki\ResourceLoader\Hook\ResourceLoaderRegisterModulesHook;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Revision\SlotRecord;
use MediaWiki\Skin\Skin;
use MediaWiki\Skins\Hook\SkinPageReadyConfigHook;
use MediaWiki\SpecialPage\Hook\SpecialPage_initListHook;
use MediaWiki\StubObject\StubUserLang;
use MediaWiki\Title\Title;
use RuntimeException;
use UnexpectedValueException;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\Lib\Formatters\AutoCommentFormatter;
use Wikibase\Lib\Hooks\WikibaseContentLanguagesHook;
use Wikibase\Lib\LibHooks;
use Wikibase\Lib\ParserFunctions\CommaSeparatedList;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\StaticContentLanguages;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\UnionContentLanguages;
use Wikibase\Lib\WikibaseContentLanguages;
use Wikibase\Repo\Api\MetaDataBridgeConfig;
use Wikibase\Repo\Api\ModifyEntity;
use Wikibase\Repo\Content\EntityContent;
use Wikibase\Repo\Content\EntityHandler;
use Wikibase\Repo\GraphQLPrototype\SpecialWikibaseGraphQL;
use Wikibase\Repo\Hooks\Helpers\OutputPageEntityViewChecker;
use Wikibase\Repo\Hooks\InfoActionHookHandler;
use Wikibase\Repo\Hooks\OutputPageEntityIdReader;
use Wikibase\Repo\Hooks\SidebarBeforeOutputHookHandler;
use Wikibase\Repo\ParserOutput\PlaceholderEmittingEntityTermsView;
use Wikibase\Repo\ParserOutput\TermboxFlag;
use Wikibase\Repo\ParserOutput\TermboxView;
use Wikibase\Repo\Store\RateLimitingIdGenerator;
use Wikibase\Repo\Store\Sql\SqlSubscriptionLookup;
use Wikibase\View\StatementSectionsView;
use Wikibase\View\ViewHooks;
use Wikimedia\Rdbms\IDBAccessObject;

/**
 * File defining the hook handlers for the Wikibase extension.
 *
 * @license GPL-2.0-or-later
 */
final class RepoHooks implements
	APIQuerySiteInfoGeneralInfoHook,
	ApiCheckCanExecuteHook,
	ApiMain__onExceptionHook,
	ApiQuery__moduleManagerHook,
	BeforePageDisplayHook,
	BeforeDisplayNoArticleTextHook,
	ContentModelCanBeUsedOnHook,
	FormatAutocommentsHook,
	ImportHandleRevisionXMLTagHook,
	InfoActionHook,
	MaintenanceShellStartHook,
	MediaWikiServicesHook,
	NamespaceIsMovableHook,
	OutputPageBodyAttributesHook,
	OutputPageParserOutputHook,
	PageHistoryLineEndingHook,
	ParserFirstCallInitHook,
	ParserOptionsRegisterHook,
	ResourceLoaderRegisterModulesHook,
	RevisionFromEditCompleteHook,
	SidebarBeforeOutputHook,
	SkinPageReadyConfigHook,
	SkinTemplateNavigation__UniversalHook,
	TitleGetRestrictionTypesHook,
	UnitTestsListHook,
	WikibaseContentLanguagesHook,
	GetPreferencesHook,
	SpecialPage_initListHook
{
	/**
	 * We implement this solely to replace the standard message that
	 * is shown when an entity does not exists.
	 *
	 * @inheritDoc
	 */
	public function onBeforeDisplayNoArticleText( $article ) {
		$namespaceLookup = WikibaseRepo::getLocalEntityNamespaceLookup();
		$contentFactory = WikibaseRepo::getEntityContentFactory();

		$ns = $article->getTitle()->getNamespace();
		$oldid = $article->getOldID();

		if ( !$oldid && $namespaceLookup->isEntityNamespace( $ns ) ) {
			$entityType = $namespaceLookup->getEntityType( $ns );
			$handler = $contentFactory->getContentHandlerForType( $entityType );
			$handler->showMissingEntity( $article->getTitle(), $article->getContext() );

			return false;
		}

		return true;
	}

	/**
	 * Handler for the BeforePageDisplay hook, that conditionally adds the wikibase
	 * mobile styles and injects the wikibase.ui.entitysearch module replacing the
	 * native search box with the entity selector widget.
	 *
	 * It additionally schedules a WikibasePingback
	 *
	 * @param OutputPage $out
	 * @param Skin $skin
	 */
	public function onBeforePageDisplay( $out, $skin ): void {
		$entityNamespaceLookup = WikibaseRepo::getEntityNamespaceLookup();
		$namespace = $out->getTitle()->getNamespace();
		$isEntityTitle = $entityNamespaceLookup->isNamespaceWithEntities( $namespace );
		$settings = WikibaseRepo::getSettings();

		if ( $settings->getSetting( 'enableEntitySearchUI' ) === true ) {
			$skinName = $skin->getSkinName();
			if ( $skinName === 'vector-2022' ) {
				// IMPORTANT: Don't load Vue.js here as that would be a considerable amount of JS to pull on page load
				// It will be lazy loaded by the search client via the SkinPageReadyConfig hook.
				// See also onSkinPageReadyConfig() for further search setup.
				if ( $settings->getSetting( 'tmpEnableScopedTypeaheadSearch' ) ) {
					$out->addModuleStyles( 'wikibase.vector.scopedTypeaheadSearchStyles' );
				}
			} elseif ( $skinName !== 'minerva' ) {
				// Minerva uses its own search widget.
				$out->addModules( 'wikibase.ui.entitysearch' );
			}
		}

		if ( $settings->getSetting( 'wikibasePingback' ) ) {
			WikibasePingback::schedulePingback();
		}

		if ( $isEntityTitle && WikibaseRepo::getMobileSite() ) {
			$out->addModules( 'wikibase.mobile' );

			$useNewTermbox = $settings->getSetting( 'termboxEnabled' );
			$entityType = $entityNamespaceLookup->getEntityType( $namespace );
			$isEntityTypeWithTermbox = $entityType === Item::ENTITY_TYPE
				|| $entityType === Property::ENTITY_TYPE;

			if ( $useNewTermbox && $isEntityTypeWithTermbox ) {
				$out->addModules( 'wikibase.termbox' );
				$out->addModuleStyles( [ 'wikibase.termbox.styles' ] );
			}
		}
	}

	/**
	 * Handler for the MediaWikiServices hook, completing the content and namespace setup.
	 * This updates the $wgContentHandlers and $wgNamespaceContentModels registries
	 * according to information provided by entity type definitions and the entityNamespaces
	 * setting for the local entity source.
	 * Note that we must not access any MediaWiki core services here (except for the hook container);
	 * see the warning in {@link \MediaWiki\Hook\MediaWikiServicesHook::onMediaWikiServices()}.
	 *
	 * @param MediaWikiServices $services
	 * @return bool|void True or no return value to continue or false to abort
	 */
	public function onMediaWikiServices( $services ) {
		global $wgContentHandlers,
			$wgNamespaceContentModels;

		if ( WikibaseRepo::getSettings( $services )->getSetting( 'defaultEntityNamespaces' ) ) {
			self::defaultEntityNamespaces();
		}

		$namespaces = WikibaseRepo::getLocalEntitySource( $services )->getEntityNamespaceIds();
		$namespaceLookup = WikibaseRepo::getEntityNamespaceLookup( $services );

		// Register entity namespaces.
		// Note that $wgExtraNamespaces and $wgNamespaceAliases have already been processed at this
		// point and should no longer be touched.
		$contentModelIds = WikibaseRepo::getContentModelMappings( $services );

		foreach ( $namespaces as $entityType => $namespace ) {
			// TODO: once there is a mechanism for registering the default content model for
			// slots other than the main slot, do that!
			// XXX: we should probably not just ignore $entityTypes that don't match $contentModelIds.
			if ( !isset( $wgNamespaceContentModels[$namespace] )
				&& isset( $contentModelIds[$entityType] )
				&& $namespaceLookup->getEntitySlotRole( $entityType ) === SlotRecord::MAIN
			) {
				$wgNamespaceContentModels[$namespace] = $contentModelIds[$entityType];
			}
		}

		// Register callbacks for instantiating ContentHandlers for EntityContent.
		foreach ( $contentModelIds as $entityType => $model ) {
			$wgContentHandlers[$model] = function () use ( $services, $entityType ) {
				$entityContentFactory = WikibaseRepo::getEntityContentFactory( $services );
				return $entityContentFactory->getContentHandlerForType( $entityType );
			};
		}
	}

	/**
	 * @suppress PhanUndeclaredConstant
	 */
	private static function defaultEntityNamespaces(): void {
		global $wgExtraNamespaces, $wgNamespacesToBeSearchedDefault;

		$baseNs = 120;

		self::ensureConstant( 'WB_NS_ITEM', $baseNs );
		self::ensureConstant( 'WB_NS_ITEM_TALK', $baseNs + 1 );
		self::ensureConstant( 'WB_NS_PROPERTY', $baseNs + 2 );
		self::ensureConstant( 'WB_NS_PROPERTY_TALK', $baseNs + 3 );

		$wgExtraNamespaces[WB_NS_ITEM] = 'Item';
		$wgExtraNamespaces[WB_NS_ITEM_TALK] = 'Item_talk';
		$wgExtraNamespaces[WB_NS_PROPERTY] = 'Property';
		$wgExtraNamespaces[WB_NS_PROPERTY_TALK] = 'Property_talk';

		$wgNamespacesToBeSearchedDefault[WB_NS_ITEM] = true;
	}

	/**
	 * Ensure that a constant is set to a certain (integer) value,
	 * defining it or checking its value if it was already defined.
	 */
	private static function ensureConstant( string $name, int $value ): void {
		if ( !defined( $name ) ) {
			define( $name, $value );
		} elseif ( constant( $name ) !== $value ) {
			$actual = constant( $name );
			throw new UnexpectedValueException(
				"Expecting constant $name to be set to $value instead of $actual"
			);
		}
	}

	/** @inheritDoc */
	public function onUnitTestsList( &$paths ) {
		$paths[] = __DIR__ . '/../tests/phpunit/';
		$paths[] = __DIR__ . '/../rest-api/tests/phpunit/';
		$paths[] = __DIR__ . '/../domains/crud/tests/phpunit/';
		$paths[] = __DIR__ . '/../domains/search/tests/phpunit/';
	}

	/** @inheritDoc */
	public function onNamespaceIsMovable( $ns, &$movable ) {
		if ( self::isNamespaceUsedByLocalEntities( $ns ) ) {
			$movable = false;
		}
	}

	private static function isNamespaceUsedByLocalEntities( int $namespace ): bool {
		$namespaceLookup = WikibaseRepo::getEntityNamespaceLookup();

		// TODO: this logic seems badly misplaced, probably WikibaseRepo should be asked and be
		// providing different and more appropriate EntityNamespaceLookup instance
		// However looking at the current use of EntityNamespaceLookup, it seems to be used
		// for different kinds of things, which calls for more systematic audit and changes.
		if ( !$namespaceLookup->isEntityNamespace( $namespace ) ) {
			return false;
		}

		$entityType = $namespaceLookup->getEntityType( $namespace );

		if ( $entityType === null ) {
			return false;
		}

		$entitySource = WikibaseRepo::getEntitySourceDefinitions()->getDatabaseSourceForEntityType(
			$entityType
		);
		if ( $entitySource === null ) {
			return false;
		}

		$localEntitySourceName = WikibaseRepo::getSettings()->getSetting( 'localEntitySourceName' );
		if ( $entitySource->getSourceName() === $localEntitySourceName ) {
			return true;
		}

		return false;
	}

	/** @inheritDoc */
	public function onRevisionFromEditComplete(
		$wikiPage,
		$rev,
		$originalRevId,
		$user,
		&$tags
	) {
		$entityContentFactory = WikibaseRepo::getEntityContentFactory();

		if ( $entityContentFactory->isEntityContentModel( $wikiPage->getContent()->getModel() ) ) {
			self::notifyEntityStoreWatcherOnUpdate(
				// @phan-suppress-next-line PhanTypeMismatchArgumentSuperType Content model is checked
				$rev->getContent( SlotRecord::MAIN ),
				$rev
			);

			$notifier = WikibaseRepo::getChangeNotifier();
			$parentId = $rev->getParentId();

			if ( !$parentId ) {
				$notifier->notifyOnPageCreated( $rev );
			} else {
				$parent = MediaWikiServices::getInstance()
					->getRevisionLookup()
					->getRevisionById( $parentId );

				if ( !$parent ) {
					wfLogWarning(
						__METHOD__ . ': Cannot notify on page modification: '
						. 'failed to load parent revision with ID ' . $parentId
					);
				} else {
					$notifier->notifyOnPageModified( $rev, $parent );
				}
			}
		}
	}

	private static function notifyEntityStoreWatcherOnUpdate(
		EntityContent $content,
		RevisionRecord $revision
	) {
		$watcher = WikibaseRepo::getEntityStoreWatcher();

		// Notify storage/lookup services that the entity was updated. Needed to track page-level changes.
		// May be redundant in some cases. Take care not to cause infinite regress.
		if ( $content->isRedirect() ) {
			$watcher->redirectUpdated(
				$content->getEntityRedirect(),
				$revision->getId()
			);
		} else {
			$watcher->entityUpdated( new EntityRevision(
				$content->getEntity(),
				$revision->getId(),
				$revision->getTimestamp()
			) );
		}
	}

	/**
	 * NOTE: Might make sense to put the inner functionality into a well structured Preferences file once this
	 *       becomes more.
	 * @inheritDoc
	 */
	public function onGetPreferences( $user, &$preferences ) {
		$preferences['wb-acknowledgedcopyrightversion'] = [
			'type' => 'api',
		];

		$preferences['wb-dismissleavingsitenotice'] = [
			'type' => 'api',
		];

		$preferences['wb-reftabs-mode'] = [
			'type' => 'api',
		];

		$preferences['wikibase-entitytermsview-showEntitytermslistview'] = [
			'type' => 'toggle',
			'label-message' => 'wikibase-setting-entitytermsview-showEntitytermslistview',
			'help-message' => 'wikibase-setting-entitytermsview-showEntitytermslistview-help',
			'section' => 'rendering/advancedrendering',
			'default' => '1',
		];

		$preferences['wb-dont-show-again-mul-popup'] = [
			'type' => 'api',
		];
	}

	/**
	 * Called after fetching the core default user options.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/UserGetDefaultOptions
	 *
	 * @param array &$defaultOptions
	 */
	public static function onUserGetDefaultOptions( array &$defaultOptions ) {
		// pre-select default language in the list of fallback languages
		$defaultLang = $defaultOptions['language'];
		$defaultOptions[ 'wb-languages-' . $defaultLang ] = 1;
	}

	/** @inheritDoc */
	public function onPageHistoryLineEnding( $historyAction, &$row, &$s, &$classes, &$attribs ) {
		// Note: This assumes that HistoryPager::getTitle returns a Title.
		$entityContentFactory = WikibaseRepo::getEntityContentFactory();
		$services = MediaWikiServices::getInstance();

		$title = $historyAction->getTitle();
		$revisionRecord = $services->getRevisionFactory()->newRevisionFromRow(
			$row,
			IDBAccessObject::READ_NORMAL,
			$title
		);
		$linkTarget = $revisionRecord->getPageAsLinkTarget();

		if ( $entityContentFactory->isEntityContentModel( $title->getContentModel() )
			&& $title->getLatestRevID() !== $revisionRecord->getId()
			&& $services->getPermissionManager()->quickUserCan(
				'edit',
				$historyAction->getUser(),
				$linkTarget
			)
			&& !$revisionRecord->isDeleted( RevisionRecord::DELETED_TEXT )
		) {
			$link = $services->getLinkRenderer()->makeKnownLink(
				$linkTarget,
				$historyAction->msg( 'wikibase-restoreold' )->text(),
				[],
				[
					'action' => 'edit',
					'restore' => $revisionRecord->getId(),
				]
			);

			$s .= ' ' . $historyAction->msg( 'parentheses' )->rawParams( $link )->escaped();
		}
	}

	/**
	 * @todo T282549 Consider moving some of this logic into a place where it can be more adequately tested
	 *
	 * @inheritDoc
	 */
	public function onSkinTemplateNavigation__Universal( $sktemplate, &$links ): void {
		$entityContentFactory = WikibaseRepo::getEntityContentFactory();

		$title = $sktemplate->getRelevantTitle();

		if ( $entityContentFactory->isEntityContentModel( $title->getContentModel() ) ) {
			unset( $links['views']['edit'] );
			unset( $links['views']['viewsource'] );

			if ( MediaWikiServices::getInstance()->getPermissionManager()
					->quickUserCan( 'edit', $sktemplate->getUser(), $title )
			) {
				$out = $sktemplate->getOutput();
				$request = $sktemplate->getRequest();
				$old = !$out->isRevisionCurrent()
					&& !$request->getCheck( 'diff' );

				$restore = $request->getCheck( 'restore' );

				if ( $old || $restore ) {
					// insert restore tab into views array, at the second position

					$revid = $restore
						? $request->getText( 'restore' )
						: $out->getRevisionId();

					$rev = MediaWikiServices::getInstance()
						->getRevisionLookup()
						->getRevisionById( $revid );
					if ( !$rev || $rev->isDeleted( RevisionRecord::DELETED_TEXT ) ) {
						return;
					}

					$head = array_slice( $links['views'], 0, 1 );
					$tail = array_slice( $links['views'], 1 );
					$neck = [
						'restore' => [
							'class' => $restore ? 'selected' : false,
							'text' => $sktemplate->getLanguage()->ucfirst(
								wfMessage( 'wikibase-restoreold' )->text()
							),
							'href' => $title->getLocalURL( [
								'action' => 'edit',
								'restore' => $revid,
							] ),
						],
					];

					$links['views'] = array_merge( $head, $neck, $tail );
				}
			}
		}
	}

	/**
	 * Used to append a css class to the body, so the page can be identified as Wikibase item page.
	 * @inheritDoc
	 */
	public function onOutputPageBodyAttributes( $out, $sk, &$bodyAttrs ): void {
		$outputPageEntityIdReader = new OutputPageEntityIdReader(
			new OutputPageEntityViewChecker( WikibaseRepo::getEntityContentFactory() ),
			WikibaseRepo::getEntityIdParser()
		);

		$entityId = $outputPageEntityIdReader->getEntityIdFromOutputPage( $out );

		if ( $entityId === null ) {
			return;
		}

		// TODO: preg_replace kind of ridiculous here, should probably change the ENTITY_TYPE constants instead
		$entityType = preg_replace( '/^wikibase-/i', '', $entityId->getEntityType() );

		// add class to body so it's clear this is a wb item:
		$bodyAttrs['class'] .= ' wb-entitypage wb-' . $entityType . 'page';
		// add another class with the ID of the item:
		$bodyAttrs['class'] .= ' wb-' . $entityType . 'page-' . $entityId->getSerialization();

		if ( $sk->getRequest()->getCheck( 'diff' ) ) {
			$bodyAttrs['class'] .= ' wb-diffpage';
		}

		if ( $out->getTitle() && $out->getRevisionId() !== $out->getTitle()->getLatestRevID() ) {
			$bodyAttrs['class'] .= ' wb-oldrevpage';
		}
	}

	/**
	 * This implementation causes the execution of ApiEditPage (action=edit) to fail
	 * for all namespaces reserved for Wikibase entities. This prevents direct text-level editing
	 * of structured data, and it also prevents other types of content being created in these
	 * namespaces.
	 *
	 * @inheritDoc
	 */
	public function onApiCheckCanExecute( $module, $user, &$message ) {
		if ( $module instanceof ApiEditPage ) {
			$params = $module->extractRequestParams();
			$pageObj = $module->getTitleOrPageId( $params );
			$namespace = $pageObj->getTitle()->getNamespace();

			// XXX FIXME: ApiEditPage doesn't expose the slot, but this 'magically' works if the edit is
			// to a MAIN slot and the entity is stored in a non-MAIN slot, because it falls back.
			// To be verified that this keeps working once T200570 is done in MediaWiki itself.
			$slots = $params['slots'] ?? [ SlotRecord::MAIN ];

			/**
			 * Don't make Wikibase check if a user can execute when the namespace in question does
			 * not refer to a namespace used locally for Wikibase entities.
			 */
			$localEntitySource = WikibaseRepo::getLocalEntitySource();
			if ( !in_array( $namespace, $localEntitySource->getEntityNamespaceIds() ) ) {
					return true;
			}

			$entityContentFactory = WikibaseRepo::getEntityContentFactory();
			$entityTypes = WikibaseRepo::getEnabledEntityTypes();

			$contentHandlerFactory = MediaWikiServices::getInstance()->getContentHandlerFactory();

			foreach ( $entityContentFactory->getEntityContentModels() as $contentModel ) {
				/** @var EntityHandler $handler */
				$handler = $contentHandlerFactory->getContentHandler( $contentModel );
				'@phan-var EntityHandler $handler';

				if ( !in_array( $handler->getEntityType(), $entityTypes ) ) {
					// If the entity type isn't enabled then Wikibase shouldn't be checking anything.
					continue;
				}

				if (
					$handler->getEntityNamespace() === $namespace &&
					in_array( $handler->getEntitySlotRole(), $slots, true )
				) {
					// XXX: This is most probably redundant with setting
					// ContentHandler::supportsDirectApiEditing to false.
					// trying to use ApiEditPage on an entity namespace
					$params = $module->extractRequestParams();

					// allow undo
					if ( $params['undo'] > 0 ) {
						return true;
					}

					// fail
					$message = [
						'wikibase-no-direct-editing',
						$pageObj->getTitle()->getNsText(),
					];

					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Implemented to prevent people from protecting pages from being
	 * created or moved in an entity namespace (which is pointless).
	 *
	 * @inheritDoc
	 */
	public function onTitleGetRestrictionTypes( $title, &$types ) {
		$namespaceLookup = WikibaseRepo::getLocalEntityNamespaceLookup();

		if ( $namespaceLookup->isEntityNamespace( $title->getNamespace() ) ) {
			// Remove create and move protection for Wikibase namespaces
			$types = array_diff( $types, [ 'create', 'move' ] );
		}
	}

	/**
	 * Hook handler for AbuseFilter's AbuseFilter-contentToString hook, implemented
	 * to provide a custom text representation of Entities for filtering.
	 *
	 * Called when converting a Content object to a string to which
	 * filters can be applied. If the hook function returns true, Content::getTextForSearchIndex()
	 * will be used for non-text content.
	 *
	 * @param Content $content
	 * @param ?string &$text Set this to the desired text
	 * @return bool|void True or no return value to continue or false to abort
	 */
	public function onAbuseFilter_contentToString( Content $content, ?string &$text ) { // phpcs:ignore MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName, Generic.Files.LineLength.TooLong
		if ( $content instanceof EntityContent ) {
			$text = $content->getTextForFilters();

			return false;
		}

		return true;
	}

	/**
	 * Handler for the FormatAutocomments hook, implementing localized formatting
	 * for machine readable autocomments generated by SummaryFormatter.
	 *
	 * @inheritDoc
	 */
	public function onFormatAutocomments( &$comment, $pre, $auto, $post, $title, $local, $wiki ) {
		// phpcs:ignore MediaWiki.Usage.DeprecatedGlobalVariables.Deprecated$wgTitle
		global $wgLang, $wgTitle;

		// If it is possible to avoid loading the whole page then the code will be lighter on the server.
		if ( !( $title instanceof Title ) ) {
			$title = $wgTitle;
		}

		if ( !( $title instanceof Title ) ) {
			return;
		}

		$namespaceLookup = WikibaseRepo::getEntityNamespaceLookup();
		$entityType = $namespaceLookup->getEntityType( $title->getNamespace() );
		if ( $entityType === null ) {
			return;
		}

		if ( $wgLang instanceof StubUserLang ) {
			StubUserLang::unstub( $wgLang );
		}

		$formatter = new AutoCommentFormatter( $wgLang, [ 'wikibase-' . $entityType, 'wikibase-entity' ] );
		$formattedComment = $formatter->formatAutoComment( $auto );

		if ( is_string( $formattedComment ) ) {
			$comment = $formatter->wrapAutoComment( $pre, $formattedComment, $post );
		}
	}

	/**
	 * Used to transfer 'wikibase-view-chunks' and entity data from ParserOutput to OutputPage.
	 * @inheritDoc
	 */
	public function onOutputPageParserOutput( $outputPage, $parserOutput ): void {
		// Set in PlaceholderEmittingEntityTermsView.
		$placeholders = $parserOutput->getExtensionData( 'wikibase-view-chunks' );
		if ( $placeholders !== null ) {
			$outputPage->setProperty( 'wikibase-view-chunks', $placeholders );
		}

		// Set in PlaceholderEmittingEntityTermsView.
		$termsListItems = $parserOutput->getExtensionData( 'wikibase-terms-list-items' );
		if ( $termsListItems !== null ) {
			$outputPage->setProperty( 'wikibase-terms-list-items', $termsListItems );
		}

		// Set in PlaceholderEmittingEntityTermsView
		$entityLabels = $parserOutput->getExtensionData( 'wikibase-entity-labels' );
		if ( $entityLabels !== null ) {
			$outputPage->setProperty( 'wikibase-entity-labels', $entityLabels );
		}

		// Used in ViewEntityAction and EditEntityAction to override the page HTML title
		// with the label, if available, or else the id. Passed via parser output
		// and output page to save overhead of fetching content and accessing an entity
		// on page view.
		$meta = $parserOutput->getExtensionData( 'wikibase-meta-tags' );
		$outputPage->setProperty( 'wikibase-meta-tags', $meta );

		$outputPage->setProperty(
			TermboxView::TERMBOX_MARKUP,
			$parserOutput->getExtensionData( TermboxView::TERMBOX_MARKUP )
		);

		// Array with <link rel="alternate"> tags for the page HEAD.
		$alternateLinks = $parserOutput->getExtensionData( 'wikibase-alternate-links' );
		if ( $alternateLinks !== null ) {
			foreach ( $alternateLinks as $link ) {
				$outputPage->addLink( $link );
			}
		}
	}

	/**
	 * Handler for the ContentModelCanBeUsedOn hook, used to prevent pages of inappropriate type
	 * to be placed in an entity namespace.
	 *
	 * @inheritDoc
	 */
	public function onContentModelCanBeUsedOn( $contentModel, $title, &$ok ) {
		$namespaceLookup = WikibaseRepo::getEntityNamespaceLookup();
		$contentModelIds = WikibaseRepo::getContentModelMappings();

		// Find any entity type that is mapped to the title namespace
		$expectedEntityType = $namespaceLookup->getEntityType( $title->getNamespace() );

		// If we don't expect an entity type, then don't check anything else.
		if ( $expectedEntityType === null ) {
			return true;
		}

		// If the entity type is not from the local source, don't check anything else
		$entitySource = WikibaseRepo::getEntitySourceDefinitions()->getDatabaseSourceForEntityType( $expectedEntityType );
		if ( $entitySource === null ||
			$entitySource->getSourceName() !== WikibaseRepo::getLocalEntitySource()->getSourceName()
		) {
			return true;
		}

		// XXX: If the slot is not the main slot, then assume someone isn't somehow trying
		// to add another content type there. We want to actually check per slot type here.
		// This should be fixed with https://gerrit.wikimedia.org/r/#/c/mediawiki/core/+/434544/
		$expectedSlot = $namespaceLookup->getEntitySlotRole( $expectedEntityType );
		if ( $expectedSlot !== SlotRecord::MAIN ) {
			return true;
		}

		// If the namespace is an entity namespace, the content model
		// must be the model assigned to that namespace.
		$expectedModel = $contentModelIds[$expectedEntityType];
		if ( $expectedModel !== $contentModel ) {
			$ok = false;
			return false;
		}

		return true;
	}

	/**
	 * Exposes configuration values to the action=query&meta=siteinfo API, including lists of
	 * property and data value types, sparql endpoint, and several base URLs and URIs.
	 *
	 * @inheritDoc
	 */
	public function onAPIQuerySiteInfoGeneralInfo( $module, &$results ) {
		$repoSettings = WikibaseRepo::getSettings();
		$dataTypes = WikibaseRepo::getDataTypeFactory()->getTypes();
		$propertyTypes = [];

		foreach ( $dataTypes as $id => $type ) {
			$propertyTypes[$id] = [ 'valuetype' => $type->getDataValueType() ];
		}

		$results['wikibase-propertytypes'] = $propertyTypes;

		$results['wikibase-conceptbaseuri'] = WikibaseRepo::getLocalEntitySource()->getConceptBaseUri();

		$geoShapeStorageBaseUrl = $repoSettings->getSetting( 'geoShapeStorageBaseUrl' );
		$results['wikibase-geoshapestoragebaseurl'] = $geoShapeStorageBaseUrl;

		$tabularDataStorageBaseUrl = $repoSettings->getSetting( 'tabularDataStorageBaseUrl' );
		$results['wikibase-tabulardatastoragebaseurl'] = $tabularDataStorageBaseUrl;

		$sparqlEndpoint = $repoSettings->getSetting( 'sparqlEndpoint' );
		if ( is_string( $sparqlEndpoint ) ) {
			$results['wikibase-sparql'] = $sparqlEndpoint;
		}
	}

	/**
	 * Called by Import.php. Implemented to prevent the import of entities.
	 * @inheritDoc
	 */
	public function onImportHandleRevisionXMLTag( $reader, $pageInfo, $revisionInfo ) {
		if ( isset( $revisionInfo['model'] ) ) {
			$contentModels = WikibaseRepo::getContentModelMappings();
			$allowImport = WikibaseRepo::getSettings()->getSetting( 'allowEntityImport' );

			if ( !$allowImport && in_array( $revisionInfo['model'], $contentModels ) ) {
				// Skip entities.
				// XXX: This is rather rough.
				throw new RuntimeException(
					'To avoid ID conflicts, the import of Wikibase entities is not supported.'
						. ' You can enable imports using the "allowEntityImport" setting.'
				);
			}
		}
	}

	/**
	 * Add Concept URI link to the toolbox section of the sidebar.
	 *
	 * @inheritDoc
	 */
	public function onSidebarBeforeOutput( $skin, &$sidebar ): void {
		$hookHandler = new SidebarBeforeOutputHookHandler(
			WikibaseRepo::getLocalEntitySource()->getConceptBaseUri(),
			WikibaseRepo::getEntityIdLookup(),
			WikibaseRepo::getEntityLookup(),
			WikibaseRepo::getEntityNamespaceLookup(),
			WikibaseRepo::getLogger()
		);

		$conceptUriLink = $hookHandler->buildConceptUriLink( $skin );

		if ( $conceptUriLink === null ) {
			return;
		}

		$sidebar['TOOLBOX']['wb-concept-uri'] = $conceptUriLink;
	}

	/** @inheritDoc */
	public function onResourceLoaderRegisterModules( $rl ): void {
		$moduleTemplate = [
			'localBasePath' => __DIR__ . '/..',
			'remoteExtPath' => 'Wikibase/repo',
		];

		$modules = [
			'wikibase.WikibaseContentLanguages' => $moduleTemplate + [
				'packageFiles' => [
					'resources/wikibase.WikibaseContentLanguages.js',

					[
						'name' => 'resources/contentLanguages.json',
						'callback' => function () {
							$contentLanguages = WikibaseRepo::getWikibaseContentLanguages();
							return [
								WikibaseContentLanguages::CONTEXT_MONOLINGUAL_TEXT => $contentLanguages
									->getContentLanguages( WikibaseContentLanguages::CONTEXT_MONOLINGUAL_TEXT )
									->getLanguages(),
								WikibaseContentLanguages::CONTEXT_TERM => $contentLanguages
									->getContentLanguages( WikibaseContentLanguages::CONTEXT_TERM )
									->getLanguages(),
							];
						},
					],
				],
				'dependencies' => [
					'util.ContentLanguages',
					'util.inherit',
					'wikibase',
					'wikibase.getLanguageNameByCode',
				],
			],
			'wikibase.special.languageLabelDescriptionAliases' => $moduleTemplate + [
				'scripts' => [
					'resources/wikibase.special/wikibase.special.languageLabelDescriptionAliases.js',
				],
				'dependencies' => [
					'wikibase.getLanguageNameByCode',
					'oojs-ui',
				],
				'messages' => [
					'wikibase-label-edit-placeholder',
					'wikibase-label-edit-placeholder-language-aware',
					'wikibase-label-edit-placeholder-mul',
					'wikibase-description-edit-placeholder',
					'wikibase-description-edit-placeholder-language-aware',
					'wikibase-item-description-edit-not-supported',
					'wikibase-property-description-edit-not-supported',
					'wikibase-aliases-edit-placeholder',
					'wikibase-aliases-edit-placeholder-language-aware',
					'wikibase-aliases-edit-placeholder-mul',
				],
			],
		];

		$isUlsLoaded = ExtensionRegistry::getInstance()->isLoaded( 'UniversalLanguageSelector' );
		if ( $isUlsLoaded ) {
			$modules['wikibase.WikibaseContentLanguages']['dependencies'][] = 'ext.uls.languagenames';
			$modules['wikibase.special.languageLabelDescriptionAliases']['dependencies'][] = 'ext.uls.mediawiki';
		}

		// temporarily register this RL module only if the feature flag is enabled,
		// so that wikis without the feature flag don’t even pay the small cost of loading the module *definition*
		// (when the feature stabilizes, this should move into repo/resources/Resources.php: T385446)
		$settings = WikibaseRepo::getSettings();
		if ( $settings->getSetting( 'tmpEnableScopedTypeaheadSearch' ) ) {
			$modules['wikibase.vector.scopedTypeaheadSearch'] = $moduleTemplate + [
				'class' => "Wikibase\\Repo\\View\\ScopedTypeaheadCodexModule",
				'packageFiles' => [
					'resources/wikibase.vector.scopedtypeaheadsearch/init.js',
					'resources/wikibase.vector.scopedtypeaheadsearch/ScopedTypeaheadSearch.vue',
					[
						'name' => 'resources/wikibase.vector.scopedtypeaheadsearch/scopedTypeaheadSearchConfig.json',
						'callback' => function() {
							return WikibaseRepo::getScopedTypeaheadSearchConfig()->getConfiguration();
						},
					],
				],
				'codexComponents' => [
					'CdxSelect',
					'CdxTypeaheadSearch',
				],
				'dependencies' => [
					'vue',
				],
				'messages' => [
					'searchbutton',
					'searchsuggest-search',
					'searchsuggest-containing-html',
					'wikibase-scoped-search-search-entities',
					'wikibase-scoped-search-search-entities-description',
				],
			];
			$modules['wikibase.vector.scopedTypeaheadSearchStyles'] = $moduleTemplate + [
				"styles" => [
					'resources/wikibase.vector.scopedTypeaheadSearch.less',
				],
			];
		}

		// temporarily register this RL module only if the feature flag is enabled,
		// so that wikis without the feature flag don’t even pay the small cost of loading the module *definition*
		// (when the feature stabilizes, this should move into repo/resources/Resources.php: T395783)
		if ( $settings->getSetting( 'tmpMobileEditingUI' ) ) {
			$modules['wikibase.wbui2025.entityView.styles'] = $moduleTemplate + [
				'styles' => [
					'resources/wikibase.wbui2025/wikibase.wbui2025.qualifiers.less',
					'resources/wikibase.wbui2025/wikibase.wbui2025.statementSections.less',
					'resources/wikibase.wbui2025/wikibase.wbui2025.statementGroupView.less',
					'resources/wikibase.wbui2025/wikibase.wbui2025.references.less',
					'resources/wikibase.wbui2025/wikibase.wbui2025.snakValue.less',
					'resources/wikibase.wbui2025/wikibase.wbui2025.mainSnak.less',
					'../view/resources/jquery/wikibase/themes/default/jquery.wikibase.statementview.RankSelector.less',
					'../view/resources/jquery/wikibase/snakview/themes/default/snakview.SnakTypeSelector.css',
				],
			];
			$modules['wikibase.wbui2025.entityViewInit'] = $moduleTemplate + [
				'class' => CodexModule::class,
				'packageFiles' => [
					'resources/wikibase.wbui2025/wikibase.wbui2025.entityViewInit.js',
					'resources/wikibase.wbui2025/wikibase.wbui2025.qualifiers.vue',
					'resources/wikibase.wbui2025/wikibase.wbui2025.references.vue',
					'resources/wikibase.wbui2025/wikibase.wbui2025.snakValue.vue',
					'resources/wikibase.wbui2025/wikibase.wbui2025.statementView.vue',
					'resources/wikibase.wbui2025/wikibase.wbui2025.statementSections.vue',
					'resources/wikibase.wbui2025/wikibase.wbui2025.statementGroupView.vue',
					'resources/wikibase.wbui2025/wikibase.wbui2025.statusMessage.vue',
					'resources/wikibase.wbui2025/wikibase.wbui2025.propertyName.vue',
					'resources/wikibase.wbui2025/wikibase.wbui2025.addStatementButton.vue',
					'resources/wikibase.wbui2025/wikibase.wbui2025.propertySelector.vue',
					'resources/wikibase.wbui2025/wikibase.wbui2025.modalOverlay.vue',
					'resources/wikibase.wbui2025/wikibase.wbui2025.editStatementGroup.vue',
					'resources/wikibase.wbui2025/wikibase.wbui2025.editStatement.vue',
					'resources/wikibase.wbui2025/wikibase.wbui2025.mainSnak.vue',
					'resources/wikibase.wbui2025/wikibase.wbui2025.utils.js',
					'resources/wikibase.wbui2025/api/editEntity.js',
					'resources/wikibase.wbui2025/store/messageStore.js',
					'resources/wikibase.wbui2025/store/serverRenderedHtml.js',
					'resources/wikibase.wbui2025/store/statementsStore.js',
					[
						'name' => 'resources/wikibase.wbui2025/icons.json',
						'callback' => CodexModule::getIcons( ... ),
						'callbackParam' => [
							'cdxIconAdd',
							'cdxIconArrowPrevious',
							'cdxIconCheck',
							'cdxIconClose',
							'cdxIconTrash',
						],
					],
					[
						'name' => 'resources/wikibase.wbui2025/supportedDatatypes.json',
						'content' => StatementSectionsView::WBUI2025_SUPPORTED_DATATYPES,
					],
				],
				'dependencies' => [
					'pinia',
					'vue',
					'wikibase',
					'wikibase.wbui2025.entityView.styles',
				],
				'messages' => [
					'wikibase-add',
					'wikibase-addqualifier',
					'wikibase-addreference',
					'wikibase-cancel',
					'wikibase-edit',
					'wikibase-entityselector-notfound',
					'wikibase-publish',
					'wikibase-remove',
					'wikibase-save',
					'wikibase-snakview-snaktypeselector-value',
					'wikibase-statementgrouplistview-add',
					'wikibase-statementgrouplistview-edit',
					'wikibase-statementlistview-add',
					'wikibase-statementview-qualifiers-counter',
					'wikibase-statementview-rank-normal',
					'wikibase-statementview-rank-preferred',
					'wikibase-statementview-rank-deprecated',
					'wikibase-statementview-references-counter',
				],
				'codexComponents' => [
					'CdxButton',
					'CdxIcon',
					'CdxLookup',
					'CdxMessage',
					'CdxSelect',
					'CdxTextInput',
				],
			];
		}

		$rl->register( $modules );
	}

	/**
	 * Adds the Wikis using the entity in action=info
	 *
	 * @inheritDoc
	 */
	public function onInfoAction( $context, &$pageInfo ) {
		$namespaceChecker = WikibaseRepo::getEntityNamespaceLookup();
		$title = $context->getTitle();

		if ( !$title || !$namespaceChecker->isNamespaceWithEntities( $title->getNamespace() ) ) {
			// shorten out
			return;
		}

		$mediaWikiServices = MediaWikiServices::getInstance();
		$subscriptionLookup = new SqlSubscriptionLookup( WikibaseRepo::getRepoDomainDbFactory( $mediaWikiServices )->newRepoDb() );
		$entityIdLookup = WikibaseRepo::getEntityIdLookup( $mediaWikiServices );

		$siteLookup = $mediaWikiServices->getSiteLookup();

		$pageProps = $mediaWikiServices->getPageProps();

		$infoActionHookHandler = new InfoActionHookHandler(
			$namespaceChecker,
			$subscriptionLookup,
			$siteLookup,
			$entityIdLookup,
			$context,
			$pageProps
		);

		$pageInfo = $infoActionHookHandler->handle( $context, $pageInfo );
	}

	/**
	 * Handler for the ParserOptionsRegister hook to add a "wb" option for cache-splitting
	 *
	 * This registers a lazy-loaded parser option with its value being the EntityHandler
	 * parser version. Non-Wikibase parses will ignore this option, while Wikibase parses
	 * will trigger its loading via ParserOutput::recordOption() and thereby include it
	 * in the cache key to fragment the cache by EntityHandler::PARSER_VERSION.
	 *
	 * @inheritDoc
	 */
	public function onParserOptionsRegister( &$defaults, &$inCacheKey, &$lazyLoad ) {
		$defaults['wb'] = null;
		$inCacheKey['wb'] = true;
		$lazyLoad['wb'] = function () {
			return EntityHandler::PARSER_VERSION;
		};
		$defaults['termboxVersion'] = null;
		$inCacheKey['termboxVersion'] = true;
		$lazyLoad['termboxVersion'] = function () {
			return TermboxFlag::getInstance()->shouldRenderTermbox() ?
				TermboxView::TERMBOX_VERSION . TermboxView::CACHE_VERSION :
				PlaceholderEmittingEntityTermsView::TERMBOX_VERSION . PlaceholderEmittingEntityTermsView::CACHE_VERSION;
		};
		$defaults['wbMobile'] = null;
		$inCacheKey['wbMobile'] = true;
		$lazyLoad['wbMobile'] = function () {
			if ( WikibaseRepo::getMobileSite() ) {
				if ( WikibaseRepo::getSettings()->getSetting( 'tmpMobileEditingUI' ) ) {
					return 'wbui2025';
				}
				return true;
			}
			return false;
		};
	}

	/** @inheritDoc */
	public function onApiQuery__moduleManager( $moduleManager ) {
		global $wgWBRepoSettings;

		if ( isset( $wgWBRepoSettings['dataBridgeEnabled'] ) && $wgWBRepoSettings['dataBridgeEnabled'] ) {
			$moduleManager->addModule(
				'wbdatabridgeconfig',
				'meta',
				[
					'class' => MetaDataBridgeConfig::class,
					'factory' => function( ApiQuery $apiQuery, string $moduleName, SettingsArray $repoSettings ) {
						return new MetaDataBridgeConfig(
							$repoSettings,
							$apiQuery,
							$moduleName,
							function ( string $pagename ): ?string {
								$pageTitle = Title::newFromText( $pagename );
								return $pageTitle ? $pageTitle->getFullURL() : null;
							}
						);
					},
					'services' => [
						'WikibaseRepo.Settings',
					],
				]
			);
		}
	}

	/** @inheritDoc */
	public function onParserFirstCallInit( $parser ) {
		$parser->setFunctionHook(
			CommaSeparatedList::NAME,
			[ CommaSeparatedList::class, 'handle' ]
		);
	}

	public static function onRegistration() {
		global $wgResourceModules, $wgRateLimits;

		LibHooks::onRegistration();
		ViewHooks::onRegistration();

		$wgResourceModules = array_merge(
			$wgResourceModules,
			require __DIR__ . '/../resources/Resources.php'
		);
		self::inheritDefaultRateLimits( $wgRateLimits );
	}

	/**
	 * Make the 'wikibase-idgenerator' rate limit inherit the 'create' rate limit,
	 * or the 'edit' rate limit if no 'create' limit is defined,
	 * unless the 'wikibase-idgenerator' rate limit was itself customized.
	 *
	 * @param array &$rateLimits should be $wgRateLimits or a similar array
	 */
	public static function inheritDefaultRateLimits( array &$rateLimits ) {
		if ( isset( $rateLimits['wikibase-idgenerator']['&inherit-create-edit'] ) ) {
			unset( $rateLimits['wikibase-idgenerator']['&inherit-create-edit'] );
			$limits = $rateLimits['create'] ?? $rateLimits['edit'] ?? [];
			foreach ( $limits as $group => $limit ) {
				if ( !isset( $rateLimits['wikibase-idgenerator'][$group] ) ) {
					$rateLimits['wikibase-idgenerator'][$group] = $limit;
				}
			}
		}
	}

	/**
	 * Attempt to create an entity locks an entity id (for items, it would be Q####) and if saving fails
	 * due to validation issues for example, that id would be wasted.
	 * We want to penalize the user by adding a bigger number to ratelimit and slow them down
	 * to avoid bots wasting significant number of Q-ids by sending faulty data over and over again.
	 * See T284538 for more information.
	 *
	 * @inheritDoc
	 */
	public function onApiMain__onException( $apiMain, $e ) {
		$module = $apiMain->getModule();
		if ( !$module instanceof ModifyEntity ) {
			return;
		}
		$repoSettings = WikibaseRepo::getSettings();
		$idGeneratorInErrorPingLimiterValue = $repoSettings->getSetting( 'idGeneratorInErrorPingLimiter' );
		if ( !$idGeneratorInErrorPingLimiterValue || !$module->isFreshIdAssigned() ) {
			return;
		}
		$apiMain->getUser()->pingLimiter( RateLimitingIdGenerator::RATELIMIT_NAME, $idGeneratorInErrorPingLimiterValue );
	}

	/** @inheritDoc */
	public function onWikibaseContentLanguages( &$contentLanguages ): void {
		if ( !WikibaseRepo::getSettings()->getSetting( 'enableMulLanguageCode' ) ) {
			return;
		}

		if ( $contentLanguages[WikibaseContentLanguages::CONTEXT_TERM]->hasLanguage( 'mul' ) ) {
			return;
		}

		$contentLanguages[WikibaseContentLanguages::CONTEXT_TERM] = new UnionContentLanguages(
			$contentLanguages[WikibaseContentLanguages::CONTEXT_TERM],
			new StaticContentLanguages( [ 'mul' ] )
		);
	}

	/** @inheritDoc */
	public function onMaintenanceShellStart(): void {
		require_once __DIR__ . '/MaintenanceShellStart.php';
	}

	/**
	 * Handler for the VectorSearchResourceLoaderConfig hook to overwrite search pattern highlighting for wikibase
	 *
	 * @param array &$vectorSearchConfig
	 * @return void
	 */
	public function onVectorSearchResourceLoaderConfig( array &$vectorSearchConfig ): void {
		$settings = WikibaseRepo::getSettings();
		if ( $settings->getSetting( 'enableEntitySearchUI' ) === true ) {
			$vectorSearchConfig['highlightQuery'] = false;
		}
	}

	/** @inheritDoc */
	public function onSkinPageReadyConfig( Context $context, array &$config ) {
		$settings = WikibaseRepo::getSettings();

		// Don't customize search on non-primary Wikibase repos
		if ( $settings->getSetting( 'enableEntitySearchUI' ) !== true ) {
			// @phan-suppress-next-line PhanTypeMismatchReturnProbablyReal Hook interface needs update T390760
			return true;
		}

		$skin = $context->getSkin();
		if ( $settings->getSetting( 'tmpEnableScopedTypeaheadSearch' ) &&
			$skin === 'vector-2022'
		) {
			$config['search'] = true;
			$config['searchModule'] = 'wikibase.vector.scopedTypeaheadSearch';
			// Stop other hooks using this
			// @phan-suppress-next-line PhanTypeMismatchReturnProbablyReal Hook interface needs update T390760
			return false;
		} elseif ( in_array( $skin, [ 'minerva' ] ) ) {
			$mainConfig = MediaWikiServices::getInstance()->getMainConfig();
			$config['searchModule'] = 'wikibase.typeahead.search';
			// @phan-suppress-next-line PhanTypeMismatchReturnProbablyReal Hook interface needs update T390760
			return false;
		} elseif ( in_array( $skin, [ 'vector-2022' ] ) ) {
			$config['searchModule'] = 'wikibase.typeahead.search';
			// @phan-suppress-next-line PhanTypeMismatchReturnProbablyReal Hook interface needs update T390760
			return false;
		}
		// @phan-suppress-next-line PhanTypeMismatchReturnProbablyReal Hook interface needs update T390760
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function onSpecialPage_initList( &$list ) {
		if ( class_exists( GraphQL::class ) ) {
			$list[SpecialWikibaseGraphQL::SPECIAL_PAGE_NAME] = [
				'class' => SpecialWikibaseGraphQL::class,
				'services' => [
					'WikibaseRepo.EntityLookup',
					'WikibaseRepo.PrefetchingTermLookup',
					'WikibaseRepo.TermsLanguages',
				],
			];
		}
	}
}
