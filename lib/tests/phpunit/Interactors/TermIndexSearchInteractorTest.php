<?php

namespace Wikibase\Lib\Tests\Interactors;

use PHPUnit4And6Compat;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermFallback;
use Wikibase\LanguageFallbackChain;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lib\Interactors\TermIndexSearchInteractor;
use Wikibase\Lib\Interactors\TermSearchOptions;
use Wikibase\Lib\Interactors\TermSearchResult;
use Wikibase\Store\BufferingTermLookup;
use Wikibase\TermIndexEntry;
use Wikibase\Lib\Tests\Store\MockTermIndex;

/**
 * @covers Wikibase\Lib\Interactors\TermIndexSearchInteractor
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 */
class TermIndexSearchInteractorTest extends \PHPUnit\Framework\TestCase {
	use PHPUnit4And6Compat;

	private function getMockTermIndex() {
		return new MockTermIndex(
			[
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
				//P22
				$this->getTermIndexEntry( 'Lama', 'en-ca', TermIndexEntry::TYPE_LABEL, new PropertyId( 'P22' ) ),
				$this->getTermIndexEntry( 'La-description', 'en', TermIndexEntry::TYPE_DESCRIPTION, new PropertyId( 'P22' ) ),
				//P44
				$this->getTermIndexEntry( 'Lama', 'en', TermIndexEntry::TYPE_LABEL, new PropertyId( 'P44' ) ),
				$this->getTermIndexEntry( 'Lama-de-desc', 'de', TermIndexEntry::TYPE_DESCRIPTION, new PropertyId( 'P44' ) ),
			]
		);
	}

	/**
	 * @param string $text
	 * @param string $languageCode
	 * @param string $termType
	 * @param EntityId|ItemId|PropertyId $entityId
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
	 * @return BufferingTermLookup
	 */
	private function getMockBufferingTermLookup() {
		$mock = $this->getMockBuilder( BufferingTermLookup::class )
			->disableOriginalConstructor()
			->getMock();
		$mock->expects( $this->any() )
			->method( 'prefetchTerms' );
		$mock->expects( $this->any() )
			->method( 'getLabels' )
			->will( $this->returnCallback( function( EntityId $entityId, $languageCodes ) {
				$labels = [];
				foreach ( $languageCodes as $languageCode ) {
					$labels[$languageCode] = 'label-' . $languageCode . '-' . $entityId->getSerialization();
				}
				return $labels;
			}
			) );
		$mock->expects( $this->any() )
			->method( 'getDescriptions' )
			->will( $this->returnCallback( function( EntityId $entityId, $languageCodes ) {
				$descriptions = [];
				foreach ( $languageCodes as $languageCode ) {
					$descriptions[$languageCode] =
						'description-' . $languageCode . '-' . $entityId->getSerialization();
				}
				return $descriptions;
			}
			) );
		return $mock;
	}

	private function getExpectedDisplayTerm( EntityId $entityId, $termType ) {
		return new TermFallback( 'pt', $termType . '-pt-' . $entityId->getSerialization(), 'pt', 'pt' );
	}

	/**
	 * @return LanguageFallbackChainFactory
	 */
	private function getMockLanguageFallbackChainFactory() {
		$mockFactory = $this->getMockBuilder( LanguageFallbackChainFactory::class )
			->disableOriginalConstructor()
			->getMock();
		$mockFactory->expects( $this->any() )
			->method( 'newFromLanguageCode' )
			->will( $this->returnCallback( function( $langCode ) {
				return $this->getMockLanguageFallbackChainFromLanguage( $langCode );
			} ) );
		return $mockFactory;
	}

	/**
	 * @param string $langCode
	 *
	 * @return LanguageFallbackChain
	 */
	public function getMockLanguageFallbackChainFromLanguage( $langCode ) {
		$mockFallbackChain = $this->getMockBuilder( LanguageFallbackChain::class )
			->disableOriginalConstructor()
			->getMock();
		$mockFallbackChain->expects( $this->any() )
			->method( 'getFetchLanguageCodes' )
			->will( $this->returnCallback( function () use( $langCode ) {
				if ( $langCode === 'en-gb' || $langCode === 'en-ca' ) {
					return [ $langCode, 'en' ];
				}
				return [ $langCode ]; // no fallback for everything else...
			} ) );
		$mockFallbackChain->expects( $this->any() )
			->method( 'extractPreferredValue' )
			->will( $this->returnCallback( function( $data ) {
				foreach ( $data as $languageCode => $value ) {
					return [
						'value' => $value,
						'language' => $languageCode,
						'source' => $languageCode,
					];
				}
				return null;
			} ) );
		return $mockFallbackChain;
	}

	/**
	 * @param bool|null $caseSensitive
	 * @param bool|null $prefixSearch
	 * @param int|null $limit
	 *
	 * @return TermIndexSearchInteractor
	 */
	private function newTermSearchInteractor(
		$caseSensitive = null,
		$prefixSearch = null,
		$limit = null
	) {
		$interactor = new TermIndexSearchInteractor(
			$this->getMockTermIndex(),
			$this->getMockLanguageFallbackChainFactory(),
			$this->getMockBufferingTermLookup(),
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
			TermIndexEntry::TYPE_ALIAS
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
				],
			],
			'P22&P44 La en-ca with fallback all terms' => [
				'caseSensitive' => true,
				'prefixSearch' => true,
				'limit' => 5000,
				[ 'La', 'en-ca', 'property', $allTermTypes ],
				[
					[
						'entityId' => new PropertyId( 'P22' ),
						'term' => new Term( 'en-ca', 'Lama' ),
						'termtype' => 'label',
					],
					[
						'entityId' => new PropertyId( 'P44' ),
						'term' => new Term( 'en', 'Lama' ),
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

		// $interactor->searchForEntities() call
		$results = call_user_func_array( [ $interactor, 'searchForEntities' ], $params );

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
