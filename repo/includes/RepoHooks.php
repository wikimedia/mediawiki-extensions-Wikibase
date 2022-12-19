<?php

namespace Wikibase\Repo;

use ApiBase;
use ApiEditPage;
use ApiMain;
use ApiModuleManager;
use ApiQuery;
use ApiQuerySiteinfo;
use Content;
use ExtensionRegistry;
use HistoryPager;
use IContextSource;
use LogEntry;
use MediaWiki\Linker\LinkTarget;
use MediaWiki\MediaWikiServices;
use MediaWiki\ResourceLoader\ResourceLoader;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Revision\SlotRecord;
use MediaWiki\User\UserIdentity;
use MWException;
use OutputPage;
use Parser;
use ParserOptions;
use ParserOutput;
use Skin;
use SkinTemplate;
use StubUserLang;
use Throwable;
use Title;
use UnexpectedValueException;
use User;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Lib\Formatters\AutoCommentFormatter;
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
use Wikibase\Repo\Hooks\Helpers\OutputPageEntityViewChecker;
use Wikibase\Repo\Hooks\InfoActionHookHandler;
use Wikibase\Repo\Hooks\OutputPageEntityIdReader;
use Wikibase\Repo\Hooks\SidebarBeforeOutputHookHandler;
use Wikibase\Repo\ParserOutput\PlaceholderEmittingEntityTermsView;
use Wikibase\Repo\ParserOutput\TermboxFlag;
use Wikibase\Repo\ParserOutput\TermboxVersionParserCacheValueRejector;
use Wikibase\Repo\ParserOutput\TermboxView;
use Wikibase\Repo\Store\RateLimitingIdGenerator;
use Wikibase\Repo\Store\Sql\SqlSubscriptionLookup;
use Wikibase\View\ViewHooks;
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
	 * It additionally schedules a WikibasePingback
	 *
	 * @param OutputPage $out
	 * @param Skin $skin
	 */
	public static function onBeforePageDisplay( OutputPage $out, Skin $skin ) {
		$settings = WikibaseRepo::getSettings();
		if ( $settings->getSetting( 'enableEntitySearchUI' ) === true ) {

			if ( $skin->getSkinName() === 'vector-2022' ) {
				$out->addModules( 'wikibase.vector.searchClient' );
			} else {
				$out->addModules( 'wikibase.ui.entitysearch' );
			}
		}

		if ( $settings->getSetting( 'wikibasePingback' ) ) {
			WikibasePingback::schedulePingback();
		}
	}

	/**
	 * Handler for the BeforePageDisplayMobile hook that adds the wikibase mobile styles.
	 *
	 * @param OutputPage $out
	 * @param Skin $skin
	 */
	public static function onBeforePageDisplayMobile( OutputPage $out, Skin $skin ) {
		$entityNamespaceLookup = WikibaseRepo::getEntityNamespaceLookup();
		$namespace = $out->getTitle()->getNamespace();
		$isEntityTitle = $entityNamespaceLookup->isNamespaceWithEntities( $namespace );

		if ( $isEntityTitle ) {
			$out->addModules( 'wikibase.mobile' );

			$useNewTermbox = WikibaseRepo::getSettings()->getSetting( 'termboxEnabled' );
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
	 * Handler for the SetupAfterCache hook, completing the content and namespace setup.
	 * This updates the $wgContentHandlers and $wgNamespaceContentModels registries
	 * according to information provided by entity type definitions and the entityNamespaces
	 * setting for the local entity source.
	 *
	 * @throws MWException
	 */
	public static function onSetupAfterCache() {
		global $wgContentHandlers,
			$wgNamespaceContentModels;

		if ( WikibaseRepo::getSettings()->getSetting( 'defaultEntityNamespaces' ) ) {
			self::defaultEntityNamespaces();
		}

		$namespaces = WikibaseRepo::getLocalEntitySource()->getEntityNamespaceIds();
		$namespaceLookup = WikibaseRepo::getEntityNamespaceLookup();

		// Register entity namespaces.
		// Note that $wgExtraNamespaces and $wgNamespaceAliases have already been processed at this
		// point and should no longer be touched.
		$contentModelIds = WikibaseRepo::getContentModelMappings();

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
			$wgContentHandlers[$model] = function () use ( $entityType ) {
				$entityContentFactory = WikibaseRepo::getEntityContentFactory();
				return $entityContentFactory->getContentHandlerForType( $entityType );
			};
		}

		return true;
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

	/**
	 * Hook to add PHPUnit test cases.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/UnitTestsList
	 *
	 * @param string[] &$paths
	 */
	public static function registerUnitTests( array &$paths ) {
		$paths[] = __DIR__ . '/../tests/phpunit/';
		$paths[] = __DIR__ . '/../rest-api/tests/phpunit/';
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
		if ( self::isNamespaceUsedByLocalEntities( $ns ) ) {
			$movable = false;
		}
	}

	private static function isNamespaceUsedByLocalEntities( $namespace ) {
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

	/**
	 * Called when a revision was inserted due to an edit.
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/RevisionFromEditComplete
	 *
	 * @param WikiPage $wikiPage
	 * @param RevisionRecord $revisionRecord
	 * @param int $baseID
	 * @param UserIdentity $user
	 */
	public static function onRevisionFromEditComplete(
		WikiPage $wikiPage,
		RevisionRecord $revisionRecord,
		$baseID,
		UserIdentity $user
	) {
		$entityContentFactory = WikibaseRepo::getEntityContentFactory();

		if ( $entityContentFactory->isEntityContentModel( $wikiPage->getContent()->getModel() ) ) {
			self::notifyEntityStoreWatcherOnUpdate(
				$revisionRecord->getContent( SlotRecord::MAIN ),
				$revisionRecord
			);

			$notifier = WikibaseRepo::getChangeNotifier();
			$parentId = $revisionRecord->getParentId();

			if ( !$parentId ) {
				$notifier->notifyOnPageCreated( $revisionRecord );
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
					$notifier->notifyOnPageModified( $revisionRecord, $parent );
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
		?Content $content,
		LogEntry $logEntry
	) {
		$entityContentFactory = WikibaseRepo::getEntityContentFactory();

		// Bail out if we are not looking at an entity
		if ( !$content || !$entityContentFactory->isEntityContentModel( $content->getModel() ) ) {
			return;
		}

		/** @var EntityContent $content */
		'@phan-var EntityContent $content';

		// Notify storage/lookup services that the entity was deleted. Needed to track page-level deletion.
		// May be redundant in some cases. Take care not to cause infinite regress.
		WikibaseRepo::getEntityStoreWatcher()->entityDeleted( $content->getEntityId() );

		$notifier = WikibaseRepo::getChangeNotifier();
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
		$entityContentFactory = WikibaseRepo::getEntityContentFactory();

		// Bail out if we are not looking at an entity
		if ( !$entityContentFactory->isEntityContentModel( $title->getContentModel() ) ) {
			return;
		}

		$revisionId = $title->getLatestRevID();
		$revisionRecord = MediaWikiServices::getInstance()
			->getRevisionLookup()
			->getRevisionById( $revisionId );
		$content = $revisionRecord ? $revisionRecord->getContent( SlotRecord::MAIN ) : null;

		if ( !( $content instanceof EntityContent ) ) {
			return;
		}

		$notifier = WikibaseRepo::getChangeNotifier();
		$notifier->notifyOnPageUndeleted( $revisionRecord );
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
	 * @param object $row
	 * @param string &$html
	 * @param array $classes
	 */
	public static function onPageHistoryLineEnding( HistoryPager $history, $row, &$html, array $classes ) {
		// Note: This assumes that HistoryPager::getTitle returns a Title.
		$entityContentFactory = WikibaseRepo::getEntityContentFactory();
		$services = MediaWikiServices::getInstance();

		$wikiPage = $services->getWikiPageFactory()->newFromTitle( $history->getTitle() );
		$revisionRecord = $services->getRevisionFactory()->newRevisionFromRow( $row );
		$linkTarget = $revisionRecord->getPageAsLinkTarget();

		if ( $entityContentFactory->isEntityContentModel( $history->getTitle()->getContentModel() )
			&& $wikiPage->getLatest() !== $revisionRecord->getId()
			&& $services->getPermissionManager()->quickUserCan(
				'edit',
				$history->getUser(),
				$linkTarget
			)
			&& !$revisionRecord->isDeleted( RevisionRecord::DELETED_TEXT )
		) {
			$link = $services->getLinkRenderer()->makeKnownLink(
				$linkTarget,
				$history->msg( 'wikibase-restoreold' )->text(),
				[],
				[
					'action' => 'edit',
					'restore' => $revisionRecord->getId(),
				]
			);

			$html .= ' ' . $history->msg( 'parentheses' )->rawParams( $link )->escaped();
		}
	}

	/**
	 * Alter the structured navigation links in SkinTemplates.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/SkinTemplateNavigation::Universal
	 *
	 * @todo T282549 Consider moving some of this logic into a place where it can be more adequately tested
	 *
	 * @param SkinTemplate $skinTemplate
	 * @param array[] &$links
	 */
	public static function onSkinTemplateNavigationUniversal( SkinTemplate $skinTemplate, array &$links ) {
		$entityContentFactory = WikibaseRepo::getEntityContentFactory();

		$title = $skinTemplate->getRelevantTitle();

		if ( $entityContentFactory->isEntityContentModel( $title->getContentModel() ) ) {
			unset( $links['views']['edit'] );
			unset( $links['views']['viewsource'] );

			if ( MediaWikiServices::getInstance()->getPermissionManager()
					->quickUserCan( 'edit', $skinTemplate->getUser(), $title )
			) {
				$out = $skinTemplate->getOutput();
				$request = $skinTemplate->getRequest();
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
							'text' => $skinTemplate->getLanguage()->ucfirst(
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
	 * Reorder the groups for the special pages
	 *
	 * @param array &$groups
	 * @param bool $moveOther
	 */
	public static function onSpecialPageReorderPages( &$groups, $moveOther ) {
		$groups = array_merge( [ 'wikibaserepo' => null ], $groups );
	}

	/**
	 * Used to append a css class to the body, so the page can be identified as Wikibase item page.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/OutputPageBodyAttributes
	 *
	 * @param OutputPage $out
	 * @param Skin $skin
	 * @param array &$bodyAttrs
	 */
	public static function onOutputPageBodyAttributes( OutputPage $out, Skin $skin, array &$bodyAttrs ) {
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
	 *                            This can be a message key or an array as expected by ApiBase::dieWithError().
	 *
	 * @return bool true to continue execution, false to abort and with $message as an error message.
	 */
	public static function onApiCheckCanExecute( ApiBase $module, User $user, &$message ) {
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
	 * Called when pushing meta-info from the ParserOutput into OutputPage.
	 * Used to transfer 'wikibase-view-chunks' and entity data from ParserOutput to OutputPage.
	 *
	 * @param OutputPage $outputPage
	 * @param ParserOutput $parserOutput
	 */
	public static function onOutputPageParserOutput( OutputPage $outputPage, ParserOutput $parserOutput ) {
		// Set in EntityParserOutputGenerator.
		$placeholders = $parserOutput->getExtensionData( 'wikibase-view-chunks' );
		if ( $placeholders !== null ) {
			$outputPage->setProperty( 'wikibase-view-chunks', $placeholders );
		}

		// Set in EntityParserOutputGenerator.
		$termsListItems = $parserOutput->getExtensionData( 'wikibase-terms-list-items' );
		if ( $termsListItems !== null ) {
			$outputPage->setProperty( 'wikibase-terms-list-items', $termsListItems );
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
	 * @param string $contentModel
	 * @param LinkTarget $title Actually a Title object, but we only require getNamespace
	 * @param bool &$ok
	 *
	 * @return bool
	 */
	public static function onContentModelCanBeUsedOn( $contentModel, LinkTarget $title, &$ok ) {
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
		if ( $entitySource->getSourceName() !== WikibaseRepo::getLocalEntitySource()->getSourceName() ) {
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
	 * @param ApiQuerySiteinfo $api
	 * @param array &$data
	 */
	public static function onAPIQuerySiteInfoGeneralInfo( ApiQuerySiteinfo $api, array &$data ) {
		$repoSettings = WikibaseRepo::getSettings();
		$dataTypes = WikibaseRepo::getDataTypeFactory()->getTypes();
		$propertyTypes = [];

		foreach ( $dataTypes as $id => $type ) {
			$propertyTypes[$id] = [ 'valuetype' => $type->getDataValueType() ];
		}

		$data['wikibase-propertytypes'] = $propertyTypes;

		$data['wikibase-conceptbaseuri'] = WikibaseRepo::getLocalEntitySource()->getConceptBaseUri();

		$geoShapeStorageBaseUrl = $repoSettings->getSetting( 'geoShapeStorageBaseUrl' );
		$data['wikibase-geoshapestoragebaseurl'] = $geoShapeStorageBaseUrl;

		$tabularDataStorageBaseUrl = $repoSettings->getSetting( 'tabularDataStorageBaseUrl' );
		$data['wikibase-tabulardatastoragebaseurl'] = $tabularDataStorageBaseUrl;

		$sparqlEndpoint = $repoSettings->getSetting( 'sparqlEndpoint' );
		if ( is_string( $sparqlEndpoint ) ) {
			$data['wikibase-sparql'] = $sparqlEndpoint;
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
			$contentModels = WikibaseRepo::getContentModelMappings();
			$allowImport = WikibaseRepo::getSettings()->getSetting( 'allowEntityImport' );

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
	 * Add Concept URI link to the toolbox section of the sidebar.
	 *
	 * @param Skin $skin
	 * @param string[] &$sidebar
	 * @return void
	 */
	public static function onSidebarBeforeOutput( Skin $skin, array &$sidebar ): void {
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

	/**
	 * Register ResourceLoader modules with dynamic dependencies.
	 *
	 * @param ResourceLoader $resourceLoader
	 */
	public static function onResourceLoaderRegisterModules( ResourceLoader $resourceLoader ) {
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
				'targets' => [ 'desktop', 'mobile' ],
			],
			'wikibase.special.languageLabelDescriptionAliases' => $moduleTemplate + [
				// T326405
				'targets' => [ 'desktop', 'mobile' ],
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
		$defaults['termboxVersion'] = null;
		$inCacheKey['termboxVersion'] = true;
		$lazyOptions['termboxVersion'] = function () {
			return TermboxFlag::getInstance()->shouldRenderTermbox() ?
				TermboxView::TERMBOX_VERSION . TermboxView::CACHE_VERSION :
				PlaceholderEmittingEntityTermsView::TERMBOX_VERSION . PlaceholderEmittingEntityTermsView::CACHE_VERSION;
		};
	}

	public static function onRejectParserCacheValue( ParserOutput $parserValue, WikiPage $wikiPage, ParserOptions $parserOpts ) {
		$rejector = new TermboxVersionParserCacheValueRejector( TermboxFlag::getInstance() );
		return $rejector->keepCachedValue( $parserValue, $parserOpts );
	}

	public static function onApiQueryModuleManager( ApiModuleManager $moduleManager ) {
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

	/**
	 * Register the parser functions.
	 *
	 * @param Parser $parser
	 */
	public static function onParserFirstCallInit( Parser $parser ) {
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
	 * @param array $rateLimits should be $wgRateLimits or a similar array
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
	 * @param ApiMain $apiMain
	 * @param Throwable $e
	 * @return bool|void
	 * @throws MWException
	 */
	public static function onApiMainOnException( $apiMain, $e ) {
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

	/** @param ContentLanguages[] $contentLanguages */
	public static function onWikibaseContentLanguages( array &$contentLanguages ): void {
		if ( !WikibaseRepo::getSettings()->getSetting( 'tmpEnableMulLanguageCode' ) ) {
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

	public static function onMaintenanceShellStart(): void {
		require_once __DIR__ . '/MaintenanceShellStart.php';
	}
}
