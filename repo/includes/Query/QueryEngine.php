<?php

namespace Wikibase\Repo\Query;
use Ask\Language\Query;

interface QueryEngine {

	/**
	 * @since wd.qe
	 *
	 * @param Query $query
	 *
	 * @return QueryResult
	 */
	public function runQuery( Query $query );

}