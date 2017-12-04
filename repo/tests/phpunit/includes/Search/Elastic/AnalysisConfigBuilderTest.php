<?php
namespace Wikibase\Repo\Search\Elastic\Tests;

use CirrusSearch\Maintenance\AnalysisConfigBuilder;
use MediaWikiTestCase;
use Wikibase\Repo\Search\Elastic\ConfigBuilder;

/**
 * @group Wikibase
 * @covers \Wikibase\Repo\Search\Elastic\ConfigBuilder
 */
class AnalysisConfigBuilderTest extends MediaWikiTestCase {

	public function testAnalysisConfig() {
		if ( !class_exists( CirrusSearch::class ) ) {
			$this->markTestSkipped( 'CirrusSearch needed.' );
		}

		$langSettings = [];
		$langSettings['useStemming'] = [
			'en' => [ 'index' => true, 'query' => true ],
			'ru' => [ 'index' => true, 'query' => false ],
			'uk' => [ 'index' => false, 'query' => true ],
			'he' => [ 'index' => false, 'query' => false ],
		];
		$upstreamBuilder =
			$this->getMockBuilder( AnalysisConfigBuilder::class )
				->disableOriginalConstructor()
				->getMock();
		$upstreamBuilder->expects( $this->exactly( 2 ) )
			->method( 'buildLanguageConfigs' )
			->withConsecutive(
				[
					$this->equalTo( [] ),
					$this->equalTo( [ 'en', 'ru' ], 0, 1, true ),
					$this->equalTo( [ 'plain', 'plain_search', 'text', 'text_search' ] )
				], [
					$this->equalTo( [] ),
					$this->equalTo( [ 'uk', 'he', 'zh' ], 0, 1, true ),
					$this->equalTo( [ 'plain', 'plain_search' ] )
				] )
			->willReturn( [] );

		$oldConfig = [];
		$builder = new ConfigBuilder( [ 'en', 'ru', 'uk', 'he', 'zh' ], $langSettings, $upstreamBuilder );

		$builder->buildConfig( $oldConfig );
	}

}
