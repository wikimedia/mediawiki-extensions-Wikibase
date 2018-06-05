<?php

// phpcs:disable Generic.Files.OneObjectStructurePerFile.MultipleFound
// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses
// phpcs:disable PSR2.Namespaces.UseDeclaration.UseAfterNamespace
// phpcs:disable MediaWiki.Files.ClassMatchesFilename.NotMatch

namespace CirrusSearch\Query {

	if ( !interface_exists( FullTextQueryBuilder::class ) ) {
		// phpcs:disable Wikibase.Commenting.ClassLevelDocumentation.Missing
		interface FullTextQueryBuilder {
		}
	}
}

namespace Wikibase\Repo\Search\Elastic\Tests {

	use CirrusSearch\Profile\SearchProfileService;
	use CirrusSearch\Query\FullTextQueryBuilder;
	use CirrusSearch\Search\SearchContext;
	use CirrusSearch\SearchConfig;
	use MediaWikiTestCase;
	use Wikibase\Lib\Store\EntityNamespaceLookup;
	use Wikibase\Repo\Search\Elastic\DispatchingQueryBuilder;

	/**
	 * @covers \Wikibase\Repo\Search\Elastic\DispatchingQueryBuilder
	 *
	 * @group Wikibase
	 * @license GPL-2.0+
	 * @author  Stas Malyshev
	 */
	class DispatchingQueryBuilderTest extends MediaWikiTestCase {
		/**
		 * @var array
		 */
		public static $buildCalled = [];

		public function setUp() {
			parent::setUp();
			if ( !class_exists( 'CirrusSearch' ) ) {
				$this->markTestSkipped( 'CirrusSearch not installed, skipping' );
			}
			self::$buildCalled = [];
		}

		static private $NS_MAP = [
			0 => 'test-item',
			1 => 'test-property',
		];

		static private $PROFILES = [
			'profile1' => [
				'builder_class' => MockQueryBuilder1::class,
				'settings' => []
			],
			'profile2' => [
				'builder_class' => MockQueryBuilder2::class,
				'settings' => []
			],
			'profile3' => [
				'builder_class' => \stdClass::class,
				'settings' => []
			],
		];

		/**
		 * @return SearchConfig
		 */
		private function getMockConfig() {
			$config = $this->getMockBuilder( SearchConfig::class )
				->disableOriginalConstructor()
				->getMock();

			$serviceMock = $this->getMockBuilder( SearchProfileService::class )
				->disableOriginalConstructor()
				->getMock();
			$serviceMock->method( "loadProfile" )->willReturnCallback( function ( $type, $value ) {
				$this->assertEquals( SearchProfileService::FT_QUERY_BUILDER, $type );
				return self::$PROFILES[$value] ?? null;
			} );

			$config->method( "getProfileService" )->willReturn( $serviceMock );

			return $config;
		}

		/**
		 * @return EntityNamespaceLookup
		 */
		private function getMockEntityNamespaceLookup() {
			$mockLookup = $this->getMockBuilder( EntityNamespaceLookup::class )
				->disableOriginalConstructor()
				->getMock();
			$mockLookup->method( 'isEntityNamespace' )->willReturnCallback( function ( $ns ) {
				return $ns < 10;
			} );

			$mockLookup->method( 'getEntityType' )->willReturnCallback( function ( $ns ) {
				return self::$NS_MAP[$ns] ?? null;
			} );

			return $mockLookup;
		}

		public function provideBuilderData() {
			return [
				"no entity defs" => [
					[],
					[ 0, 1 ],
					[]
				],
				"one entity def" => [
					[
						'test-item' => 'profile1',
					],
					[ 0 ],
					[ MockQueryBuilder1::class ],
				],
				"one entity def 2" => [
					[
						'test-item' => 'profile2',
					],
					[ 0 ],
					[ MockQueryBuilder2::class ],
				],
				"two defs, same handler" => [
					[
						'test-item' => 'profile1',
						'test-property' => 'profile1',
					],
					[ 0, 1 ],
					[ MockQueryBuilder1::class ],
				],
				"bad def" => [
					[
						'test-item' => 'profile3',
					],
					[ 0 ],
					[],
					[
						[ 'wikibase-search-config-badclass', 'stdClass' ]
					]
				],
				"bad profile" => [
					[
						'test-item' => 'profile4',
					],
					[ 0 ],
					[],
					[
						[ 'wikibase-search-config-notfound', 'profile4' ]
					]
				],
				"mixed handlers" => [
					[
						'test-item' => 'profile1',
						'test-property' => 'profile2',
					],
					[ 0, 1 ],
					[],
				],
				"mixed with non-entity" => [
					[
						'test-item' => 'profile1',
					],
					[ 0, 11 ],
					[ MockQueryBuilder1::class ],
					[
						[ 'wikibase-search-namespace-mix' ]
					]
				],
				"two defs with non-entity" => [
					[
						'test-item' => 'profile1',
						'test-property' => 'profile2',
					],
					[ 0, 1, 11 ],
					[],
				],
				"mixed with non-entity 2" => [
					[
						'test-item' => 'profile1',
					],
					[ 0, 1 ],
					[ MockQueryBuilder1::class ],
					[
						[ 'wikibase-search-namespace-mix' ]
					]
				],
				"null namespaces" => [
					[
						'test-item' => 'profile1',
					],
					null,
					[],
				],

			];
		}

		/**
		 * @dataProvider provideBuilderData
		 * @param array $defs
		 * @param int[] $namespaces
		 * @param string[] $called
		 * @param string[] $warnings
		 */
		public function testDispatchBuilder( $defs, $namespaces, $called, $warnings = [] ) {
			$builder = new DispatchingQueryBuilder( $defs, $this->getMockEntityNamespaceLookup() );

			$context = new SearchContext( $this->getMockConfig() );
			$context->setNamespaces( $namespaces );
			$builder->build( $context, "test" );

			$this->assertEquals( $called, self::$buildCalled, "Callers do not match" );
			$this->assertEquals( $warnings, $context->getWarnings(), "Warnings do not match" );
		}

	}

	/**
	 * Mock builder class
	 */
	class MockQueryBuilder1 implements FullTextQueryBuilder {

		public function build( SearchContext $searchContext, $term ) {
			DispatchingQueryBuilderTest::$buildCalled[] = get_class( $this );
		}

		public static function newFromGlobals( $settings ) {
			return new static();
		}

		public function buildDegraded( SearchContext $searchContext ) {
			return false;
		}

	}

	/**
	 * Mock builder class
	 */
	class MockQueryBuilder2 extends MockQueryBuilder1 {
	}

}
