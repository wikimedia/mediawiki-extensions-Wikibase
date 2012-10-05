<?php
namespace Wikibase;

/**
 * File defining the hook handlers for the Wikibase Client extension.
 *
 * @since 0.1
 *
 * @file
 * @ingroup WikibaseClient
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
use Title;

final class ClientHooks {

	/**
	 * Schema update to set up the needed database tables.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/LoadExtensionSchemaUpdates
	 *
	 * @since 0.1
	 *
	 * @param \DatabaseUpdater $updater
	 *
	 * @return boolean
	 */
	public static function onSchemaUpdate( \DatabaseUpdater $updater ) {
		$type = $updater->getDB()->getType();

		if ( $type === 'mysql' || $type === 'sqlite' /* || $type === 'postgres' */ ) {
			$extension = $type === 'postgres' ? '.pg.sql' : '.sql';

			$updater->addExtensionTable(
				'wbc_entity_cache',
				__DIR__ . '/sql/WikibaseCache' . $extension
			);

			$updater->addExtensionTable(
				'wbc_item_usage',
				__DIR__ . '/sql/KillLocalItems.sql'
			);

			$updater->addExtensionTable(
				'wbc_item_usage',
				__DIR__ . '/sql/WikibaseClient' . $extension
			);
		}
		else {
			wfWarn( "Database type '$type' is not supported by Wikibase Client." );
		}

		return true;
	}

	/**
	 * Hook to add PHPUnit test cases.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/UnitTestsList
	 *
	 * @since 0.1
	 *
	 * @param array $files
	 *
	 * @return boolean
	 */
	public static function registerUnitTests( array &$files ) {
		// @codeCoverageIgnoreStart
		$testFiles = array(
			'includes/CachedEntity',
			'includes/EntityCacheUpdater',

			'includes/store/EntityCacheTable',
		);

		foreach ( $testFiles as $file ) {
			$files[] = __DIR__ . '/tests/phpunit/' . $file . 'Test.php';
		}

		return true;
		// @codeCoverageIgnoreEnd
	}

	/**
	 * When the poll script finds a new change or set of changes, it will fire
	 * this hook for each change, so it can be handled appropriately.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/WikibasePollHandle
	 *
	 * @since 0.1
	 *
	 * @param Change $change
	 *
	 * @return boolean
	 */
	public static function onWikibasePollHandle( Change $change ) {
		list( $mainType, ) = explode( '~', $change->getType() ); //@todo: ugh! provide getter for entity type!

		// strip the wikibase- prefix
		$mainType = preg_replace( '/^wikibase-/', '', $mainType );

		if ( in_array( $mainType, EntityFactory::singleton()->getEntityTypes() ) ) {

			$cacheUpdater = new EntityCacheUpdater();
			$cacheUpdater->handleChange( $change );

			// The following code is a temporary hack to invalidate the cache.
			// TODO: create cache invalidater that works with all clients for this cluster
			if ( $mainType == Item::ENTITY_TYPE ) { //FIXME: handle all kinds of entities!
				/**
				 * @var Item $item
				 */
				$item = $change->getEntity();
				$siteGlobalId = Settings::get( 'siteGlobalID' );
				$siteLink = $item->getSiteLink( $siteGlobalId );
				$title = null;

				$info = $change->getField( 'info' );

				if ( $siteLink !== null ) {
					$page = $siteLink->getPage();

					if ( array_key_exists( 'diff', $info ) ) {
						$siteLinkChangeOperations = $change->getDiff()->getSiteLinkDiff()->getTypeOperations( 'change' );

						// handle when a link to this client is changed to some other page
						// remove lang links on the old page, add them to new page that item links to
						if ( is_array( $siteLinkChangeOperations ) && array_key_exists( $siteGlobalId, $siteLinkChangeOperations ) ) {
							$oldTitle = \Title::newFromText( $siteLinkChangeOperations[ $siteGlobalId ]->getOldValue() );
							$newTitle = \Title::newFromText( $siteLinkChangeOperations[ $siteGlobalId ]->getNewValue() );

							if ( !is_null( $oldTitle ) ) {
								self::updatePage( $oldTitle, $change, true );
							}

							if ( !is_null( $newTitle ) ) {
								self::updatePage( $newTitle, $change, false );
							}
						// a lang link was added or removed
						} else {
							$title = \Title::newFromText( $page );
							if ( !is_null( $title ) ) {
								self::updatePage( $title, $change );
							}
						}
					} else {
						// handle item deletion or restore
						$title = \Title::newFromText( $page );
						if ( !is_null( $title ) ) {
							self::updatePage( $title, $change );
						}
					}
				} else if ( array_key_exists( 'diff', $info ) ) {
					// cache should be invalidated when the sitelink got removed
					$removedSiteLinks = $change->getDiff()->getSiteLinkDiff()->getRemovedValues();
					if ( is_array( $removedSiteLinks ) && array_key_exists( $siteGlobalId, $removedSiteLinks ) ) {
						$title = \Title::newFromText( $removedSiteLinks[ $siteGlobalId ] );
						if ( !is_null( $title ) ) {
							self::updatePage( $title, $change, true );
						}
					}
				}
			}
		}

		return true;
	}

	/**
	 * @param \Title $title  The Title of the page to update
	 * @param Change $change The Change that caused the update
	 * @param bool $gone If set, indicates that the change's entity no longer refers to the given page.
	 */
	protected static function updatePage( Title $title, Change $change, $gone = false ) {
		global $wgContLang;

		if ( !$title->exists() ) {
			return;
		}

		$title->invalidateCache();

		$fields = $change->getFields(); //@todo: Fixme: add getFields() to the interface, or provide getters!
		list( $entityType, $changeType ) = explode( '~', $change->getType() ); //@todo: ugh! provide getters!

		/* @var Entity $entity */
		$entity = $fields['info']['entity'];

		$fields['entity_type'] = $entityType;
		unset( $fields['info'] ); //@todo: may want to preserve some stuff from the info field.

		$params = array(
			'wikibase-repo-change' => $fields
		);

		//@todo: FIXME: check CentralAuth, point to local user. Or use dummy user?
		//@todo: XXX: what if the local user is unknown? How to point to a remote user?
		$username = "REPO USER " . $fields['user_id'];
		$user = \User::newFromName( $username );

		$ip = isset( $fields['ip'] ) ? $fields['ip'] : null; //@todo: provide this!
		$comment = "DATA CHANGE: $changeType of $entityType " . $entity->getId(); //@todo: i18n! //@todo: more info! Link to diff?

		$label = $entity->getLabel( $wgContLang->getCode() );

		if ( $label !== false ) {
			$comment .= ' "' . $label . '"'; //@todo: i18n!
		}

		if ( isset( $fields['comment'] ) && $fields['comment'] !== '' ) { //@todo: provide comment!
			$comment .= " ({$fields['comment']})"; //@todo: i18n!
		}

		$rc = \RecentChange::newLogEntry( $fields['time'], $title, $user, '', $ip, null, '', $title, $comment, serialize( $params ) );

		$attribs = $rc->getAttributes();

		$attribs['rc_type'] = RC_EDIT; //@todo: define RC_EXTERNAL in core
		$attribs['rc_minor'] = ( isset( $fields['minor'] ) && $fields['minor'] ) ? 1 : 0; //@todo: provide this!
		$attribs['rc_bot'] = ( isset( $fields['bot'] ) && $fields['bot'] ) ? 1 : 0; //@todo: provide this!

		$attribs['rc_old_len'] = $title->getLength();
		$attribs['rc_new_len'] = $title->getLength();

		$attribs['rc_this_oldid'] = $title->getLatestRevID();
		$attribs['rc_last_oldid'] = $title->getLatestRevID();

		$rc->setAttribs( $attribs );
		$rc->save(); //@todo: avoid reporting the same change multiple times when re-playing repo changes! how?!
	}

	/**
	 * Hook runs after internal parsing
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ParserAfterParse
	 *
	 * @since 0.1
	 *
	 * @param \Parser $parser
	 * @param string $text
	 * @param \StripState $stripState
	 *
	 * @return boolean
	 */
	public static function onParserAfterParse( \Parser &$parser, &$text, \StripState $stripState ) {
		global $wgLanguageCode;

		$parserOutput = $parser->getOutput();

		// only run this once, for the article content and not interface stuff
		if ( ! $parser->getOptions()->getInterfaceMessage() ) {
			$useRepoLinks = LangLinkHandler::useRepoLinks( $parser );

			if ( $useRepoLinks ) {

				$repoLinkItems = array();

				$repoLinks = LangLinkHandler::getEntityCacheLinks( $parser );

				if ( count( $repoLinks ) > 0 ) {
					LangLinkHandler::suppressRepoLinks( $parser, $repoLinks );

					/**
					 * @var SiteLink $link
					 */
					foreach ( $repoLinks as $link ) {
						foreach ( $link->getSite()->getNavigationIds() as $navigationId ) {
							if ( $navigationId !== $wgLanguageCode ) {
								$repoLinkItems[] = $navigationId . ':' . $link->getPage();
							}
						}
					}
				}

				// get interwiki lang links from local wikitext
				$localLinks = $parserOutput->getLanguageLinks();

				// clear links from parser output and then we repopulate them
				$parserOutput->setLanguageLinks( array() );

				// merge the local and repo language links and remove duplicates
				$langLinks = array_unique( array_merge( $repoLinkItems, $localLinks ) );

				// add language links to the sidebar
				foreach( $langLinks as $langLink ) {
					$parserOutput->addLanguageLink( $langLink );
				}
			}

			if ( $useRepoLinks || Settings::get( 'alwaysSort' ) ) {
				// sort links
				SortUtils::sortLinks( $parserOutput->getLanguageLinks() );
			}
		}

		return true;
	}

	/**
	 * Adds default settings.
	 * Setting name (string) => setting value (mixed)
	 *
	 * @param array &$settings
	 *
	 * @since 0.1
	 *
	 * @return boolean
	 */
	public static function onWikibaseDefaultSettings( array &$settings ) {
		$settings = array_merge(
			$settings,
			array(
				'namespaces' => array( NS_MAIN ),
				'source' => array( 'dir' => __DIR__ . '/tests' ),
				// temporary hack to provide default settings
				'repoBase' => 'https://wikidata-test-repo.wikimedia.de/wiki/',
				'repoApi' => 'https://wikidata-test-repo.wikimedia.de/w/api.php',
				'sort' => 'none',
				'sortPrepend' => false,
				'alwaysSort' => false,
				'siteGlobalID' => 'enwiki',
				'siteGroup' => 'wikipedia',
				'defaultClientStore' => 'sqlstore',
			)
		);

		return true;
	}

	/**
	 * Adds css for the edit links sidebar link
	 *
	 * @param \OutputPage $out
	 * @param \Skin $skin
	 *
	 * @since 0.1
	 *
	 * @return boolean
	 */
	public static function onBeforePageDisplay( \OutputPage $out, \Skin $skin ) {
		// FIXME: we do NOT want to add these resources on every page where the parser is used (ie pretty much all pages)
		$out->addModules( 'ext.wikibaseclient' );
		return true;
	}

	/**
	 * Displays a list of links to pages on the central wiki at the end of the language box.
	 *
	 * @param \Skin $skin
	 * @param \QuickTemplate $template
	 *
	 * @since 0.1
	 *
	 * @return boolean
	 */
	public static function onSkinTemplateOutputPageBeforeExec( \Skin &$skin, \QuickTemplate &$template ) {
		global $wgLanguageCode;

		$editUrl = Settings::get( 'repoBase' );
		if( !$editUrl ) {
			return true;
		}

		$title = $skin->getContext()->getTitle();

		// gets the main part of the title, no underscores used in this db table
		$titleText = $title->getText();

		// main part of title for building link
		$titleLink = $title->getPartialURL();
		$siteId = Settings::get( 'siteGlobalID' );

		$itemId = ClientStoreFactory::getStore()->newSiteLinkCache()->getItemIdForLink( $siteId, $titleText );

		if ( $itemId ) {
			// links to the special page
			$template->data['language_urls'][] = array(
				'href' => rtrim( $editUrl, "/" ) . "/Special:ItemByTitle/$siteId/$titleLink",
				'text' => wfMessage( 'wbc-editlinks' )->text(),
				'title' => wfMessage( 'wbc-editlinkstitle' )->text(),
				'class' => 'wbc-editpage',
			);
		}

		return true;
	}

}
