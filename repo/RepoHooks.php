<?php

namespace Wikibase;

use ApiBase;
use ApiEditPage;
use ApiQuerySiteinfo;
use BaseTemplate;
use CirrusSearch\Maintenance\AnalysisConfigBuilder;
use CirrusSearch\Profile\ArrayProfileRepository;
use CirrusSearch\Profile\SearchProfileRepositoryTransformer;
use CirrusSearch\Profile\SearchProfileService;
use CirrusSearch\Query\FullTextQueryBuilder;
use CirrusSearch\Search\SearchContext;
use Content;
use ContentHandler;
use ExtensionRegistry;
use HistoryPager;
use IContextSource;
use LogEntry;
use MediaWiki\MediaWikiServices;
use MediaWiki\Storage\RevisionRecord;
use MWException;
use MWExceptionHandler;
use OutputPage;
use ParserOutput;
use RecentChange;
use RequestContext;
use ResourceLoader;
use Revision;
use Skin;
use SkinTemplate;
use StubUserLang;
use Title;
use User;
use Wikibase\Lib\AutoCommentFormatter;
use Wikibase\Lib\Changes\CentralIdLookupFactory;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\Sql\EntityChangeLookup;
use Wikibase\Repo\Content\EntityHandler;
use Wikibase\Repo\Hooks\InfoActionHookHandler;
use Wikibase\Repo\Hooks\OutputPageEntityIdReader;
use Wikibase\Repo\Search\Elastic\DispatchingQueryBuilder;
use Wikibase\Repo\Search\Elastic\EntitySearchElastic;
use Wikibase\Repo\Search\Elastic\Fields\StatementsField;
use Wikibase\Repo\Search\Elastic\ConfigBuilder;
use Wikibase\Repo\Search\Elastic\Query\HasWbStatementFeature;
use Wikibase\Repo\Search\Elastic\Query\WbStatementQuantityFeature;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Store\Sql\SqlSubscriptionLookup;
use Wikibase\View\ToolbarEditSectionGenerator;
use WikiPage;

/**
 * File defining the hook handlers for the Wikibase extension.
 *
 * @license GPL-2.0-or-later
 */
final class RepoHooks {

	/**
	 * Handler for the BeforePageDisplay hook, simply injects wikibase.ui.entitysearch module
	 * replacing the native search box with the entity selector widget.
	 *
	 * @param OutputPage $out
	 * @param Skin $skin
	 */
	public static function onBeforePageDisplay( OutputPage $out, Skin $skin ) {
		$out->addModules( 'wikibase.ui.entitysearch' );
	}

	/**
	 * Handler for the BeforePageDisplayMobile hook that adds the wikibase mobile styles.
	 *
	 * @param OutputPage $out
	 * @param Skin $skin
	 */
	public static function onBeforePageDisplayMobile( OutputPage $out, Skin $skin ) {
		$title = $out->getTitle();
		$entityNamespaceLookup = WikibaseRepo::getDefaultInstance()->getEntityNamespaceLookup();
		$isEntityTitle = $entityNamespaceLookup->isEntityNamespace( $title->getNamespace() );

		if ( $isEntityTitle ) {
			$out->addModules( 'wikibase.mobile' );
		}
	}

	/**
	 * Handler for the SetupAfterCache hook, completing the content and namespace setup.
	 * This updates the $wgContentHandlers and $wgNamespaceContentModels registries
	 * according to information provided by entity type definitions and the entityNamespaces
	 * setting.
	 *
	 * @throws MWException
	 */
	public static function onSetupAfterCache() {
		global $wgContentHandlers,
			$wgNamespaceContentModels;

		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$namespaces = $wikibaseRepo->getLocalEntityNamespaces();

		// Register entity namespaces.
		// Note that $wgExtraNamespaces and $wgNamespaceAliases have already been processed at this
		// point and should no longer be touched.
		$contentModelIds = $wikibaseRepo->getContentModelMappings();

		foreach ( $namespaces as $entityType => $namespace ) {
			if ( !isset( $wgNamespaceContentModels[$namespace] ) ) {
				$wgNamespaceContentModels[$namespace] = $contentModelIds[$entityType];
			}
		}

		// Register callbacks for instantiating ContentHandlers for EntityContent.
		foreach ( $contentModelIds as $entityType => $model ) {
			$wgContentHandlers[$model] = function () use ( $wikibaseRepo, $entityType ) {
				$entityContentFactory = $wikibaseRepo->getEntityContentFactory();
				return $entityContentFactory->getContentHandlerForType( $entityType );
			};
		}
		$request = RequestContext::getMain()->getRequest();
		$settings = $wikibaseRepo->getSettings();
		$searchSettings = $settings->getSetting( 'entitySearch' );
		$useCirrus = $request->getVal( 'useCirrus' );
		if ( $useCirrus !== null ) {
			// if we have request one, use it
			$searchSettings['useCirrus'] =
			// This really should be global utility function
				( $useCirrus === 'on' || $useCirrus === 'true' || $useCirrus === 'yes' ||
				  $useCirrus === '1' );
			$settings->setSetting( 'entitySearch', $searchSettings );
		}
		if ( $searchSettings['useCirrus'] ) {
			global $wgCirrusSearchExtraIndexSettings;
			// Bump max fields so that labels/descriptions fields fit in.
			$wgCirrusSearchExtraIndexSettings['index.mapping.total_fields.limit'] = 5000;

		}

		return true;
	}

	/**
	 * Hook to add PHPUnit test cases.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/UnitTestsList
	 *
	 * @param string[] &$paths
	 */
	public static function registerUnitTests( array &$paths ) {
		$paths[] = __DIR__ . '/tests/phpunit/';
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ResourceLoaderTestModules
	 *
	 * @param array[] &$testModules
	 * @param ResourceLoader $resourceLoader
	 */
	public static function registerQUnitTests( array &$testModules, ResourceLoader $resourceLoader ) {
		$testModules['qunit'] = array_merge(
			$testModules['qunit'],
			include __DIR__ . '/tests/qunit/resources.php'
		);
	}

	/**
	 * Handler for the NamespaceIsMovable hook.
	 *
	 * Implemented to prevent moving pages that are in an entity namespace.
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/NamespaceIsMovable
	 *
	 * @param int $ns Namespace ID
	 * @param bool &$movable
	 */
	public static function onNamespaceIsMovable( $ns, &$movable ) {
		$namespaceLookup = WikibaseRepo::getDefaultInstance()->getEntityNamespaceLookup();

		if ( $namespaceLookup->isEntityNamespace( $ns ) ) {
			$movable = false;
		}
	}

	/**
	 * Called when a revision was inserted due to an edit.
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/NewRevisionFromEditComplete
	 *
	 * @param WikiPage $wikiPage
	 * @param Revision $revision
	 * @param int $baseID
	 * @param User $user
	 */
	public static function onNewRevisionFromEditComplete(
		WikiPage $wikiPage,
		Revision $revision,
		$baseID,
		User $user
	) {
		$entityContentFactory = WikibaseRepo::getDefaultInstance()->getEntityContentFactory();

		if ( $entityContentFactory->isEntityContentModel( $wikiPage->getContent()->getModel() ) ) {
			self::notifyEntityStoreWatcherOnUpdate(
				$revision->getContent(),
				$revision->getRevisionRecord()
			);

			$notifier = WikibaseRepo::getDefaultInstance()->getChangeNotifier();
			$parentId = $revision->getParentId();

			if ( !$parentId ) {
				$notifier->notifyOnPageCreated( $revision );
			} else {
				$parent = Revision::newFromId( $parentId );

				if ( !$parent ) {
					wfLogWarning(
						__METHOD__ . ': Cannot notify on page modification: '
						. 'failed to load parent revision with ID ' . $parentId
					);
				} else {
					$notifier->notifyOnPageModified( $revision, $parent );
				}
			}
		}
	}

	private static function notifyEntityStoreWatcherOnUpdate(
		EntityContent $content,
		RevisionRecord $revision
	) {
		$watcher = WikibaseRepo::getDefaultInstance()->getEntityStoreWatcher();

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
	 * Occurs after the delete article request has been processed.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ArticleDeleteComplete
	 *
	 * @param WikiPage $wikiPage
	 * @param User $user
	 * @param string $reason
	 * @param int $id id of the article that was deleted
	 * @param Content|null $content
	 * @param LogEntry $logEntry
	 *
	 * @throws MWException
	 */
	public static function onArticleDeleteComplete(
		WikiPage $wikiPage,
		User $user,
		$reason,
		$id,
		Content $content = null,
		LogEntry $logEntry
	) {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$entityContentFactory = $wikibaseRepo->getEntityContentFactory();

		// Bail out if we are not looking at an entity
		if ( !$content || !$entityContentFactory->isEntityContentModel( $content->getModel() ) ) {
			return;
		}

		/** @var EntityContent $content */

		// Notify storage/lookup services that the entity was deleted. Needed to track page-level deletion.
		// May be redundant in some cases. Take care not to cause infinite regress.
		$wikibaseRepo->getEntityStoreWatcher()->entityDeleted( $content->getEntityId() );

		$notifier = $wikibaseRepo->getChangeNotifier();
		$notifier->notifyOnPageDeleted( $content, $user, $logEntry->getTimestamp() );
	}

	/**
	 * Handle changes for undeletions
	 *
	 * @param Title $title
	 * @param bool $created
	 * @param string $comment
	 */
	public static function onArticleUndelete( Title $title, $created, $comment ) {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$entityContentFactory = $wikibaseRepo->getEntityContentFactory();

		// Bail out if we are not looking at an entity
		if ( !$entityContentFactory->isEntityContentModel( $title->getContentModel() ) ) {
			return;
		}

		$revisionId = $title->getLatestRevID();
		$revision = Revision::newFromId( $revisionId );
		$content = $revision ? $revision->getContent() : null;

		if ( !( $content instanceof EntityContent ) ) {
			return;
		}

		$notifier = $wikibaseRepo->getChangeNotifier();
		$notifier->notifyOnPageUndeleted( $revision );
	}

	/**
	 * Nasty hack to inject information from RC into the change notification saved earlier
	 * by the onNewRevisionFromEditComplete hook handler.
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/RecentChange_save
	 *
	 * @todo: find a better way to do this!
	 *
	 * @param RecentChange $recentChange
	 */
	public static function onRecentChangeSave( RecentChange $recentChange ) {
		$logType = $recentChange->getAttribute( 'rc_log_type' );
		$logAction = $recentChange->getAttribute( 'rc_log_action' );
		$revId = $recentChange->getAttribute( 'rc_this_oldid' );

		if ( $revId <= 0 ) {
			// If we don't have a revision ID, we have no chance to find the right change to update.
			// NOTE: As of February 2015, RC entries for undeletion have rc_this_oldid = 0.
			return;
		}

		if ( $logType === null || ( $logType === 'delete' && $logAction === 'restore' ) ) {
			$changeLookup = WikibaseRepo::getDefaultInstance()->getStore()->getEntityChangeLookup();

			$change = $changeLookup->loadByRevisionId( $revId, EntityChangeLookup::FROM_MASTER );

			if ( $change ) {
				$changeStore = WikibaseRepo::getDefaultInstance()->getStore()->getChangeStore();

				$centralIdLookup = ( new CentralIdLookupFactory() )->getCentralIdLookup();
				if ( $centralIdLookup === null ) {
					$centralUserId = 0;
				} else {
					$repoUser = $recentChange->getPerformer();
					$centralUserId = $centralIdLookup->centralIdFromLocalUser(
						$repoUser
					);
				}

				$change->setMetadataFromRC( $recentChange, $centralUserId );
				$changeStore->saveChange( $change );
			}
		}
	}

	/**
	 * Allows to add user preferences.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/GetPreferences
	 *
	 * NOTE: Might make sense to put the inner functionality into a well structured Preferences file once this
	 *       becomes more.
	 *
	 * @param User $user
	 * @param array[] &$preferences
	 */
	public static function onGetPreferences( User $user, array &$preferences ) {
		$preferences['wb-acknowledgedcopyrightversion'] = [
			'type' => 'api'
		];

		$preferences['wikibase-entitytermsview-showEntitytermslistview'] = [
			'type' => 'toggle',
			'label-message' => 'wikibase-setting-entitytermsview-showEntitytermslistview',
			'help-message' => 'wikibase-setting-entitytermsview-showEntitytermslistview-help',
			'section' => 'rendering/advancedrendering',
			'default' => '1',
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

	/**
	 * Modify line endings on history page.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/PageHistoryLineEnding
	 *
	 * @param HistoryPager $history
	 * @param object &$row
	 * @param string &$html
	 * @param array &$classes
	 */
	public static function onPageHistoryLineEnding( HistoryPager $history, &$row, &$html, array &$classes ) {
		// Note: This assumes that HistoryPager::getTitle returns a Title.
		$entityContentFactory = WikibaseRepo::getDefaultInstance()->getEntityContentFactory();

		$wikiPage = $history->getWikiPage();
		$rev = new Revision( $row );

		if ( $entityContentFactory->isEntityContentModel( $history->getTitle()->getContentModel() )
			&& $wikiPage->getLatest() !== $rev->getId()
			&& $rev->getTitle()->quickUserCan( 'edit', $history->getUser() )
			&& !$rev->isDeleted( RevisionRecord::DELETED_TEXT )
		) {
			$linkRenderer = MediaWikiServices::getInstance()->getLinkRenderer();
			$link = $linkRenderer->makeKnownLink(
				$rev->getTitle(),
				$history->msg( 'wikibase-restoreold' )->text(),
				[],
				[
					'action' => 'edit',
					'restore' => $rev->getId()
				]
			);

			$html .= ' ' . $history->msg( 'parentheses' )->rawParams( $link )->escaped();
		}
	}

	/**
	 * Alter the structured navigation links in SkinTemplates.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/SkinTemplateNavigation
	 *
	 * @param SkinTemplate $skinTemplate
	 * @param array[] &$links
	 */
	public static function onPageTabs( SkinTemplate $skinTemplate, array &$links ) {
		$entityContentFactory = WikibaseRepo::getDefaultInstance()->getEntityContentFactory();

		$title = $skinTemplate->getRelevantTitle();

		if ( $entityContentFactory->isEntityContentModel( $title->getContentModel() ) ) {
			unset( $links['views']['edit'] );
			unset( $links['views']['viewsource'] );

			if ( $title->quickUserCan( 'edit', $skinTemplate->getUser() ) ) {
				$request = $skinTemplate->getRequest();
				$old = !$skinTemplate->isRevisionCurrent()
					&& !$request->getCheck( 'diff' );

				$restore = $request->getCheck( 'restore' );

				if ( $old || $restore ) {
					// insert restore tab into views array, at the second position

					$revid = $restore
						? $request->getText( 'restore' )
						: $skinTemplate->getRevisionId();

					$rev = Revision::newFromId( $revid );
					if ( !$rev || $rev->isDeleted( RevisionRecord::DELETED_TEXT ) ) {
						return;
					}

					$head = array_slice( $links['views'], 0, 1 );
					$tail = array_slice( $links['views'], 1 );
					$neck = [
						'restore' => [
							'class' => $restore ? 'selected' : false,
							'text' => $skinTemplate->getLanguage()->ucfirst(
								wfMessage( 'wikibase-restoreold' )->text()
							),
							'href' => $title->getLocalURL( [
								'action' => 'edit',
								'restore' => $revid
							] ),
						]
					];

					$links['views'] = array_merge( $head, $neck, $tail );
				}
			}
		}
	}

	/**
	 * Reorder the groups for the special pages
	 *
	 * @param array &$groups
	 * @param bool &$moveOther
	 */
	public static function onSpecialPageReorderPages( &$groups, &$moveOther ) {
		$groups = array_merge( [ 'wikibaserepo' => null ], $groups );
	}

	/**
	 * Used to append a css class to the body, so the page can be identified as Wikibase item page.
	 * @see http://www.mediawiki.org/wiki/Manual:Hooks/OutputPageBodyAttributes
	 *
	 * @param OutputPage $out
	 * @param Skin $skin
	 * @param array &$bodyAttrs
	 */
	public static function onOutputPageBodyAttributes( OutputPage $out, Skin $skin, array &$bodyAttrs ) {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$outputPageEntityIdReader = new OutputPageEntityIdReader(
			$wikibaseRepo->getEntityContentFactory(),
			$wikibaseRepo->getEntityIdParser()
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

		if ( $skin->getRequest()->getCheck( 'diff' ) ) {
			$bodyAttrs['class'] .= ' wb-diffpage';
		}

		if ( $out->getTitle() && $out->getRevisionId() !== $out->getTitle()->getLatestRevID() ) {
			$bodyAttrs['class'] .= ' wb-oldrevpage';
		}
	}

	/**
	 * Handler for the ApiCheckCanExecute hook in ApiMain.
	 *
	 * This implementation causes the execution of ApiEditPage (action=edit) to fail
	 * for all namespaces reserved for Wikibase entities. This prevents direct text-level editing
	 * of structured data, and it also prevents other types of content being created in these
	 * namespaces.
	 *
	 * @param ApiBase $module The API module being called
	 * @param User    $user   The user calling the API
	 * @param array|string|null &$message Output-parameter holding for the message the call should fail with.
	 *                            This can be a message key or an array as expected by ApiBase::dieUsageMsg().
	 *
	 * @return bool true to continue execution, false to abort and with $message as an error message.
	 */
	public static function onApiCheckCanExecute( ApiBase $module, User $user, &$message ) {
		$entityContentFactory = WikibaseRepo::getDefaultInstance()->getEntityContentFactory();

		if ( $module instanceof ApiEditPage ) {
			$params = $module->extractRequestParams();
			$pageObj = $module->getTitleOrPageId( $params );
			$namespace = $pageObj->getTitle()->getNamespace();

			foreach ( $entityContentFactory->getEntityContentModels() as $contentModel ) {
				/** @var EntityHandler $handler */
				$handler = ContentHandler::getForModelID( $contentModel );

				if ( $handler->getEntityNamespace() === $namespace ) {
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
						$pageObj->getTitle()->getNsText()
					];

					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Handler for the TitleGetRestrictionTypes hook.
	 *
	 * Implemented to prevent people from protecting pages from being
	 * created or moved in an entity namespace (which is pointless).
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/TitleGetRestrictionTypes
	 *
	 * @param Title $title
	 * @param string[] &$types The types of protection available
	 */
	public static function onTitleGetRestrictionTypes( Title $title, array &$types ) {
		$namespaceLookup = WikibaseRepo::getDefaultInstance()->getEntityNamespaceLookup();

		if ( $namespaceLookup->isEntityNamespace( $title->getNamespace() ) ) {
			// Remove create and move protection for Wikibase namespaces
			$types = array_diff( $types, [ 'create', 'move' ] );
		}
	}

	/**
	 * Hook handler for AbuseFilter's AbuseFilter-contentToString hook, implemented
	 * to provide a custom text representation of Entities for filtering.
	 *
	 * @param Content $content
	 * @param string  &$text The resulting text
	 *
	 * @return bool
	 */
	public static function onAbuseFilterContentToString( Content $content, &$text ) {
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
	 * @param string &$comment reference to the autocomment text
	 * @param bool $pre true if there is content before the autocomment
	 * @param string $auto the autocomment unformatted
	 * @param bool $post true if there is content after the autocomment
	 * @param Title|null $title use for further information
	 * @param bool $local shall links be generated locally or globally
	 */
	public static function onFormat( &$comment, $pre, $auto, $post, $title, $local ) {
		global $wgLang, $wgTitle;

		// If it is possible to avoid loading the whole page then the code will be lighter on the server.
		if ( !( $title instanceof Title ) ) {
			$title = $wgTitle;
		}

		if ( !( $title instanceof Title ) ) {
			return;
		}

		$namespaceLookup = WikibaseRepo::getDefaultInstance()->getEntityNamespaceLookup();
		$entityType = $namespaceLookup->getEntityType( $title->getNamespace() );
		if ( $entityType === null ) {
			return;
		}

		if ( $wgLang instanceof StubUserLang ) {
			wfDebugLog(
				'wikibase-debug',
				'Bug: T112070: ' . MWExceptionHandler::prettyPrintTrace(
					MWExceptionHandler::redactTrace( debug_backtrace() )
				)
			);

			StubUserLang::unstub( $wgLang );
		}

		$formatter = new AutoCommentFormatter( $wgLang, [ 'wikibase-' . $entityType, 'wikibase-entity' ] );
		$formattedComment = $formatter->formatAutoComment( $auto );

		if ( is_string( $formattedComment ) ) {
			$comment = $formatter->wrapAutoComment( $pre, $formattedComment, $post );
		}
	}

	/**
	 * Called when pushing meta-info from the ParserOutput into OutputPage.
	 * Used to transfer 'wikibase-view-chunks' and entity data from ParserOutput to OutputPage.
	 *
	 * @param OutputPage $out
	 * @param ParserOutput $parserOutput
	 */
	public static function onOutputPageParserOutput( OutputPage $out, ParserOutput $parserOutput ) {
		// Set in EntityParserOutputGenerator.
		$placeholders = $parserOutput->getExtensionData( 'wikibase-view-chunks' );
		if ( $placeholders !== null ) {
			$out->setProperty( 'wikibase-view-chunks', $placeholders );
		}

		// Set in EntityParserOutputGenerator.
		$termsListItems = $parserOutput->getExtensionData( 'wikibase-terms-list-items' );
		if ( $termsListItems !== null ) {
			$out->setProperty( 'wikibase-terms-list-items', $termsListItems );
		}

		// Used in ViewEntityAction and EditEntityAction to override the page HTML title
		// with the label, if available, or else the id. Passed via parser output
		// and output page to save overhead of fetching content and accessing an entity
		// on page view.
		$meta = $parserOutput->getExtensionData( 'wikibase-meta-tags' );
		$out->setProperty( 'wikibase-meta-tags', $meta );

		// Array with <link rel="alternate"> tags for the page HEAD.
		$alternateLinks = $parserOutput->getExtensionData( 'wikibase-alternate-links' );
		if ( $alternateLinks !== null ) {
			foreach ( $alternateLinks as $link ) {
				$out->addLink( $link );
			}
		}
	}

	/**
	 * Handler for the ContentModelCanBeUsedOn hook, used to prevent pages of inappropriate type
	 * to be placed in an entity namespace.
	 *
	 * @param string $contentModel
	 * @param Title $title
	 * @param bool &$ok
	 *
	 * @return bool
	 */
	public static function onContentModelCanBeUsedOn( $contentModel, Title $title, &$ok ) {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();

		$namespaceLookup = $wikibaseRepo->getEntityNamespaceLookup();
		$contentModelIds = $wikibaseRepo->getContentModelMappings();

		$expectedModel = false;
		$expectedEntityType = $namespaceLookup->getEntityType( $title->getNamespace() );
		if ( $expectedEntityType !== null ) {
			$expectedModel = $contentModelIds[$expectedEntityType];
		}

		// If the namespace is an entity namespace, the content model
		// must be the model assigned to that namespace.
		if ( $expectedModel !== false && $expectedModel !== $contentModel ) {
			$ok = false;
			return false;
		}

		return true;
	}

	/**
	 * Exposes configuration values to the action=query&meta=siteinfo API, including lists of
	 * property and data value types, sparql endpoint, and several base URLs and URIs.
	 *
	 * @param ApiQuerySiteinfo $api
	 * @param array &$data
	 */
	public static function onAPIQuerySiteInfoGeneralInfo( ApiQuerySiteinfo $api, array &$data ) {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$dataTypes = $wikibaseRepo->getDataTypeFactory()->getTypes();
		$propertyTypes = [];

		foreach ( $dataTypes as $id => $type ) {
			$propertyTypes[$id] = [ 'valuetype' => $type->getDataValueType() ];
		}

		$data['wikibase-propertytypes'] = $propertyTypes;

		$conceptBaseUri = $wikibaseRepo->getSettings()->getSetting( 'conceptBaseUri' );
		$data['wikibase-conceptbaseuri'] = $conceptBaseUri;

		$geoShapeStorageBaseUrl = $wikibaseRepo->getSettings()->getSetting( 'geoShapeStorageBaseUrl' );
		$data['wikibase-geoshapestoragebaseurl'] = $geoShapeStorageBaseUrl;

		$tabularDataStorageBaseUrl = $wikibaseRepo->getSettings()->getSetting( 'tabularDataStorageBaseUrl' );
		$data['wikibase-tabulardatastoragebaseurl'] = $tabularDataStorageBaseUrl;

		$sparqlEndpoint = $wikibaseRepo->getSettings()->getSetting( 'sparqlEndpoint' );
		if ( is_string( $sparqlEndpoint ) ) {
			$data['wikibase-sparql'] = $sparqlEndpoint;
		}
	}

	/**
	 * Helper for onAPIQuerySiteInfoStatisticsInfo
	 *
	 * @param object $row
	 * @return array
	 */
	private static function formatDispatchRow( $row ) {
		$data = [
			'pending' => $row->chd_pending,
			'lag' => $row->chd_lag,
		];
		if ( isset( $row->chd_site ) ) {
			$data['site'] = $row->chd_site;
		}
		if ( isset( $row->chd_seen ) ) {
			$data['position'] = $row->chd_seen;
		}
		if ( isset( $row->chd_touched ) ) {
			$data['touched'] = wfTimestamp( TS_ISO_8601, $row->chd_touched );
		}

		return $data;
	}

	/**
	 * Adds DispatchStats info to the API
	 *
	 * @param array[] &$data
	 */
	public static function onAPIQuerySiteInfoStatisticsInfo( array &$data ) {
		$stats = new DispatchStats();
		$stats->load();
		if ( $stats->hasStats() ) {
			$data['dispatch'] = [
				'oldest' => [
					'id' => $stats->getMinChangeId(),
					'timestamp' => $stats->getMinChangeTimestamp(),
				],
				'newest' => [
					'id' => $stats->getMaxChangeId(),
					'timestamp' => $stats->getMaxChangeTimestamp(),
				],
				'freshest' => self::formatDispatchRow( $stats->getFreshest() ),
				'median' => self::formatDispatchRow( $stats->getMedian() ),
				'stalest' => self::formatDispatchRow( $stats->getStalest() ),
				'average' => self::formatDispatchRow( $stats->getAverage() ),
			];
		}
	}

	/**
	 * Called by Import.php. Implemented to prevent the import of entities.
	 *
	 * @param object $importer unclear, see Bug T66657
	 * @param array $pageInfo
	 * @param array $revisionInfo
	 *
	 * @throws MWException
	 */
	public static function onImportHandleRevisionXMLTag( $importer, $pageInfo, $revisionInfo ) {
		if ( isset( $revisionInfo['model'] ) ) {
			$wikibaseRepo = WikibaseRepo::getDefaultInstance();
			$contentModels = $wikibaseRepo->getContentModelMappings();
			$allowImport = $wikibaseRepo->getSettings()->getSetting( 'allowEntityImport' );

			if ( !$allowImport && in_array( $revisionInfo['model'], $contentModels ) ) {
				// Skip entities.
				// XXX: This is rather rough.
				throw new MWException(
					'To avoid ID conflicts, the import of Wikibase entities is not supported.'
						. ' You can enable imports using the "allowEntityImport" setting.'
				);
			}
		}
	}

	/**
	 * Called in SkinTemplate::buildNavUrls(), allows us to set up navigation URLs to later be used
	 * in the toolbox.
	 *
	 * @param SkinTemplate $skinTemplate
	 * @param array[] &$navigationUrls
	 */
	public static function onSkinTemplateBuildNavUrlsNavUrlsAfterPermalink(
		SkinTemplate $skinTemplate,
		array &$navigationUrls
	) {
		$title = $skinTemplate->getTitle();
		$namespaceLookup = WikibaseRepo::getDefaultInstance()->getEntityNamespaceLookup();

		if ( !$title || !$namespaceLookup->isEntityNamespace( $title->getNamespace() ) ) {
			return;
		}

		$baseUri = WikibaseRepo::getDefaultInstance()->getSettings()->getSetting( 'conceptBaseUri' );
		$navigationUrls['wb-concept-uri'] = [
			'text' => $skinTemplate->msg( 'wikibase-concept-uri' ),
			'href' => $baseUri . $title->getDBkey(),
			'title' => $skinTemplate->msg( 'wikibase-concept-uri-tooltip' )
		];
	}

	/**
	 * Called in BaseTemplate::getToolbox(), allows us to add navigation URLs to the toolbox.
	 *
	 * @param BaseTemplate $baseTemplate
	 * @param array[] &$toolbox
	 */
	public static function onBaseTemplateToolbox( BaseTemplate $baseTemplate, array &$toolbox ) {
		if ( !isset( $baseTemplate->data['nav_urls']['wb-concept-uri'] ) ) {
			return;
		}

		$toolbox['wb-concept-uri'] = $baseTemplate->data['nav_urls']['wb-concept-uri'];
		$toolbox['wb-concept-uri']['id'] = 't-wb-concept-uri';
	}

	/**
	 * Register ResourceLoader modules with dynamic dependencies.
	 *
	 * @param ResourceLoader $resourceLoader
	 */
	public static function onResourceLoaderRegisterModules( ResourceLoader $resourceLoader ) {
		$moduleTemplate = [
			'localBasePath' => __DIR__,
			'remoteExtPath' => 'Wikibase/repo',
		];

		$modules = [
			'wikibase.WikibaseContentLanguages' => $moduleTemplate + [
				'scripts' => [
					'resources/wikibase.WikibaseContentLanguages.js',
				],
				'dependencies' => [
					'util.ContentLanguages',
					'util.inherit',
					'wikibase',
				],
			],
			'wikibase.special.languageLabelDescriptionAliases' => $moduleTemplate + [
				'scripts' => [
					'resources/wikibase.special/wikibase.special.languageLabelDescriptionAliases.js',
				],
				'dependencies' => [
					'oojs-ui',
				],
				'messages' => [
					'wikibase-label-edit-placeholder',
					'wikibase-label-edit-placeholder-language-aware',
					'wikibase-description-edit-placeholder',
					'wikibase-description-edit-placeholder-language-aware',
					'wikibase-aliases-edit-placeholder',
					'wikibase-aliases-edit-placeholder-language-aware',
				],
			],
		];

		$isUlsLoaded = ExtensionRegistry::getInstance()->isLoaded( 'UniversalLanguageSelector' );
		if ( $isUlsLoaded ) {
			$modules['wikibase.WikibaseContentLanguages']['dependencies'][] = 'ext.uls.languagenames';
			$modules['wikibase.special.languageLabelDescriptionAliases']['dependencies'][] = 'ext.uls.mediawiki';
		}

		$resourceLoader->register( $modules );
	}

	/**
	 * Adds the Wikis using the entity in action=info
	 *
	 * @param IContextSource $context
	 * @param array[] &$pageInfo
	 */
	public static function onInfoAction( IContextSource $context, array &$pageInfo ) {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();

		$namespaceChecker = $wikibaseRepo->getEntityNamespaceLookup();
		$title = $context->getTitle();

		if ( !$title || !$namespaceChecker->isEntityNamespace( $title->getNamespace() ) ) {
			// shorten out
			return;
		}

		$mediaWikiServices = MediaWikiServices::getInstance();
		$loadBalancer = $mediaWikiServices->getDBLoadBalancer();
		$subscriptionLookup = new SqlSubscriptionLookup( $loadBalancer );
		$entityIdLookup = $wikibaseRepo->getEntityIdLookup();

		$siteLookup = $mediaWikiServices->getSiteLookup();

		$infoActionHookHandler = new InfoActionHookHandler(
			$namespaceChecker,
			$subscriptionLookup,
			$siteLookup,
			$entityIdLookup,
			$context
		);

		$pageInfo = $infoActionHookHandler->handle( $context, $pageInfo );
	}

	/**
	 * Post-cache transformation for Wikibase section-edit markers
	 *
	 * @param ParserOutput $out
	 * @param string &$text Text being transformed
	 * @param array &$options Transformation options
	 */
	public static function onParserOutputPostCacheTransform(
		ParserOutput $out,
		&$text,
		array &$options
	) {
		$text = ToolbarEditSectionGenerator::enableSectionEditLinks(
			$text,
			$options['enableSectionEditLinks']
		);
	}

	/**
	 * Add Wikibase-specific ElasticSearch analyzer configurations.
	 * @param array &$config
	 * @param AnalysisConfigBuilder $builder
	 */
	public static function onCirrusSearchAnalysisConfig( &$config, AnalysisConfigBuilder $builder ) {
		static $inHook;
		if ( $inHook ) {
			// Do not call this hook repeatedly, since ConfigBuilder calls AnalysisConfigBuilder
			// FIXME: this is not a very nice hack, but we need it because we want AnalysisConfigBuilder
			// to call the hook, since other extensions may make relevant changes to config.
			// We just don't want to run this specific hook again, but Mediawiki API does not have
			// the means to exclude one hook temporarily.
			return;
		}

		// Analyzer for splitting statements and extracting properties:
		// P31:Q1234 => P31
		$config['analyzer']['extract_wb_property'] = [
			'type' => 'custom',
			'tokenizer' => 'split_wb_statements',
			'filter' => [ 'first_token' ],
		];
		$config['tokenizer']['split_wb_statements'] = [
			'type' => 'pattern',
			'pattern' => StatementsField::STATEMENT_SEPARATOR,
		];
		$config['filter']['first_token'] = [
			'type' => 'limit',
			'max_token_count' => 1
		];

		// Analyzer for extracting quantity data and storing it in a term frequency field
		$config['analyzer']['extract_wb_quantity'] = [
			'type' => 'custom',
			'tokenizer' => 'keyword',
			'filter' => [ 'term_freq' ],
		];

		// Language analyzers for descriptions
		$repo = WikibaseRepo::getDefaultInstance();
		$wbBuilder = new ConfigBuilder( $repo->getTermsLanguages()->getLanguages(),
			$repo->getSettings()->getSetting( 'entitySearch' ),
			$builder
		);
		$inHook = true;
		try {
			$wbBuilder->buildConfig( $config );
		} finally {
			$inHook = false;
		}
	}

	/**
	 * Register our cirrus profiles using WikibaseRepo::getDefaultInstance().
	 *
	 * @param SearchProfileService $service
	 */
	public static function onCirrusSearchProfileService( SearchProfileService $service ) {
		$settings = WikibaseRepo::getDefaultInstance()->getSettings()->getSetting( 'entitySearch' );
		self::registerSearchProfiles( $service, $settings );
	}

	/**
	 * Register cirrus profiles .
	 * @param SearchProfileService $service
	 * @param array $entitySearchConfig
	 */
	public static function registerSearchProfiles( SearchProfileService $service, array $entitySearchConfig ) {
		$stmtBoost = isset( $entitySearchConfig['statementBoost'] ) ? $entitySearchConfig['statementBoost'] : [];
		// register base profiles available on all wikibase installs
		$service->registerFileRepository( SearchProfileService::RESCORE,
			'wikibase_base', __DIR__ . '/config/ElasticSearchRescoreProfiles.php' );
		$service->registerRepository( new SearchProfileRepositoryTransformer(
				ArrayProfileRepository::fromFile(
					SearchProfileService::RESCORE_FUNCTION_CHAINS,
					'wikibase_base',
					__DIR__ . '/config/ElasticSearchRescoreFunctions.php' ),
				[ EntitySearchElastic::STMT_BOOST_PROFILE_REPL => $stmtBoost ]
		) );
		$service->registerFileRepository( EntitySearchElastic::WIKIBASE_PREFIX_QUERY_BUILDER,
			'wikibase_base', __DIR__ . '/config/EntityPrefixSearchProfiles.php' );
		$service->registerFileRepository( SearchProfileService::FT_QUERY_BUILDER,
			'wikibase_base', __DIR__ . '/config/EntitySearchProfiles.php' );

		// register custom profiles provided in the wikibase config
		if ( isset( $entitySearchConfig['rescoreProfiles'] ) ) {
			$service->registerArrayRepository( SearchProfileService::RESCORE,
				'wikibase_config', $entitySearchConfig['rescoreProfiles'] );
		}
		if ( isset( $entitySearchConfig['rescoreFunctionChains'] ) ) {
			$service->registerRepository( new SearchProfileRepositoryTransformer(
				ArrayProfileRepository::fromArray(
					SearchProfileService::RESCORE_FUNCTION_CHAINS,
					'wikibase_config',
					$entitySearchConfig['rescoreFunctionChains'] ),
				[ EntitySearchElastic::STMT_BOOST_PROFILE_REPL => $stmtBoost ]
			) );
		}
		if ( isset( $entitySearchConfig['prefixSearchProfiles'] ) ) {
			$service->registerArrayRepository( EntitySearchElastic::WIKIBASE_PREFIX_QUERY_BUILDER,
				'wikibase_config', $entitySearchConfig['prefixSearchProfiles'] );
		}
		if ( isset( $entitySearchConfig['fulltextSearchProfiles'] ) ) {
			$service->registerArrayRepository( SearchProfileService::FT_QUERY_BUILDER,
				'wikibase_config', $entitySearchConfig['fulltextSearchProfiles'] );
		}

		// Determine the default rescore profile to use for entity autocomplete search
		$defaultRescore = EntitySearchElastic::DEFAULT_RESCORE_PROFILE;
		if ( isset( $entitySearchConfig['defaultPrefixRescoreProfile'] ) ) {
			// If set in config use it
			$defaultRescore = $entitySearchConfig['defaultPrefixRescoreProfile'];
		}
		$service->registerDefaultProfile( SearchProfileService::RESCORE,
			EntitySearchElastic::CONTEXT_WIKIBASE_PREFIX, $defaultRescore );
		// add the possibility to override the profile by setting the URI param cirrusRescoreProfile
		$service->registerUriParamOverride( SearchProfileService::RESCORE,
			EntitySearchElastic::CONTEXT_WIKIBASE_PREFIX, 'cirrusRescoreProfile' );

		// Determine the default query builder profile to use for entity autocomplete search
		$defaultQB = EntitySearchElastic::DEFAULT_QUERY_BUILDER_PROFILE;
		if ( isset( $entitySearchConfig['prefixSearchProfile'] ) ) {
			$defaultQB = $entitySearchConfig['prefixSearchProfile'];
		}
		$service->registerDefaultProfile( EntitySearchElastic::WIKIBASE_PREFIX_QUERY_BUILDER,
			EntitySearchElastic::CONTEXT_WIKIBASE_PREFIX, $defaultQB );
		$service->registerUriParamOverride( EntitySearchElastic::WIKIBASE_PREFIX_QUERY_BUILDER,
			EntitySearchElastic::CONTEXT_WIKIBASE_PREFIX, 'cirrusWBProfile' );

		// Determine query builder profile for fulltext search
		$defaultFQB = EntitySearchElastic::DEFAULT_QUERY_BUILDER_PROFILE;
		if ( isset( $entitySearchConfig['fulltextSearchProfile'] ) ) {
			$defaultFQB = $entitySearchConfig['fulltextSearchProfile'];
		}
		$service->registerDefaultProfile( SearchProfileService::FT_QUERY_BUILDER,
			EntitySearchElastic::CONTEXT_WIKIBASE_FULLTEXT, $defaultFQB );
		$service->registerUriParamOverride( SearchProfileService::FT_QUERY_BUILDER,
			EntitySearchElastic::CONTEXT_WIKIBASE_FULLTEXT, 'cirrusWBProfile' );

		// Determine the default rescore profile to use for fulltext search
		$defaultFTRescore = EntitySearchElastic::DEFAULT_RESCORE_PROFILE;
		if ( isset( $entitySearchConfig['defaultFulltextRescoreProfile'] ) ) {
			// If set in config use it
			$defaultFTRescore = $entitySearchConfig['defaultFulltextRescoreProfile'];
		}
		$service->registerDefaultProfile( SearchProfileService::RESCORE,
			EntitySearchElastic::CONTEXT_WIKIBASE_FULLTEXT, $defaultFTRescore );
		// add the possibility to override the profile by setting the URI param cirrusRescoreProfile
		$service->registerUriParamOverride( SearchProfileService::RESCORE,
			EntitySearchElastic::CONTEXT_WIKIBASE_FULLTEXT, 'cirrusRescoreProfile' );
	}

	/**
	 * @param FullTextQueryBuilder $builder
	 * @param SearchContext $context
	 */
	public static function onCirrusSearchFulltextQueryBuilder(
		FullTextQueryBuilder &$builder,
		SearchContext $context
	) {
		if ( !$context->getConfig()->isLocalWiki() ) {
			// don't mess with interwiki searches
			return;
		}
		$repo = WikibaseRepo::getDefaultInstance();
		$settings = $repo->getSettings()->getSetting( 'entitySearch' );
		if ( !$settings || empty( $settings['useCirrus'] ) ) {
			return;
		}

		$qbSettings = $context->getConfig()->getProfileService()
			->loadProfile( SearchProfileService::FT_QUERY_BUILDER,
				EntitySearchElastic::CONTEXT_WIKIBASE_FULLTEXT );
		$builder = new $qbSettings['builder_class'](
				$builder,
				$repo->getSettings()->getSetting( 'entitySearch' ),
				$qbSettings['settings'],
				$repo->getEntityNamespaceLookup(),
				$repo->getLanguageFallbackChainFactory(),
				$repo->getEntityIdParser(),
				$repo->getUserLanguage()->getCode()
			);
	}

	/**
	 * @param FullTextQueryBuilder $builder
	 * @param string $term
	 * @param SearchContext $context
	 */
	public static function onCirrusSearchFulltextQueryBuilderComplete(
		FullTextQueryBuilder $builder,
		$term,
		SearchContext $context
	) {
		if ( !$context->getConfig()->isLocalWiki() ) {
			// don't mess with interwiki searches
			return;
		}

		$repo = WikibaseRepo::getDefaultInstance();
		$settings = $repo->getSettings()->getSetting( 'entitySearch' );
		if ( !$settings || empty( $settings['useCirrus'] ) ) {
			// Right now our specialized search is Cirrus, so no point in
			// calling dispatcher if Cirrus is disabled.
			return;
		}

		$wbBuilder = new DispatchingQueryBuilder( $repo->getFulltextSearchTypes(),
				$repo->getEntityNamespaceLookup() );
		$wbBuilder->build( $context, $term );
	}

	/**
	 * Add extra cirrus search query features for wikibase
	 *
	 * @param $config (not used, required by hook)
	 * @param array $extraFeatures
	 */
	public static function onCirrusSearchAddQueryFeatures( $config, array &$extraFeatures ) {
		$foreignRepoNames = [];
		$foreignRepos = WikibaseRepo::getDefaultInstance()->getSettings()->getSetting(
			'foreignRepositories'
		);
		if ( !empty( $foreignRepos ) ) {
			$foreignRepoNames = array_keys( $foreignRepos );
		}
		$extraFeatures[] = new HasWbStatementFeature( $foreignRepoNames );
		$extraFeatures[] = new WbStatementQuantityFeature( $foreignRepoNames );
	}

	/**
	 * Handler for the ApiMaxLagInfo to add dispatching lag stats
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ApiMaxLagInfo
	 *
	 * @param array &$lagInfo
	 */
	public static function onApiMaxLagInfo( array &$lagInfo ) {

		$dispatchLagToMaxLagFactor = WikibaseRepo::getDefaultInstance()->getSettings()->getSetting(
			'dispatchLagToMaxLagFactor'
		);

		if ( $dispatchLagToMaxLagFactor <= 0 ) {
			return;
		}

		$stats = new DispatchStats();
		$stats->load();
		$median = $stats->getMedian();

		if ( $median ) {
			$maxDispatchLag = $median->chd_lag / (float)$dispatchLagToMaxLagFactor;
			if ( $maxDispatchLag > $lagInfo['lag'] ) {
				$lagInfo = [
					'host' => $median->chd_site,
					'lag' => $maxDispatchLag,
					'type' => 'wikibase-dispatching',
					'dispatchLag' => $median->chd_lag,
				];
			}
		}
	}

	/**
	 * Handler for the ParserOptionsRegister hook to add a "wb" option for cache-splitting
	 *
	 * This registers a lazy-loaded parser option with its value being the EntityHandler
	 * parser version. Non-Wikibase parses will ignore this option, while Wikibase parses
	 * will trigger its loading via ParserOutput::recordOption() and thereby include it
	 * in the cache key to fragment the cache by EntityHandler::PARSER_VERSION.
	 *
	 * @param array &$defaults Options and their defaults
	 * @param array &$inCacheKey Whether each option splits the parser cache
	 * @param array &$lazyOptions Initializers for lazy-loaded options
	 */
	public static function onParserOptionsRegister( &$defaults, &$inCacheKey, &$lazyOptions ) {
		$defaults['wb'] = null;
		$inCacheKey['wb'] = true;
		$lazyOptions['wb'] = function () {
			return EntityHandler::PARSER_VERSION;
		};
	}

}
