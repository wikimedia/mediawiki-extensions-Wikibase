<?php

namespace Wikibase\Client\Tests\Usage;

use MediaWikiTestCase;
use Wikibase\Client\Usage\UsageTrackingLanguageFallbackLabelDescriptionLookup;
use Wikibase\Client\Usage\HashUsageAccumulator;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\TermFallback;
use Wikibase\LanguageFallbackChain;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookup;

/**
 * @covers Wikibase\Client\Usage\UsageTrackingLanguageFallbackLabelDescriptionLookup
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseUsageTracking
 *
 * @license GPL-2.0+
 * @author Marius Hoch
 */
class UsageTrackingLanguageFallbackLabelDescriptionLookupTest extends MediaWikiTestCase {

	public function provideGetLabel() {
		return [
			'No term found -> all languages tracked' => [
				[ 'Q2#L.a', 'Q2#L.b', 'Q2#L.c' ],
				[ 'a', 'b', 'c' ],
				null
			],
			'Only language in chain used' => [
				[ 'Q2#L.en' ],
				[ 'en' ],
				new TermFallback( 'en', 'blah blah blah', 'en', null )
			],
			'One language in chain used' => [
				[ 'Q2#L.de', 'Q2#L.es' ],
				[ 'de', 'es', 'en' ],
				new TermFallback( 'de', 'blah blah blah', 'es', null )
			],
			'Last language in chain used' => [
				[ 'Q2#L.de', 'Q2#L.es', 'Q2#L.ru' ],
				[ 'de', 'es', 'ru' ],
				new TermFallback( 'de', 'blah blah blah', 'ru', null )
			],
			'Transliteration' => [
				[ 'Q2#L.foo', 'Q2#L.ku', 'Q2#L.ku-arab', 'Q2#L.ku-latn' ],
				[ 'foo', 'ku', 'ku-arab', 'ku-latn', 'en' ],
				new TermFallback( 'ku', 'blah blah blah', 'ku', 'ku-latn' )
			]
		];
	}

	/**
	 * @dataProvider provideGetLabel
	 */
	public function testGetLabel( array $expectedUsages, array $fetchLanguageCodes, TermFallback $term = null ) {
		$q2 = new ItemId( 'Q2' );

		$usageAccumulator = new HashUsageAccumulator();

		$languageFallbackChain = $this->getMockBuilder( LanguageFallbackChain::class )
			->disableOriginalConstructor()
			->getMock();
		$languageFallbackChain->expects( $this->once() )
			->method( 'getFetchLanguageCodes' )
			->will( $this->returnValue( $fetchLanguageCodes ) );

		$usageTrackingLanguageFallbackLabelDescriptionLookup = new UsageTrackingLanguageFallbackLabelDescriptionLookup(
			$this->getLanguageFallbackLabelDescriptionLookup( 'getLabel', $q2, $term ),
			$usageAccumulator,
			$languageFallbackChain
		);

		$this->assertSame( $term, $usageTrackingLanguageFallbackLabelDescriptionLookup->getLabel( $q2 ) );
		$this->assertSame( $expectedUsages, array_keys( $usageAccumulator->getUsages() ) );
	}

	public function testGetDescription() {
		$q2 = new ItemId( 'Q2' );
		$description = new TermFallback( 'de', 'blah', 'df', 'sd' );

		$usageAccumulator = new HashUsageAccumulator();

		$usageTrackingLanguageFallbackLabelDescriptionLookup = new UsageTrackingLanguageFallbackLabelDescriptionLookup(
			$this->getLanguageFallbackLabelDescriptionLookup( 'getDescription', $q2, $description ),
			$usageAccumulator,
			new LanguageFallbackChain( [] )
		);

		$this->assertSame( $description, $usageTrackingLanguageFallbackLabelDescriptionLookup->getDescription( $q2 ) );
		$this->assertSame( [], $usageAccumulator->getUsages() );
	}

	/**
	 * @param string $method
	 * @param ItemId $item
	 * @param TermFallback|null $value
	 *
	 * @return LanguageFallbackLabelDescriptionLookup
	 */
	private function getLanguageFallbackLabelDescriptionLookup( $method, ItemId $item, TermFallback $value = null ) {
		$languageFallbackLabelDescriptionLookup = $this->getMockBuilder( LanguageFallbackLabelDescriptionLookup::class )
			->disableOriginalConstructor()
			->getMock();

		$languageFallbackLabelDescriptionLookup->expects( $this->once() )
			->method( $method )
			->with( $item )
			->will( $this->returnValue( $value ) );

		return $languageFallbackLabelDescriptionLookup;
	}

}
