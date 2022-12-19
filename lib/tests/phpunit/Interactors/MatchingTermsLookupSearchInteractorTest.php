<?php

namespace Wikibase\Lib\Tests\Interactors;

use Wikibase\DataAccess\PrefetchingTermLookup;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermFallback;
use Wikibase\Lib\Interactors\MatchingTermsLookupSearchInteractor;
use Wikibase\Lib\Interactors\TermSearchOptions;
use Wikibase\Lib\Interactors\TermSearchResult;
use Wikibase\Lib\LanguageFallbackChainFactory;
use Wikibase\Lib\TermIndexEntry;
use Wikibase\Lib\TermLanguageFallbackChain;
use Wikibase\Lib\Tests\Store\MockMatchingTermsLookup;

/**
 * @covers \Wikibase\Lib\Interactors\MatchingTermsLookupSearchInteractor
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 */
class MatchingTermsLookupSearchInteractorTest extends \PHPUnit\Framework\TestCase {

	private function getMockTermIndex() {
		return new MockMatchingTermsLookup(
			[
				/**
				 * NOTE: The ordering of the properties is important for the test-case that covers the limit being applied in case of
				 * a language fallback being used. If the order is changed, it might subtly lead to that line not being covered despite all
				 * tests passing!
				 */

				//Q111 - Has label, description and alias all the same
				$this->getTermIndexEntry( 'Foo', 'en', TermIndexEntry::TYPE_LABEL, new ItemId( 'Q111' ) ),
				$this->getTermIndexEntry( 'Foo', 'en', TermIndexEntry::TYPE_DESCRIPTION, new ItemId( 'Q111' ) ),
				$this->getTermIndexEntry( 'Foo', 'en', TermIndexEntry::TYPE_ALIAS, new ItemId( 'Q111' ) ),
				$this->getTermIndexEntry( 'FOO', 'en', TermIndexEntry::TYPE_ALIAS, new ItemId( 'Q111' ) ),
				//Q333
				$this->getTermIndexEntry( 'Food is great', 'en', TermIndexEntry::TYPE_LABEL, new ItemId( 'Q333' ) ),
				//Q555
				$this->getTermIndexEntry( 'Ta', 'en', TermIndexEntry::TYPE_ALIAS, new ItemId( 'Q555' ) ),
				$this->getTermIndexEntry( 'Taa', 'en', TermIndexEntry::TYPE_ALIAS, new ItemId( 'Q555' ) ),
				$this->getTermIndexEntry( 'TAAA', 'en-ca', TermIndexEntry::TYPE_ALIAS, new ItemId( 'Q555' ) ),
				$this->getTermIndexEntry( 'Taa', 'en-ca', TermIndexEntry::TYPE_ALIAS, new ItemId( 'Q555' ) ),
				//P11
				$this->getTermIndexEntry( 'Lahmacun', 'en', TermIndexEntry::TYPE_LABEL, new NumericPropertyId( 'P11' ) ),
				//P22
				$this->getTermIndexEntry( 'Lama', 'en', TermIndexEntry::TYPE_LABEL, new NumericPropertyId( 'P22' ) ),
				$this->getTermIndexEntry( 'La-description', 'en', TermIndexEntry::TYPE_DESCRIPTION, new NumericPropertyId( 'P22' ) ),
				//P44
				$this->getTermIndexEntry( 'Lama', 'en-ca', TermIndexEntry::TYPE_LABEL, new NumericPropertyId( 'P44' ) ),
				$this->getTermIndexEntry( 'Lama-de-desc', 'de', TermIndexEntry::TYPE_DESCRIPTION, new NumericPropertyId( 'P44' ) ),
			]
		);
	}

	/**
	 * @param string $text
	 * @param string $languageCode
	 * @param string $termType
	 * @param EntityId|ItemId|NumericPropertyId $entityId
	 *
	 * @return TermIndexEntry
	 */
	private function getTermIndexEntry( $text, $languageCode, $termType, EntityId $entityId ) {
		return new TermIndexEntry( [
			'termText' => $text,
			'termLanguage' => $languageCode,
			'termType' => $termType,
			'entityId' => $entityId,
		] );
	}

	/**
	 * Get a lookup that always returns a pt label and description suffixed by the entity ID
	 *
	 * @return PrefetchingTermLookup
	 */
	private function getMockPrefetchingTermLookup() {
		$mock = $this->createMock( PrefetchingTermLookup::class );
		$mock->method( 'getLabels' )
			->willReturnCallback( function( EntityId $entityId, $languageCodes ) {
				$labels = [];
				foreach ( $languageCodes as $languageCode ) {
					$labels[$languageCode] = 'label-' . $languageCode . '-' . $entityId->getSerialization();
				}
				return $labels;
			}
			);
		$mock->method( 'getDescriptions' )
			->willReturnCallback( function( EntityId $entityId, $languageCodes ) {
				$descriptions = [];
				foreach ( $languageCodes as $languageCode ) {
					$descriptions[$languageCode] =
						'description-' . $languageCode . '-' . $entityId->getSerialization();
				}
				return $descriptions;
			}
			);
		return $mock;
	}

	private function getExpectedDisplayTerm( EntityId $entityId, $termType ) {
		return new TermFallback( 'pt', $termType . '-pt-' . $entityId->getSerialization(), 'pt', 'pt' );
	}

	/**
	 * @return LanguageFallbackChainFactory
	 */
	private function getMockLanguageFallbackChainFactory() {
		$mockFactory = $this->createMock( LanguageFallbackChainFactory::class );
		$mockFactory->method( 'newFromLanguageCode' )
			->willReturnCallback( function( $langCode ) {
				return $this->getMockLanguageFallbackChainFromLanguage( $langCode );
			} );
		return $mockFactory;
	}

	/**
	 * @param string $langCode
	 *
	 * @return TermLanguageFallbackChain
	 */
	public function getMockLanguageFallbackChainFromLanguage( $langCode ) {
		$mockFallbackChain = $this->createMock( TermLanguageFallbackChain::class );
		$mockFallbackChain->method( 'getFetchLanguageCodes' )
			->willReturnCallback( function () use( $langCode ) {
				if ( $langCode === 'en-gb' || $langCode === 'en-ca' ) {
					return [ $langCode, 'en' ];
				}
				return [ $langCode ]; // no fallback for everything else...
			} );
		$mockFallbackChain->method( 'extractPreferredValue' )
			->willReturnCallback( function( $data ) {
				foreach ( $data as $languageCode => $value ) {
					return [
						'value' => $value,
						'language' => $languageCode,
						'source' => $languageCode,
					];
				}
				return null;
			} );
		return $mockFallbackChain;
	}

	/**
	 * @param bool|null $caseSensitive
	 * @param bool|null $prefixSearch
	 * @param int|null $limit
	 *
	 * @return MatchingTermsLookupSearchInteractor
	 */
	private function newTermSearchInteractor(
		$caseSensitive = null,
		$prefixSearch = null,
		$limit = null
	) {
		$interactor = new MatchingTermsLookupSearchInteractor(
			$this->getMockTermIndex(),
			$this->getMockLanguageFallbackChainFactory(),
			$this->getMockPrefetchingTermLookup(),
			'pt'
		);

		$searchOptions = new TermSearchOptions();
		if ( $caseSensitive !== null ) {
			$searchOptions->setIsCaseSensitive( $caseSensitive );
		}
		if ( $prefixSearch !== null ) {
			$searchOptions->setIsPrefixSearch( $prefixSearch );
		}
		if ( $limit !== null ) {
			$searchOptions->setLimit( $limit );
		}

		$interactor->setTermSearchOptions( $searchOptions );

		return $interactor;
	}

	public function provideSearchForEntitiesTest() {
		$allTermTypes = [
			TermIndexEntry::TYPE_LABEL,
			TermIndexEntry::TYPE_DESCRIPTION,
			TermIndexEntry::TYPE_ALIAS,
		];

		return [
			'No Results' => [
				'caseSensitive' => false,
				'prefixSearch' => false,
				'limit' => 5000,
				[ 'ABCDEFGHI123', 'br', 'item', $allTermTypes ],
				[],
			],
			'Q111 Foo en Label match exactly' => [
				'caseSensitive' => false,
				'prefixSearch' => false,
				'limit' => 5000,
				[ 'Foo', 'en', 'item', [ TermIndexEntry::TYPE_LABEL ] ],
				[
					[
						'entityId' => new ItemId( 'Q111' ),
						'term' => new Term( 'en', 'Foo' ),
						'termtype' => 'label',
					],
				],
			],
			'Q111&Q333 Foo en Label match prefix search' => [
				'caseSensitive' => false,
				'prefixSearch' => true,
				'limit' => 5000,
				[ 'Foo', 'en', 'item', [ TermIndexEntry::TYPE_LABEL ] ],
				[
					[
						'entityId' => new ItemId( 'Q111' ),
						'term' => new Term( 'en', 'Foo' ),
						'termtype' => 'label',
					],
					[
						'entityId' => new ItemId( 'Q333' ),
						'term' => new Term( 'en', 'Food is great' ),
						'termtype' => 'label',
					],
				],
			],
			'Q111&Q333 Foo en Label match prefix search LIMIT 1' => [
				'caseSensitive' => false,
				'prefixSearch' => true,
				'limit' => 1,
				[ 'Foo', 'en', 'item', [ TermIndexEntry::TYPE_LABEL ] ],
				[
					[
						'entityId' => new ItemId( 'Q111' ),
						'term' => new Term( 'en', 'Foo' ),
						'termtype' => 'label',
					],
				],
			],
			'Q111 Foo en-ca Label fallback to en' => [
				'caseSensitive' => false,
				'prefixSearch' => false,
				'limit' => 5000,
				[ 'Foo', 'en-ca', 'item', [ TermIndexEntry::TYPE_LABEL ] ],
				[
					[
						'entityId' => new ItemId( 'Q111' ),
						'term' => new Term( 'en', 'Foo' ),
						'termtype' => 'label',
					],
				],
			],
			'Q111 Foo en all term types match case insensitive' => [
				'caseSensitive' => false,
				'prefixSearch' => false,
				'limit' => 5000,
				[ 'Foo', 'en', 'item', $allTermTypes ],
				[
					[
						'entityId' => new ItemId( 'Q111' ),
						'term' => new Term( 'en', 'Foo' ),
						'termtype' => 'label',
					],
					[
						'entityId' => new ItemId( 'Q111' ),
						'term' => new Term( 'en', 'Foo' ),
						'termtype' => 'description',
					],
					[
						'entityId' => new ItemId( 'Q111' ),
						'term' => new Term( 'en', 'Foo' ),
						'termtype' => 'alias',
					],
					[
						'entityId' => new ItemId( 'Q111' ),
						'term' => new Term( 'en', 'FOO' ),
						'termtype' => 'alias',
					],
				],
			],
			'Q111 Foo en aliases match case sensitive' => [
				'caseSensitive' => true,
				'prefixSearch' => false,
				'limit' => 5000,
				[ 'Foo', 'en', 'item', $allTermTypes ],
				[
					[
						'entityId' => new ItemId( 'Q111' ),
						'term' => new Term( 'en', 'Foo' ),
						'termtype' => 'label',
					],
					[
						'entityId' => new ItemId( 'Q111' ),
						'term' => new Term( 'en', 'Foo' ),
						'termtype' => 'description',
					],
					[
						'entityId' => new ItemId( 'Q111' ),
						'term' => new Term( 'en', 'Foo' ),
						'termtype' => 'alias',
					],
				],
			],
			'Q555 Ta en-ca with fallback aliases only' => [
				'caseSensitive' => false,
				'prefixSearch' => true,
				'limit' => 5000,
				[ 'Ta', 'en-ca', 'item', $allTermTypes ],
				[
					[
						'entityId' => new ItemId( 'Q555' ),
						'term' => new Term( 'en-ca', 'TAAA' ),
						'termtype' => 'alias',
					],
					[
						'entityId' => new ItemId( 'Q555' ),
						'term' => new Term( 'en-ca', 'Taa' ),
						'termtype' => 'alias',
					],
				],
			],
			'P22&P44 La en-ca with fallback all terms' => [
				'caseSensitive' => true,
				'prefixSearch' => true,
				'limit' => 5000,
				[ 'La', 'en-ca', 'property', $allTermTypes ],
				[
					[
						'entityId' => new NumericPropertyId( 'P44' ),
						'term' => new Term( 'en-ca', 'Lama' ),
						'termtype' => 'label',
					],
					[
						'entityId' => new NumericPropertyId( 'P11' ),
						'term' => new Term( 'en', 'Lahmacun' ),
						'termtype' => 'label' ,
					],
					[
						'entityId' => new NumericPropertyId( 'P22' ),
						'term' => new Term( 'en', 'Lama' ),
						'termtype' => 'label' ,
					],
					[
						'entityId' => new NumericPropertyId( 'P22' ),
						'term' => new Term( 'en', 'La-description' ),
						'termtype' => 'description' ,
					],
				],
			],
			'P22&P44 La en-ca with fallback all terms search LIMIT 2' => [
				'caseSensitive' => true,
				'prefixSearch' => true,
				'limit' => 2,
				[ 'La', 'en-ca', 'property', $allTermTypes ],
				[
					[
						'entityId' => new NumericPropertyId( 'P44' ),
						'term' => new Term( 'en-ca', 'Lama' ),
						'termtype' => 'label',
					],
					[
						'entityId' => new NumericPropertyId( 'P11' ),
						'term' => new Term( 'en', 'Lahmacun' ),
						'termtype' => 'label' ,
					],
				],
			],
		];
	}

	/**
	 * @dataProvider provideSearchForEntitiesTest
	 *
	 * @param bool|null $caseSensitive
	 * @param bool|null $prefixSearch
	 * @param int|null $limit
	 * @param array $params
	 * @param array[] $expectedTermsDetails each element has a 'term', 'termtype' and a 'entityId' key
	 */
	public function testSearchForEntities_returnsExpectedResults(
		$caseSensitive,
		$prefixSearch,
		$limit,
		array $params,
		array $expectedTermsDetails
	) {
		$interactor = $this->newTermSearchInteractor( $caseSensitive, $prefixSearch, $limit );

		$results = $interactor->searchForEntities( ...$params );

		$this->assertCount(
			count( $expectedTermsDetails ),
			$results,
			'Incorrect number of search results'
		);

		/** @var TermSearchResult $result */
		foreach ( $results as $key => $result ) {
			$expectedTermDetails = $expectedTermsDetails[$key];

			/** @var EntityId $expectedEntityId */
			$expectedEntityId = $expectedTermDetails['entityId'];
			$this->assertTrue( $expectedEntityId->equals( $result->getEntityId() ) );

			$this->assertEquals( $expectedTermDetails['term'], $result->getMatchedTerm() );
			$this->assertEquals( $expectedTermDetails['termtype'], $result->getMatchedTermType() );

			// These are mocked
			$this->assertEquals(
				$this->getExpectedDisplayTerm( $expectedEntityId, TermIndexEntry::TYPE_LABEL ),
				$result->getDisplayLabel()
			);
			$this->assertEquals(
				$this->getExpectedDisplayTerm( $expectedEntityId, TermIndexEntry::TYPE_DESCRIPTION ),
				$result->getDisplayDescription()
			);
		}
	}

}
