<?php

namespace Wikibase\Client;

use MediaWiki\MediaWikiServices;
use MediaWiki\Skin\Skin;
use MediaWiki\Title\Title;
use MediaWiki\User\User;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Lib\StaticContentLanguages;
use Wikibase\Lib\UnionContentLanguages;
use Wikibase\Lib\WikibaseContentLanguages;

/**
 * File defining the hook handlers for the Wikibase Client extension.
 *
 * @license GPL-2.0-or-later
 */
final class ClientHooks {

	/**
	 * @see NamespaceChecker::isWikibaseEnabled
	 *
	 * @param int $namespace
	 *
	 * @return bool
	 */
	protected static function isWikibaseEnabled( $namespace ) {
		return WikibaseClient::getNamespaceChecker()->isWikibaseEnabled( $namespace );
	}

	/**
	 * Build 'Wikidata item' link for later addition to the toolbox section of the sidebar
	 *
	 * @param Skin $skin
	 *
	 * @return string[]|null Array of link elements or Null if link cannot be created.
	 */
	public static function buildWikidataItemLink( Skin $skin ): ?array {
		$title = $skin->getTitle();
		$idString = $skin->getOutput()->getProperty( 'wikibase_item' );
		$entityId = null;

		if ( $idString !== null ) {
			$entityIdParser = WikibaseClient::getEntityIdParser();
			$entityId = $entityIdParser->parse( $idString );
		} elseif ( $title &&
			$skin->getActionName() !== 'view' && $title->exists()
		) {
			// Try to load the item ID from Database, but only do so on non-article views,
			// (where the article's OutputPage isn't available to us).
			$entityId = self::getEntityIdForTitle( $title );
		}

		if ( $entityId !== null ) {
			$repoLinker = WikibaseClient::getRepoLinker();

			return [
				// Warning: This id is misleading; the 't' refers to the link's original place in the toolbox,
				// it now lives in the other projects section, but we must keep the 't' for compatibility with gadgets.
				'id' => 't-wikibase',
				'icon' => 'logoWikidata',
				'text' => $skin->msg( 'wikibase-dataitem' )->text(),
				'href' => $repoLinker->getEntityUrl( $entityId ),
			];
		}

		return null;
	}

	/**
	 * @param Title $title
	 * @return EntityId|null
	 */
	private static function getEntityIdForTitle( Title $title ): ?EntityId {
		if ( !self::isWikibaseEnabled( $title->getNamespace() ) ) {
			return null;
		}

		$entityIdLookup = WikibaseClient::getEntityIdLookup();
		return $entityIdLookup->getEntityIdForTitle( $title );
	}

	/**
	 * Adds a preference for showing or hiding Wikidata entries in recent changes
	 *
	 * @param User $user
	 * @param array[] &$prefs
	 */
	public static function onGetPreferences( User $user, array &$prefs ) {
		$settings = WikibaseClient::getSettings();

		if ( !$settings->getSetting( 'showExternalRecentChanges' ) ) {
			return;
		}

		$prefs['rcshowwikidata'] = [
			'type' => 'toggle',
			'label-message' => 'wikibase-rc-show-wikidata-pref',
			'section' => 'rc/advancedrc',
		];

		$prefs['wlshowwikibase'] = [
			'type' => 'toggle',
			'label-message' => 'wikibase-watchlist-show-changes-pref',
			'section' => 'watchlist/advancedwatchlist',
		];
	}

	/**
	 * Used to propagate configuration for the linkitem feature to JavaScript.
	 * This is used in the "wikibase.client.linkitem.init" module.
	 */
	public static function getLinkitemConfiguration(): array {
		$cache = MediaWikiServices::getInstance()->getLocalServerObjectCache();
		$key = $cache->makeKey(
			'wikibase-client',
			'siteConfiguration'
		);
		return $cache->getWithSetCallback(
			$key,
			$cache::TTL_DAY, // when changing the TTL, also update linkItemTags in options.md
			function () {
				$site = WikibaseClient::getSite();
				$currentSite = [
					'globalSiteId' => $site->getGlobalId(),
					'languageCode' => $site->getLanguageCode(),
					'langLinkSiteGroup' => WikibaseClient::getLangLinkSiteGroup(),
				];
				$value = [ 'currentSite' => $currentSite ];

				$tags = WikibaseClient::getSettings()->getSetting( 'linkItemTags' );
				if ( $tags !== [] ) {
					$value['tags'] = $tags;
				}

				return $value;
			}
		);
	}

	/** @param ContentLanguages[] &$contentLanguages */
	public static function onWikibaseContentLanguages( array &$contentLanguages ): void {
		if ( !WikibaseClient::getSettings()->getSetting( 'enableMulLanguageCode' ) ) {
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

}
