<?php
namespace Wikibase\Lib\Store\Sql\Terms\Util;

use MediaWiki\MediaWikiServices;
use Wikibase\Lib\WikibaseSettings;

/**
 * Trait for adding stats metrics on queries from repo/client
 *
 * @license GPL-2.0-or-later
 */
trait StatsMonitoring {
	public function incrementForQuery( string $queryType ): void {
		MediaWikiServices::getInstance()->getStatsFactory()
			->getCounter( 'wikibase_repo_term_store_total' )
			->setLabel( 'query_type', $queryType )
			->copyToStatsdAt( "wikibase.repo.term_store.$queryType" )
			->increment();
		if ( WikibaseSettings::isRepoEnabled() ) {
			$queryContext = 'repo';
		} else {
			$queryContext = 'client';
		}
		MediaWikiServices::getInstance()->getStatsFactory()
			->getCounter( 'wikibase_query_contexts_term_store_total' )
			->setLabel( 'query_context', $queryContext )
			->setLabel( 'query_type', $queryType )
			->copyToStatsdAt( "wikibase.query_contexts.$queryContext.term_store.$queryType" )
			->increment();
	}
}
