<?php
namespace Wikibase\Lib\Store\Sql\Terms\Util;

use MediaWiki\MediaWikiServices;
use Wikibase\Lib\WikibaseSettings;

/**
 * Trait for adding statsd metrics on queries from repo/client
 *
 * @license GPL-2.0-or-later
 */
trait StatsdMonitoring {
	public function incrementForQuery( string $queryType ): void {
		MediaWikiServices::getInstance()->getStatsdDataFactory()->increment(
			'wikibase.repo.term_store.' . $queryType
		);
		if ( WikibaseSettings::isRepoEnabled() ) {
			$queryContext = 'repo';
		} else {
			$queryContext = 'client';
		}
		MediaWikiServices::getInstance()->getStatsdDataFactory()->increment(
			"wikibase.query_contexts.$queryContext.term_store.$queryType"
		);
	}
}
