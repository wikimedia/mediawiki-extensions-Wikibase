<?php

namespace Wikibase\Repo\Tests\Search\Elastic\Fields;

use CirrusSearch;
use PHPUnit_Framework_TestCase;
use SearchEngine;

/**
 * Helper test class for search field testing.
 */
class SearchFieldTestCase extends PHPUnit_Framework_TestCase {

	/**
	 * Prepare search engine mock suitable for testing search fields.
	 * @return SearchEngine
	 */
	protected function getSearchEngineMock() {
		if ( class_exists( CirrusSearch::class ) ) {
			$searchEngine = $this->getMockBuilder( CirrusSearch::class )->getMock();
			$searchEngine->method( 'getConfig' )->willReturn( new CirrusSearch\SearchConfig() );
		} else {
			$searchEngine = $this->getMockBuilder( SearchEngine::class )->getMock();
		}
		return $searchEngine;
	}

}
