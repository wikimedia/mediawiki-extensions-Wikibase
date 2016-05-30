<?php

namespace Wikibase;

use ApiBase;
use ApiEditPage;
use ApiQuerySiteinfo;
use BaseTemplate;
use Content;
use ContentHandler;
use ExtensionRegistry;
use HistoryPager;
use Html;
use Linker;
use LogEntry;
use MWException;
use MWExceptionHandler;
use OutOfBoundsException;
use OutputPage;
use ParserOutput;
use RecentChange;
use ResourceLoader;
use Revision;
use SearchResult;
use Skin;
use SkinTemplate;
use SpecialSearch;
use StubUserLang;
use Title;
use User;
use Wikibase\DataModel\Term\DescriptionsProvider;
use Wikibase\Lib\AutoCommentFormatter;
use Wikibase\Lib\Store\EntityChangeLookup;
use Wikibase\Repo\Content\EntityHandler;
use Wikibase\Repo\Hooks\OutputPageEntityIdReader;
use Wikibase\Repo\WikibaseRepo;
use WikiPage;

/**
 * File defining the hook handlers for the Wikibase extension.
 *
 * @since 0.1
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Nikola Smolenski
 * @author Daniel Werner
 * @author Michał Łazowik
 * @author Jens Ohlig
 */
final class RepoHooks {

	/**
	 * Handler for the BeforePageDisplay hook, simply injects wikibase.ui.entitysearch module
	 * replacing the native search box with the entity selector widget.
	 *
	 * @since 0.4
	 *
	 * @param OutputPage $out
	 * @param Skin $skin
	 *
	 * @return bool
	 */
	public static function onBeforePageDisplay( OutputPage &$out, Skin &$skin ) {
		$out->addModules( 'wikibase.ui.entitysearch' );
		return true;
	}

	/**
	 * Handler for the SetupAfterCache hook, completing setup of
	 * content and namespace setup.
	 *
	 * @since 0.1
	 *
	 * @note: $wgExtraNamespaces and $wgNamespaceAliases have already been processed at this point
	 *        and should no longer be touched.
	 *
	 * @throws MWException
	 * @return bool
	 */
	public static function onSetupAfterCache() {
		global $wgNamespaceContentModels;

		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$entityNamespaceLookup = $wikibaseRepo->getEntityNamespaceLookup();
		$namespaces = $entityNamespaceLookup->getEntityNamespaces();

		if ( empty( $namespaces ) ) {
			throw new MWException( 'Wikibase: Incomplete configuration: '
				. '$wgWBRepoSettings[\'entityNamespaces\'] has to be set to an '
				. 'array mapping entity types to namespace IDs. '
				. 'See Wikibase.example.php for details and examples.' );
		}

		$contentModelIds = $wikibaseRepo->getContentModelMappings();

		foreach ( $namespaces as $entityType => $namespace ) {
			if ( !isset( $wgNamespaceContentModels[$namespace] ) ) {
				$wgNamespaceContentModels[$namespace] = $contentModelIds[$entityType];
			}
		}

		return true;
	}

	/**
	 * Hook to add PHPUnit test cases.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/UnitTestsList
	 *
	 * @since 0.1
	 *
	 * @param string[] &$paths
	 *
	 * @return bool
	 */
	public static function registerUnitTests( array &$paths ) {
		$paths[] = __DIR__ . '/tests/phpunit/';

		return true;
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ResourceLoaderTestModules
	 *
	 * @since 0.2 (in repo as RepoHooks::onResourceLoaderTestModules in 0.1)
	 *
	 * @param array &$testModules
	 * @param ResourceLoader &$resourceLoader
	 *
	 * @return boolean
	 */
	public static function registerQUnitTests( array &$testModules, ResourceLoader &$resourceLoader ) {
		$testModules['qunit'] = array_merge(
			$testModules['qunit'],
			include __DIR__ . '/tests/qunit/resources.php'
		);

		return true;
	}

	/**
	 * Handler for the NamespaceIsMovable hook.
	 *
	 * Implemented to prevent moving pages that are in an entity namespace.
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/NamespaceIsMovable
	 *
	 * @since 0.1
	 *
	 * @param int $ns Namespace ID
	 * @param bool $movable
	 *
	 * @return bool
	 */
	public static function onNamespaceIsMovable( $ns, &$movable ) {
		$namespaceLookup = WikibaseRepo::getDefaultInstance()->getEntityNamespaceLookup();

		if ( $namespaceLookup->isEntityNamespace( $ns ) ) {
			$movable = false;
		}

		return true;
	}

	/**
	 * Called when a revision was inserted due to an edit.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/NewRevisionFromEditComplete
	 *
	 * @since 0.1
	 *
	 * @param WikiPage $article A WikiPage object as of MediaWiki 1.19, an Article one before.
	 * @param Revision $revision
	 * @param int $baseID
	 * @param User $user
	 *
	 * @return bool
	 */
	public static function onNewRevisionFromEditComplete( $article, Revision $revision, $baseID, User $user ) {
		$entityContentFactory = WikibaseRepo::getDefaultInstance()->getEntityContentFactory();

		if ( $entityContentFactory->isEntityContentModel( $article->getContent()->getModel() ) ) {
			self::notifyEntityStoreWatcherOnUpdate( $revision );

			$notifier = WikibaseRepo::getDefaultInstance()->getChangeNotifier();

			if ( $revision->getParentId() === null ) {
				$notifier->notifyOnPageCreated( $revision );
			} else {
				$parent = Revision::newFromId( $revision->getParentId() );
				$notifier->notifyOnPageModified( $revision, $parent );
			}
		}

		return true;
	}

	/**
	 * @param Revision $revision
	 */
	private static function notifyEntityStoreWatcherOnUpdate( Revision $revision ) {
		/** @var EntityContent $content */
		$content = $revision->getContent();
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
	 * @since 0.1
	 *
	 * @param WikiPage &$wikiPage
	 * @param User &$user
	 * @param string $reason
	 * @param int $id id of the article that was deleted
	 * @param Content|null $content
	 * @param LogEntry $logEntry
	 *
	 * @throws MWException
	 */
	public static function onArticleDeleteComplete(
		WikiPage &$wikiPage,
		User &$user,
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
	 * @since 0.2
	 *
	 * @param Title $title
	 * @param bool $created
	 * @param string $comment
	 *
	 * @return bool
	 */
	public static function onArticleUndelete( Title $title, $created, $comment ) {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$entityContentFactory = $wikibaseRepo->getEntityContentFactory();

		// Bail out if we are not looking at an entity
		if ( !$entityContentFactory->isEntityContentModel( $title->getContentModel() ) ) {
			return true;
		}

		$revisionId = $title->getLatestRevID();
		$revision = Revision::newFromId( $revisionId );
		$content = $revision ? $revision->getContent() : null;

		if ( !( $content instanceof EntityContent ) ) {
			return true;
		}

		//XXX: EntityContent::save() also does this. Why are we doing this twice?
		$wikibaseRepo->getStore()->newEntityPerPage()->addEntityPage(
			$content->getEntityId(),
			$title->getArticleID()
		);

		$notifier = $wikibaseRepo->getChangeNotifier();
		$notifier->notifyOnPageUndeleted( $revision );

		return true;
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
	 * @return bool
	 */
	public static function onRecentChangeSave( RecentChange $recentChange ) {
		$logType = $recentChange->getAttribute( 'rc_log_type' );
		$logAction = $recentChange->getAttribute( 'rc_log_action' );
		$revId = $recentChange->getAttribute( 'rc_this_oldid' );

		if ( $revId <= 0 ) {
			// If we don't have a revision ID, we have no chance to find the right change to update.
			// NOTE: As of February 2015, RC entries for undeletion have rc_this_oldid = 0.
			return true;
		}

		if ( $logType === null || ( $logType === 'delete' && $logAction === 'restore' ) ) {
			$changeLookup = WikibaseRepo::getDefaultInstance()->getStore()->getEntityChangeLookup();

			$change = $changeLookup->loadByRevisionId( $revId, EntityChangeLookup::FROM_MASTER );

			if ( $change ) {
				$changeStore = WikibaseRepo::getDefaultInstance()->getStore()->getChangeStore();

				$change->setMetadataFromRC( $recentChange );
				$changeStore->saveChange( $change );
			}
		}

		return true;
	}

	/**
	 * Allows to add user preferences.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/GetPreferences
	 *
	 * NOTE: Might make sense to put the inner functionality into a well structured Preferences file once this
	 *       becomes more.
	 *
	 * @since 0.1
	 *
	 * @param User $user
	 * @param array &$preferences
	 *
	 * @return bool
	 */
	public static function onGetPreferences( User $user, array &$preferences ) {
		$preferences['wb-acknowledgedcopyrightversion'] = array(
			'type' => 'api'
		);

		$preferences['wikibase-entitytermsview-showEntitytermslistview'] = array(
			'type' => 'toggle',
			'label-message' => 'wikibase-setting-entitytermsview-showEntitytermslistview',
			'help-message' => 'wikibase-setting-entitytermsview-showEntitytermslistview-help',
			'section' => 'rendering/advancedrendering',
			'default' => '1',
		);

		return true;
	}

	/**
	 * Called after fetching the core default user options.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/UserGetDefaultOptions
	 *
	 * @param array &$defaultOptions
	 *
	 * @return bool
	 */
	public static function onUserGetDefaultOptions( array &$defaultOptions ) {
		// pre-select default language in the list of fallback languages
		$defaultLang = $defaultOptions['language'];
		$defaultOptions[ 'wb-languages-' . $defaultLang ] = 1;

		return true;
	}

	/**
	 * Modify line endings on history page.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/PageHistoryLineEnding
	 *
	 * @since 0.1
	 *
	 * @param HistoryPager $history
	 * @param object &$row
	 * @param string &$s
	 * @param array &$classes
	 *
	 * @return bool
	 */
	public static function onPageHistoryLineEnding( HistoryPager $history, &$row, &$s, array &$classes ) {
		$entityContentFactory = WikibaseRepo::getDefaultInstance()->getEntityContentFactory();

		$article = $history->getArticle();
		$rev = new Revision( $row );

		if ( $entityContentFactory->isEntityContentModel( $history->getTitle()->getContentModel() )
			&& $article->getPage()->getLatest() !== $rev->getID()
			&& $rev->getTitle()->quickUserCan( 'edit', $history->getUser() )
			&& !$rev->isDeleted( Revision::DELETED_TEXT )
		) {
			$link = Linker::linkKnown(
				$rev->getTitle(),
				$history->msg( 'wikibase-restoreold' )->escaped(),
				array(),
				array(
					'action' => 'edit',
					'restore' => $rev->getId()
				)
			);

			$s .= ' ' . $history->msg( 'parentheses' )->rawParams( $link )->escaped();
		}

		return true;
	}

	/**
	 * Alter the structured navigation links in SkinTemplates.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/SkinTemplateNavigation
	 *
	 * @since 0.1
	 *
	 * @param SkinTemplate $skinTemplate
	 * @param array $links
	 *
	 * @return bool
	 */
	public static function onPageTabs( SkinTemplate &$skinTemplate, array &$links ) {
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
					if ( $rev->isDeleted( Revision::DELETED_TEXT ) ) {
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

		return true;
	}

	/**
	 * Reorder the groups for the special pages
	 *
	 * @since 0.4
	 *
	 * @param array &$groups
	 * @param bool &$moveOther
	 *
	 * @return bool
	 */
	public static function onSpecialPageReorderPages( &$groups, &$moveOther ) {
		$groups = array_merge( array( 'wikibaserepo' => null ), $groups );
		return true;
	}

	/**
	 * Used to append a css class to the body, so the page can be identified as Wikibase item page.
	 * @see http://www.mediawiki.org/wiki/Manual:Hooks/OutputPageBodyAttributes
	 *
	 * @since 0.1
	 *
	 * @param OutputPage $out
	 * @param Skin $sk
	 * @param array $bodyAttrs
	 *
	 * @return bool
	 */
	public static function onOutputPageBodyAttributes( OutputPage $out, Skin $sk, array &$bodyAttrs ) {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$outputPageEntityIdReader = new OutputPageEntityIdReader(
			$wikibaseRepo->getEntityContentFactory(),
			$wikibaseRepo->getEntityIdParser()
		);

		$entityId = $outputPageEntityIdReader->getEntityIdFromOutputPage( $out );

		if ( $entityId === null ) {
			return true;
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

		if ( $out->getRevisionId() !== $out->getTitle()->getLatestRevID() ) {
			$bodyAttrs['class'] .= ' wb-oldrevpage';
		}

		return true;
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
	 * @param array|string|null   $message Output-parameter holding for the message the call should fail with.
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

				if ( $handler->getEntityNamespace() == $namespace ) {
					// trying to use ApiEditPage on an entity namespace
					$params = $module->extractRequestParams();

					// allow undo
					if ( $params['undo'] > 0 ) {
						return true;
					}

					// fail
					$message = array(
						'wikibase-no-direct-editing',
						$pageObj->getTitle()->getNsText()
					);

					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Format the output when the search result contains entities
	 *
	 * @since 0.3
	 *
	 * @param SpecialSearch $searchPage
	 * @param SearchResult $result
	 * @param array $terms
	 * @param string &$link
	 * @param string &$redirect
	 * @param string &$section
	 * @param string &$extract
	 * @param string &$score
	 * @param string &$size
	 * @param string &$date
	 * @param string &$related
	 * @param string &$html
	 *
	 * @return bool
	 */
	public static function onShowSearchHit( SpecialSearch $searchPage, SearchResult $result, $terms,
		&$link, &$redirect, &$section, &$extract, &$score, &$size, &$date, &$related, &$html
	) {
		$entityContentFactory = WikibaseRepo::getDefaultInstance()->getEntityContentFactory();

		$title = $result->getTitle();
		$contentModel = $title->getContentModel();

		if ( $entityContentFactory->isEntityContentModel( $contentModel ) ) {
			/** @var EntityContent $content */
			$page = WikiPage::factory( $title );
			$content = $page->getContent();

			if ( $content && !$content->isRedirect() ) {
				$entity = $content->getEntity();
				$languageCode = $searchPage->getLanguage()->getCode(); // TODO: language fallback!

				if ( $entity instanceof DescriptionsProvider &&
					$entity->getDescriptions()->hasTermForLanguage( $languageCode )
				) {
					$description = $entity->getDescriptions()->getByLanguage( $languageCode )->getText();
					$attr = array( 'class' => 'wb-itemlink-description' );
					$link .= wfMessage( 'colon-separator' )->text();
					$link .= Html::element( 'span', $attr, $description );
				}
			}

			$extract = ''; // TODO: set this to something useful.
		}

		return true;
	}

	/**
	 * Remove span tag (added by Cirrus) placed around title search hit for entity titles
	 * to highlight matches in bold.
	 *
	 * @todo highlight the Q## part of the entity link formatting and highlight label matches
	 *
	 * @param string &$link_t
	 * @param string &$titleSnippet
	 * @param SearchResult $result
	 *
	 * @return bool
	 */
	public static function onShowSearchHitTitle( &$link_t, &$titleSnippet, SearchResult $result ) {
		$title = $result->getTitle();
		$namespaceLookup = WikibaseRepo::getDefaultInstance()->getEntityNamespaceLookup();

		if ( $namespaceLookup->isEntityNamespace( $title->getNamespace() ) ) {
			$titleSnippet = $title->getPrefixedText();
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
	 * @since 0.3
	 *
	 * @param Title $title
	 * @param array $types The types of protection available
	 *
	 * @return bool
	 */
	public static function onTitleGetRestrictionTypes( Title $title, array &$types ) {
		$namespaceLookup = WikibaseRepo::getDefaultInstance()->getEntityNamespaceLookup();

		if ( $namespaceLookup->isEntityNamespace( $title->getNamespace() ) ) {
			// Remove create and move protection for Wikibase namespaces
			$types = array_diff( $types, array( 'create', 'move' ) );
		}

		return true;
	}

	/**
	 * Handler for the TitleQuickPermissions hook, implemented to point out that entity pages cannot
	 * be "created" normally.
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/TitleQuickPermissions
	 *
	 * @since 0.5
	 *
	 * @param Title $title The Title being checked
	 * @param User $user The User performing the action
	 * @param string $action The action being performed
	 * @param array[] &$errors
	 * @param bool $doExpensiveQueries Whether to do expensive DB queries
	 * @param bool $short Whether to return immediately on first error
	 *
	 * @return bool
	 */
	public static function onTitleQuickPermissions(
		Title $title,
		User $user,
		$action,
		array &$errors,
		$doExpensiveQueries,
		$short
	) {
		if ( $action === 'create' ) {
			$namespaceLookup = WikibaseRepo::getDefaultInstance()->getEntityNamespaceLookup();

			if ( $namespaceLookup->isEntityNamespace( $title->getNamespace() ) ) {
				// Do not allow normal creation of pages in Wikibase namespaces
				$errors[] = array( 'wikibase-no-direct-editing', $title->getNsText() );

				return false;
			}
		}

		return true;
	}

	/**
	 * Hook handler for AbuseFilter's AbuseFilter-contentToString hook, implemented
	 * to provide a custom text representation of Entities for filtering.
	 *
	 * @param Content $content The content object
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
	 * @param string[] $data Extra data supplied when registering the hook function,
	 *        matches list( $contentModel, $messagePrefix ).
	 * @param string &$comment reference to the autocomment text
	 * @param bool $pre true if there is content before the autocomment
	 * @param string $auto the autocomment unformatted
	 * @param bool $post true if there is content after the autocomment
	 * @param Title|null $title use for further information
	 * @param bool $local shall links be generated locally or globally
	 *
	 * @return bool
	 */
	public static function onFormat( $data, &$comment, $pre, $auto, $post, $title, $local ) {
		global $wgLang, $wgTitle;

		list( $contentModel, $messagePrefix ) = $data;

		// If it is possible to avoid loading the whole page then the code will be lighter on the server.
		if ( !( $title instanceof Title ) ) {
			$title = $wgTitle;
		}

		if ( !( $title instanceof Title ) || $title->getContentModel() !== $contentModel ) {
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

		$formatter = new AutoCommentFormatter( $wgLang, array( $messagePrefix, 'wikibase-entity' ) );
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
	 *
	 * @return bool
	 */
	public static function onOutputPageParserOutput( OutputPage $out, ParserOutput $parserOutput ) {
		// Set in EntityParserOutputGenerator.
		$placeholders = $parserOutput->getExtensionData( 'wikibase-view-chunks' );
		if ( $placeholders !== null ) {
			$out->setProperty( 'wikibase-view-chunks', $placeholders );
		}

		// Used in ViewEntityAction and EditEntityAction to override the page HTML title
		// with the label, if available, or else the id. Passed via parser output
		// and output page to save overhead of fetching content and accessing an entity
		// on page view.
		$titleText = $parserOutput->getExtensionData( 'wikibase-titletext' );
		if ( $titleText !== null ) {
			$out->setProperty( 'wikibase-titletext', $titleText );
		}

		// Array with <link rel="alternate"> tags for the page HEAD.
		$alternateLinks = $parserOutput->getExtensionData( 'wikibase-alternate-links' );
		if ( $alternateLinks !== null ) {
			foreach ( $alternateLinks as $link ) {
				$out->addLink( $link );
			}
		}

		return true;
	}

	/**
	 * Handler for the ContentModelCanBeUsedOn hook, used to prevent pages of inappropriate type
	 * to be placed in an entity namespace.
	 *
	 * @param string $contentModel
	 * @param Title $title
	 * @param bool $ok
	 *
	 * @return bool
	 */
	public static function onContentModelCanBeUsedOn( $contentModel, Title $title, &$ok ) {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();

		$namespaceLookup = $wikibaseRepo->getEntityNamespaceLookup();
		$contentModelIds = $wikibaseRepo->getContentModelMappings();

		$expectedModel = false;
		$expectedEntityType = array_search(
			$title->getNamespace(),
			$namespaceLookup->getEntityNamespaces()
		);
		if ( $expectedEntityType !== false ) {
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
	 * Handler for the ContentHandlerForModelID hook, implemented to create EntityHandler
	 * instances that have knowledge of the necessary services.
	 *
	 * @param string $modelId
	 * @param ContentHandler|null $handler
	 *
	 * @return bool|null False on success to stop other ContentHandlerForModelID hooks from running,
	 *  null on error.
	 */
	public static function onContentHandlerForModelID( $modelId, &$handler ) {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();

		try {
			$handler = $wikibaseRepo->getEntityContentFactory()->getEntityHandlerForContentModel( $modelId );
			return false;
		} catch ( OutOfBoundsException $ex ) {
			// no entity content model id
		}
	}

	/**
	 * Adds a list of data value types to the action=query&meta=siteinfo API.
	 *
	 * @param ApiQuerySiteinfo $api
	 * @param array &$data
	 */
	public static function onAPIQuerySiteInfoGeneralInfo( ApiQuerySiteinfo $api, array &$data ) {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$dataTypes = $wikibaseRepo->getDataTypeFactory()->getTypes();
		$propertyTypes = array();

		foreach ( $dataTypes as $id => $type ) {
			$propertyTypes[$id] = array( 'valuetype' => $type->getDataValueType() );
		}

		$data['wikibase-propertytypes'] = $propertyTypes;

		$sparqlEndpoint = $wikibaseRepo->getSettings()->getSetting( 'sparqlEndpoint' );
		if ( is_string( $sparqlEndpoint ) ) {
			$data['wikibase-sparql'] = $sparqlEndpoint;
		}
	}

	/**
	 * Helper for onAPIQuerySiteInfoStatisticsInfo
	 * @param object $row
	 * @return array
	 */
	private static function formatDispatchRow( $row ) {
		$data = array(
			'pending' => $row->chd_pending,
			'lag' => $row->chd_lag,
		);
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
	 * @param array $data
	 * @return bool
	 */
	public static function onAPIQuerySiteInfoStatisticsInfo( array &$data ) {
		$stats = new DispatchStats();
		$stats->load();
		if ( $stats->hasStats() ) {
			$data['dispatch'] = array(
				'oldest' => array(
					'id' => $stats->getMinChangeId(),
					'timestamp' => $stats->getMinChangeTimestamp(),
				),
				'newest' => array(
					'id' => $stats->getMaxChangeId(),
					'timestamp' => $stats->getMaxChangeTimestamp(),
				),
				'freshest' => self::formatDispatchRow( $stats->getFreshest() ),
				'median' => self::formatDispatchRow( $stats->getMedian() ),
				'stalest' => self::formatDispatchRow( $stats->getStalest() ),
				'average' => self::formatDispatchRow( $stats->getAverage() ),
			);
		}

		return true;
	}

	/**
	 * Called by Import.php. Implemented to prevent the import of entities.
	 *
	 * @param object $importer unclear, see Bug T66657
	 * @param array $pageInfo
	 * @param array $revisionInfo
	 *
	 * @throws MWException
	 * @return bool
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

		return true;
	}

	/**
	 * Called in SkinTemplate::buildNavUrls(), allows us to set up navigation URLs to later be used
	 * in the toolbox.
	 *
	 * @param SkinTemplate $skinTemplate
	 * @param array $navigationUrls
	 *
	 * @return bool
	 */
	public static function onSkinTemplateBuildNavUrlsNavUrlsAfterPermalink(
		SkinTemplate $skinTemplate,
		array &$navigationUrls
	) {
		$title = $skinTemplate->getTitle();
		$namespaceLookup = WikibaseRepo::getDefaultInstance()->getEntityNamespaceLookup();

		if ( !$namespaceLookup->isEntityNamespace( $title->getNamespace() ) ) {
			return true;
		}

		$baseUri = WikibaseRepo::getDefaultInstance()->getSettings()->getSetting( 'conceptBaseUri' );
		$navigationUrls['wb-concept-uri'] = array(
			'text' => $skinTemplate->msg( 'wikibase-concept-uri' ),
			'href' => $baseUri . $title->getDBKey(),
			'title' => $skinTemplate->msg( 'wikibase-concept-uri-tooltip' )
		);

		return true;
	}

	/**
	 * Called in BaseTemplate::getToolbox(), allows us to add navigation URLs to the toolbox.
	 *
	 * @param BaseTemplate $baseTemplate
	 * @param array $toolbox
	 *
	 * @return bool
	 */
	public static function onBaseTemplateToolbox( BaseTemplate $baseTemplate, array &$toolbox ) {
		if ( !isset( $baseTemplate->data['nav_urls']['wb-concept-uri'] ) ) {
			return true;
		}

		$toolbox['wb-concept-uri'] = $baseTemplate->data['nav_urls']['wb-concept-uri'];
		$toolbox['wb-concept-uri']['id'] = 't-wb-concept-uri';

		return true;
	}

	/**
	 * Disable mobile editor for entity pages in Extension:MobileFrontend.
	 * @see https://www.mediawiki.org/wiki/Extension:MobileFrontend
	 *
	 * @param Skin $skin
	 * @param array &$modules associative array of resource loader modules
	 *
	 * @return bool
	 */
	public static function onSkinMinervaDefaultModules( Skin $skin, array &$modules ) {
		$title = $skin->getTitle();
		$namespaceLookup = WikibaseRepo::getDefaultInstance()->getEntityNamespaceLookup();

		// remove the editor module so that it does not get loaded on entity pages
		if ( $namespaceLookup->isEntityNamespace( $title->getNamespace() ) ) {
			unset( $modules['editor'] );
		}

		return true;
	}

	/**
	 * Register ResourceLoader modules with dynamic dependencies.
	 *
	 * @param ResourceLoader $resourceLoader
	 *
	 * @return bool
	 */
	public static function onResourceLoaderRegisterModules( ResourceLoader $resourceLoader ) {
		preg_match( '+' . preg_quote( DIRECTORY_SEPARATOR ) . '(?:vendor|extensions)'
			. preg_quote( DIRECTORY_SEPARATOR ) . '.*+', __DIR__, $remoteExtPath );

		$moduleTemplate = array(
			'localBasePath' => __DIR__,
			'remoteExtPath' => '..' . $remoteExtPath[0],
			'position' => 'top' // reducing the time between DOM construction and JS initialisation
		);

		$modules = array(
			'wikibase.WikibaseContentLanguages' => $moduleTemplate + array(
				'scripts' => array(
					'resources/wikibase.WikibaseContentLanguages.js',
				),
				'dependencies' => array(
					'util.ContentLanguages',
					'util.inherit',
					'wikibase',
				),
			),
			'wikibase.special.languageLabelDescriptionAliases' => $moduleTemplate + array(
				'scripts' => array(
					'resources/wikibase.special/wikibase.special.languageLabelDescriptionAliases.js',
				),
				'dependencies' => array(
					'oojs-ui',
				),
				'messages' => array(
					'wikibase-label-edit-placeholder',
					'wikibase-label-edit-placeholder-language-aware',
					'wikibase-description-edit-placeholder',
					'wikibase-description-edit-placeholder-language-aware',
					'wikibase-aliases-edit-placeholder',
					'wikibase-aliases-edit-placeholder-language-aware',
				),
			),
		);

		$isUlsLoaded = ExtensionRegistry::getInstance()->isLoaded( 'UniversalLanguageSelector' );
		if ( $isUlsLoaded ) {
			$modules['wikibase.WikibaseContentLanguages']['dependencies'][] = 'ext.uls.languagenames';
			$modules['wikibase.special.languageLabelDescriptionAliases']['dependencies'][] = 'ext.uls.mediawiki';
		}

		$resourceLoader->register( $modules );

		return true;
	}

}
