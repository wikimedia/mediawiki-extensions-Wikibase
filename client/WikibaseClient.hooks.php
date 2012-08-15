<?php

namespace Wikibase;

/**
 * File defining the hook handlers for the Wikibase Client extension.
 *
 * @since 0.1
 *
 * @file WikibaseClient.hooks.php
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
//			'General',
//			'Sorting',

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

				$globalId = Settings::get( 'siteGlobalID' );

				$siteLink = $item->getSiteLink( $globalId );

				if ( $siteLink !== null ) {
					$title = Title::newFromText( $siteLink->getPage() );

					if ( !is_null( $title ) && $title->getArticleID() !== 0 ) {
						$title->invalidateCache();
					}
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

		if ( LangLinkHandler::doInterWikiLinks( $parser ) && LangLinkHandler::useRepoLinks( $parser ) ) {
			$repoLinks = LangLinkHandler::getLocalItemLinks( $parser );

			if ( $repoLinks !== array() ) {
				LangLinkHandler::suppressRepoLinks( $parser, $repoLinks );

				foreach ( $repoLinks as $link ) {
					// TODO: know that this site is in the wikipedia group and get links for only this group
					// TODO: hold into account wiki-wide and page-specific settings to do the merge rather then just overriding.
					$localKey = $link->getSite()->getConfig()->getLocalId();

					// unset self referencing interwiki link
					if ( $localKey != $wgLanguageCode ) {
						$parserOutput->addLanguageLink( $localKey . ':' . $link->getPage() );
					}
				}
			}
		}

		// Because, you know, the function might refuse to sort them.
		// And it's all uncertain with this quantum stuff anyway...
		SortUtils::maybeSortLinks( $parserOutput->getLanguageLinks() );

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
				'repoBase' => 'http://wikidata-test-repo.wikimedia.de/wiki/',
				'repoApi' => 'http://wikidata-test-repo.wikimedia.de/w/api.php',
				'sort' => 'none',
				'sortPrepend' => false,
				'alwaysSort' => false,
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

		if( ! $editUrl = Settings::get( 'repoBase' ) ) {
			return true;
		}

		$title = $skin->getContext()->getTitle();

		// This must be the same as in LangLinkHandler
		// NOTE: Instead of getFullText(), we need to get a normalized title, and the server should use a locale-aware normalization function yet to be written which has the same output
		$titleText = $title->getFullText();
		$siteId = Settings::get( 'siteGlobalID' );

		$template->data['language_urls'][] = array(
			'href' => rtrim( $editUrl, "/" ) . "/Special:ItemByTitle/$siteId/$titleText",
			'text' => wfMsg( 'wbc-editlinks' ),
			'title' => wfMsg( 'wbc-editlinkstitle' ),
			'class' => 'wbc-editpage',
		);

		return true;
	}

}
