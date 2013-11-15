<?php

/**
 * Class registration file for the WikibaseClient component.
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
return call_user_func( function() {

	$classes = array(
		// Autoloading
		'Wikibase\ClientHooks' => 'WikibaseClient.hooks.php',

		'Wikibase\CachedEntity' => 'includes/CachedEntity.php',
		'Wikibase\EntityCacheUpdater' => 'includes/EntityCacheUpdater.php',
		'Wikibase\EntityIdPropertyUpdater' => 'includes/EntityIdPropertyUpdater.php',
		'Wikibase\InterwikiSorter' => 'includes/InterwikiSorter.php',
		'Wikibase\LangLinkHandler' => 'includes/LangLinkHandler.php',
		'Wikibase\ChangeHandler' => 'includes/ChangeHandler.php',
		'Wikibase\NamespaceChecker' => 'includes/NamespaceChecker.php',
		'Wikibase\RepoItemLinkGenerator' => 'includes/RepoItemLinkGenerator.php',
		'Wikibase\RepoLinker' => 'includes/RepoLinker.php',
		'Wikibase\Client\WikibaseClient' => 'includes/WikibaseClient.php',
		'Wikibase\PageUpdater' => 'includes/PageUpdater.php',
		'Wikibase\SiteLinkCommentCreator' => 'includes/SiteLinkCommentCreator.php',
		'Wikibase\WikiPageUpdater' => 'includes/WikiPageUpdater.php',
		'Wikibase\UpdateRepo' => 'includes/UpdateRepo.php',
		'Wikibase\UpdateRepoOnMove' => 'includes/UpdateRepoOnMove.php',

		// includes/api
		'Wikibase\ApiClientInfo' => 'includes/api/ApiClientInfo.php',

		// includes/hooks
		'Wikibase\Client\MovePageNotice' => 'includes/hooks/MovePageNotice.php',

		// includes/modules
		'Wikibase\SiteModule'  => 'includes/modules/SiteModule.php',

		// include/parserhooks
		'Wikibase\NoLangLinkHandler' => 'includes/parserhooks/NoLangLinkHandler.php',
		'Wikibase\ParserErrorMessageFormatter' => 'includes/parserhooks/ParserErrorMessageFormatter.php',
		'Wikibase\PropertyParserFunction' => 'includes/parserhooks/PropertyParserFunction.php',
		'Wikibase\PropertyParserFunctionRenderer' => 'includes/parserhooks/PropertyParserFunctionRenderer.php',

		// includes/recentchanges
		'Wikibase\ExternalChangesLine' => 'includes/recentchanges/ExternalChangesLine.php',
		'Wikibase\ExternalRecentChange' => 'includes/recentchanges/ExternalRecentChange.php',
		'Wikibase\RecentChangesFilterOptions' => 'includes/recentchanges/RecentChangesFilterOptions.php',

		// includes/specials
		'Wikibase\Client\Specials\SpecialUnconnectedPages' => 'includes/specials/SpecialUnconnectedPages.php',

		// includes/store
		'Wikibase\ClientStore' => 'includes/store/ClientStore.php',
		'Wikibase\EntityCache' => 'includes/store/EntityCache.php',

		// includes/store/sql
		'Wikibase\CachingSqlStore' => 'includes/store/sql/CachingSqlStore.php',
		'Wikibase\DirectSqlStore' => 'includes/store/sql/DirectSqlStore.php',
		'Wikibase\EntityCacheTable' => 'includes/store/sql/EntityCacheTable.php',

		// includes/scribunto
		'Scribunto_LuaWikibaseLibrary' => 'includes/scribunto/Scribunto_LuaWikibaseLibrary.php',
		'Scribunto_LuaWikibaseLibraryImplementation' => 'includes/scribunto/Scribunto_LuaWikibaseLibraryImplementation.php',

		// test
		'Wikibase\Test\MockPageUpdater' => 'tests/phpunit/MockPageUpdater.php'

	);

	return $classes;

} );
