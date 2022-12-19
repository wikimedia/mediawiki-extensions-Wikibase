<?php

namespace Wikibase\Client\Tests\Unit\Usage;

use MediaWikiCoversValidator;
use Wikibase\Client\Usage\HashUsageAccumulator;
use Wikibase\Client\Usage\UsageAccumulator;
use Wikibase\Client\Usage\UsageTrackingLanguageFallbackLabelDescriptionLookup;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\TermFallback;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookup;
use Wikibase\Lib\TermLanguageFallbackChain;

/**
 * @covers \Wikibase\Client\Usage\UsageTrackingLanguageFallbackLabelDescriptionLookup
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseUsageTracking
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch
 */
class UsageTrackingLanguageFallbackLabelDescriptionLookupTest extends \PHPUnit\Framework\TestCase {
	use MediaWikiCoversValidator;

	public function provideGetLabel() {
		return $this->provideGetTermFallback( 'L' );
	}

	public function provideGetDescription() {
		return $this->provideGetTermFallback( 'D' );
	}

	private function provideGetTermFallback( $usagePrefix ) {
		return [
			'No term found -> all languages tracked' => [
				[ "Q2#$usagePrefix.a", "Q2#$usagePrefix.b", "Q2#$usagePrefix.c" ],
				[ 'a', 'b', 'c' ],
				null,
			],
			'allowDataAccessInUserLanguage' => [
				[ "Q2#$usagePrefix" ],
				[ 'a', 'b', 'c' ],
				null,
				true,
			],
			'Only language in chain used' => [
				[ "Q2#$usagePrefix.en" ],
				[ 'en' ],
				new TermFallback( 'en', 'blah blah blah', 'en', null ),
			],
			'One language in chain used' => [
				[ "Q2#$usagePrefix.de", "Q2#$usagePrefix.es" ],
				[ 'de', 'es', 'en' ],
				new TermFallback( 'de', 'blah blah blah', 'es', null ),
			],
			'Last language in chain used' => [
				[ "Q2#$usagePrefix.de", "Q2#$usagePrefix.es", "Q2#$usagePrefix.ru" ],
				[ 'de', 'es', 'ru' ],
				new TermFallback( 'de', 'blah blah blah', 'ru', null ),
			],
			'Transliteration' => [
				[
					"Q2#$usagePrefix.foo",
					"Q2#$usagePrefix.ku",
					"Q2#$usagePrefix.ku-arab",
					"Q2#$usagePrefix.ku-latn",
				],
				[ 'foo', 'ku', 'ku-arab', 'ku-latn', 'en' ],
				new TermFallback( 'ku', 'blah blah blah', 'ku', 'ku-latn' ),
			],
		];
	}

	/**
	 * @dataProvider provideGetLabel
	 */
	public function testGetLabel(
		array $expectedUsages,
		array $fetchLanguageCodes,
		TermFallback $term = null,
		$trackUsagesInAllLanguages = false
	) {
		$q2 = new ItemId( 'Q2' );

		$usageAccumulator = new HashUsageAccumulator();

		$lookup = $this->getUsageTrackingLanguageFallbackLabelDescriptionLookup(
			$usageAccumulator,
			$term,
			'getLabel',
			$fetchLanguageCodes,
			$trackUsagesInAllLanguages
		);

		$this->assertSame( $term, $lookup->getLabel( $q2 ) );
		$this->assertSame( $expectedUsages, array_keys( $usageAccumulator->getUsages() ) );
	}

	/**
	 * @dataProvider provideGetDescription
	 */
	public function testGetDescription(
		array $expectedUsages,
		array $fetchLanguageCodes,
		TermFallback $term = null,
		$trackUsagesInAllLanguages = false
	) {
		$q2 = new ItemId( 'Q2' );

		$usageAccumulator = new HashUsageAccumulator();

		$lookup = $this->getUsageTrackingLanguageFallbackLabelDescriptionLookup(
			$usageAccumulator,
			$term,
			'getDescription',
			$fetchLanguageCodes,
			$trackUsagesInAllLanguages
		);

		$this->assertSame( $term, $lookup->getDescription( $q2 ) );
		$this->assertSame( $expectedUsages, array_keys( $usageAccumulator->getUsages() ) );
	}

	/**
	 * @param UsageAccumulator $usageAccumulator
	 * @param TermFallback|null $term
	 * @param string $method
	 * @param string[] $fetchLanguageCodes
	 * @param bool $trackUsagesInAllLanguages
	 *
	 * @return UsageTrackingLanguageFallbackLabelDescriptionLookup
	 */
	private function getUsageTrackingLanguageFallbackLabelDescriptionLookup(
		UsageAccumulator $usageAccumulator,
		?TermFallback $term,
		$method,
		array $fetchLanguageCodes,
		$trackUsagesInAllLanguages
	) {
		$languageFallbackChain = $this->createMock( TermLanguageFallbackChain::class );
		$languageFallbackChain->expects( $this->exactly( $trackUsagesInAllLanguages ? 0 : 1 ) )
			->method( 'getFetchLanguageCodes' )
			->willReturn( $fetchLanguageCodes );

		$usageTrackingLanguageFallbackLabelDescriptionLookup = new UsageTrackingLanguageFallbackLabelDescriptionLookup(
			$this->getLanguageFallbackLabelDescriptionLookup( $method, new ItemId( 'Q2' ), $term ),
			$usageAccumulator,
			$languageFallbackChain,
			$trackUsagesInAllLanguages
		);

		return $usageTrackingLanguageFallbackLabelDescriptionLookup;
	}

	/**
	 * @param string $method
	 * @param ItemId $item
	 * @param TermFallback|null $value
	 *
	 * @return LanguageFallbackLabelDescriptionLookup
	 */
	private function getLanguageFallbackLabelDescriptionLookup( $method, ItemId $item, TermFallback $value = null ) {
		$languageFallbackLabelDescriptionLookup = $this->createMock( LanguageFallbackLabelDescriptionLookup::class );

		$languageFallbackLabelDescriptionLookup->expects( $this->once() )
			->method( $method )
			->with( $item )
			->willReturn( $value );

		return $languageFallbackLabelDescriptionLookup;
	}

}
