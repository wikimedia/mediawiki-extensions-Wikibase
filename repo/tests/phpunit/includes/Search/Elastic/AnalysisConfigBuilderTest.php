<?php
namespace Wikibase\Repo\Search\Elastic\Tests;

use CirrusSearch\HashSearchConfig;
use CirrusSearch\Maintenance\AnalysisConfigBuilder;
use MediaWikiTestCase;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Repo\Search\Elastic\ConfigBuilder;
use Wikibase\Repo\WikibaseRepo;

/**
 * @group Wikibase
 * @covers ConfigBuilder
 * FIXME: this test depends on internal configs of CirrusSearch\Maintenance\AnalysisConfigBuilder
 * This is not an ideal situation, but I am not sure how to fix it without making it more hacky.
 */
class AnalysisConfigBuilderTest extends MediaWikiTestCase {

	public function configDataProvider() {
		$emptyConfig = [
			'analyzer' => [],
			'filter' => [],
			'char_filter' => []
		];
		$allPlugins = [
			'extra',
			'analysis-icu',
			'analysis-stempel',
			'analysis-kuromoji',
			'analysis-smartcn',
			'analysis-hebrew',
			'analysis-ukrainian',
			'analysis-stconvert'
		];

		return [
			"some languages" => [
				[ 'en', 'ru', 'es', 'de', 'zh' ],
				$emptyConfig,
				$allPlugins,
				'en-ru-es-de-zh',
			],
			// sv has custom icu_folding filter
			"sv" => [
				[ 'en', 'zh', 'sv' ],
				$emptyConfig,
				$allPlugins,
				'sv',
			],
			"with plugins" => [
				[ 'he', 'uk' ],
				$emptyConfig,
				$allPlugins,
				'he-uk',
			],
			"without language plugins" => [
				[ 'he', 'uk' ],
				$emptyConfig,
				[ 'extra', 'analysis-icu' ],
				'he-uk-nolang',
			],
			"without any plugins" => [
				[ 'he', 'uk' ],
				$emptyConfig,
				[],
				'he-uk-noplug',
			],
		];
	}

	/**
	 * @param string[] $languages
	 * @param array $oldConfig
	 * @param string[] $plugins
	 * @param string $expectedConfig Filename with expected config
	 * @dataProvider configDataProvider
	 */
	public function testAnalysisConfig( $languages, $oldConfig, $plugins, $expectedConfig ) {
		// We use these static settings because we rely on tests in main
		// AnalysisConfigBuilderTest to handle variations
		$config = new HashSearchConfig( [ 'CirrusSearchUseIcuFolding' => 'yes' ] );

		$languageMock = $this->getMock( ContentLanguages::class );
		$languageMock->method( 'getLanguages' )->willReturn( $languages );

		$repo = $this->getMockBuilder( WikibaseRepo::class )->disableOriginalConstructor()->getMock();
		$repo->method( 'getTermsLanguages' )->willReturn( $languageMock );

		$upstreamBuilder = new AnalysisConfigBuilder( 'dummy', $plugins, $config );
		$builder = new ConfigBuilder( $repo, $upstreamBuilder );

		$builder->buildConfig( $oldConfig );

		$expectedFile = __DIR__ . "/../../../data/analyzer/$expectedConfig.expected";
		if ( is_file( $expectedFile ) ) {
			$expected = json_decode( file_get_contents( $expectedFile ), true );
			$this->assertEquals( $expected, $oldConfig );
		} else {
			file_put_contents( $expectedFile, json_encode( $oldConfig, JSON_PRETTY_PRINT ) );
			$this->markTestSkipped( "Generated new fixture" );
		}
	}

}
