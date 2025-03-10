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
		if ( WikibaseSettings::isRepoEnabled() ) {
			$queryContext = 'repo';
		} else {
			$queryContext = 'client';
		}

		MediaWikiServices::getInstance()->getStatsFactory()
			->withComponent( 'WikibaseLib' )
			->getCounter( 'termStore_total' )
			->setLabels( [ 'query_type' => $queryType, 'query_context' => $queryContext ] )
			->copyToStatsdAt( "wikibase.repo.term_store.$queryType" )
			->increment();

		MediaWikiServices::getInstance()->getStatsFactory()
			->withComponent( 'WikibaseLib' )
			->getCounter( 'termStore_queryContexts_total' )
			->setLabels( [ 'query_type' => $queryType, 'query_context' => $queryContext ] )
			->copyToStatsdAt( "wikibase.query_contexts.$queryContext.term_store.$queryType" )
			->increment();
	}
}
