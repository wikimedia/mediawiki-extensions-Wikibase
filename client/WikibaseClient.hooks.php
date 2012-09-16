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
			'includes/EntityCache',
			'includes/EntityCacheUpdater',
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
		list( $mainType, ) = explode( '~', $change->getType() );

		if ( array_key_exists( $mainType, EntityObject::$typeMap ) ) {

			$cacheUpdater = new EntityCacheUpdater();
			$cacheUpdater->handleChange( $change );

			// The following code is a temporary hack to invalidate the cache.
			// TODO: create cache invalidater that works with all clients for this cluster
			if ( $mainType == Item::ENTITY_TYPE ) {
				/**
				 * @var Item $item
				 */
				$item = $change->getEntity();
				$siteGlobalId = Settings::get( 'siteGlobalID' );
				$siteLink = $item->getSiteLink( $siteGlobalId );
				$title = null;

				if ( $siteLink !== null ) {
					// check whether connecting sitelink has changed
					// if so, purge both pages: the new and the old one and return
					$siteLinkChangeOperations = $change->getDiff()->getSiteLinkDiff()->getTypeOperations( 'change' );
					if ( is_array( $siteLinkChangeOperations ) && array_key_exists( $siteGlobalId, $siteLinkChangeOperations ) ) {
						$oldTitle = \Title::newFromText( $siteLinkChangeOperations[ $siteGlobalId ]->getOldValue() );
						$newTitle = \Title::newFromText( $siteLinkChangeOperations[ $siteGlobalId ]->getNewValue() );
						if ( !is_null( $oldTitle ) && $oldTitle->getArticleID() !== 0 ) {
							$oldTitle->invalidateCache();
						}
						if ( !is_null( $newTitle ) && $newTitle->getArticleID() !== 0 ) {
							$newTitle->invalidateCache();
						}
						return true;
					}
					$title = \Title::newFromText( $siteLink->getPage() );
				} else {
					// cache should be invalidated when the sitelink got removed
					$removedSiteLinks = $change->getDiff()->getSiteLinkDiff()->getRemovedValues();
					if ( is_array( $removedSiteLinks ) && array_key_exists( $siteGlobalId, $removedSiteLinks ) ) {
						$title = \Title::newFromText( $removedSiteLinks[ $siteGlobalId ] );
					}
				}

				if ( !is_null( $title ) && $title->getArticleID() !== 0 ) {
					$title->invalidateCache();
				}
			}
		}

		return true;
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
				'editURL' => '',
				// temporary hack to provide default settings
				'repoBase' => 'http://wikidata-test-repo.wikimedia.de/wiki/',
				'repoApi' => 'http://wikidata-test-repo.wikimedia.de/w/api.php',
				'sort' => 'none',
				'sortPrepend' => false,
				'alwaysSort' => false,
				'siteGlobalID' => 'enwiki',
				'siteGroup' => 'wikipedia'
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
		if( ! $editUrl ) {
			return true;
		}

		$title = $skin->getContext()->getTitle();

		// gets the Main part of the title, no underscores used in this db table
		$titleText = $title->getText();
		$siteId = Settings::get( 'siteGlobalID' );

		// TODO: create and use client store
		$itemId = SiteLinkCache::singleton()->getItemIdForLink( $siteId, $titleText );

		if ( $itemId ) {
			// links to the special page
			// TODO: know what the repo namespace is
			$template->data['language_urls'][] = array(
				'href' => rtrim( $editUrl, "/" ) . "/Data:Q" . $itemId . '?uselang=' . $wgLanguageCode,
				'text' => wfMessage( 'wbc-editlinks' )->text(),
				'title' => wfMessage( 'wbc-editlinkstitle' )->text(),
				'class' => 'wbc-editpage',
			);
		}

		return true;
	}

}
