<?php

namespace Wikibase\Test\Interactors;

use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermFallback;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Repo\Interactors\TermIndexSearchInteractor;
use Wikibase\Store\BufferingTermLookup;
use Wikibase\TermIndexEntry;
use Wikibase\Test\MockTermIndex;

/**
 * @covers Wikibase\Repo\Interactors\TermIndexSearchInteractor
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseInteractor
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 */
class TermIndexSearchInteractorTest extends PHPUnit_Framework_TestCase {

	private function getMockTermIndex() {
		return new MockTermIndex(
			array(
				//Q111
				$this->getTermIndexEntry( 'Foo', 'en', TermIndexEntry::TYPE_LABEL, new ItemId( 'Q111' ) ),
				$this->getTermIndexEntry( 'Foo', 'en-gb', TermIndexEntry::TYPE_LABEL, new ItemId( 'Q111' ) ),
				$this->getTermIndexEntry( 'Foo german decription', 'de', TermIndexEntry::TYPE_DESCRIPTION, new ItemId( 'Q111' ) ),
				$this->getTermIndexEntry( 'Foooooo', 'en', TermIndexEntry::TYPE_ALIAS, new ItemId( 'Q111' ) ),
				//Q222
				$this->getTermIndexEntry( 'Bar', 'en', TermIndexEntry::TYPE_LABEL, new ItemId( 'Q222' ) ),
				$this->getTermIndexEntry( 'BarGerman', 'de', TermIndexEntry::TYPE_LABEL, new ItemId( 'Q222' ) ),
				$this->getTermIndexEntry( 'BarGerman description', 'de', TermIndexEntry::TYPE_DESCRIPTION, new ItemId( 'Q222' ) ),
				$this->getTermIndexEntry( 'Barrrrr', 'en', TermIndexEntry::TYPE_ALIAS, new ItemId( 'Q222' ) ),
				//Q333
				$this->getTermIndexEntry( 'Food', 'en', TermIndexEntry::TYPE_LABEL, new ItemId( 'Q333' ) ),
				$this->getTermIndexEntry( 'Bar snacks', 'en', TermIndexEntry::TYPE_ALIAS, new ItemId( 'Q333' ) ),
				//P11
				$this->getTermIndexEntry( 'Foo', 'en', TermIndexEntry::TYPE_LABEL, new PropertyId( 'P11' ) ),
				//P22
				$this->getTermIndexEntry( 'Lama', 'en', TermIndexEntry::TYPE_LABEL, new PropertyId( 'P22' ) ),
				$this->getTermIndexEntry( 'Laama', 'en', TermIndexEntry::TYPE_ALIAS, new PropertyId( 'P22' ) ),
				$this->getTermIndexEntry( 'Lamama', 'en', TermIndexEntry::TYPE_ALIAS, new PropertyId( 'P22' ) ),
				$this->getTermIndexEntry( 'La description', 'pt', TermIndexEntry::TYPE_ALIAS, new PropertyId( 'P22' ) ),
				$this->getTermIndexEntry( 'LaLa description', 'pt', TermIndexEntry::TYPE_ALIAS, new PropertyId( 'P22' ) ),
				$this->getTermIndexEntry( 'ptDisplayAlias2', 'pt', TermIndexEntry::TYPE_ALIAS, new PropertyId( 'P22' ) ),
			)
		);
	}

	/**
	 * @param string $text
	 * @param string $languageCode
	 * @param string $termType
	 * @param EntityId|ItemId|PropertyId $entityId
	 *
	 * @returns TermIndexEntry
	 */
	private function getTermIndexEntry( $text, $languageCode, $termType, EntityId $entityId ) {
		return new TermIndexEntry( array(
			'termText' => $text,
			'termLanguage' => $languageCode,
			'termType' => $termType,
			'entityId' => $entityId->getNumericId(),
			'entityType' => $entityId->getEntityType(),
		) );
	}

	/**
	 * Get a lookup that always returns a pt label and description suffixed by the entity ID
	 *
	 * @return BufferingTermLookup
	 */
	private function getMockBufferingTermLookup() {
		$mock = $this->getMockBuilder( 'Wikibase\Store\BufferingTermLookup' )
			->disableOriginalConstructor()
			->getMock();
		$mock->expects( $this->any() )
			->method( 'prefetchTerms' );
		$mock->expects( $this->any() )
			->method( 'getLabels' )
			->will( $this->returnCallback( function( EntityId $entityId, $languageCodes ) {
				$labels = array();
				foreach( $languageCodes as $languageCode ) {
					$labels[$languageCode] = 'label-' . $languageCode . '-' . $entityId->getSerialization();
				}
				return $labels;
			}
			) );
		$mock->expects( $this->any() )
			->method( 'getDescriptions' )
			->will( $this->returnCallback( function( EntityId $entityId, $languageCodes ) {
				$descriptions = array();
				foreach( $languageCodes as $languageCode ) {
					$descriptions[$languageCode] = 'description-' . $languageCode . '-' . $entityId->getSerialization();
				}
				return $descriptions;
			}
			) );
		return $mock;
	}

	private function getDisplayTerm( EntityId $entityId, $termType ) {
		return new TermFallback( 'pt', $termType . '-pt-' . $entityId->getSerialization(), 'pt', 'pt' );
	}

	/**
	 * @return LanguageFallbackChainFactory
	 */
	private function getMockLanguageFallbackChainFactory() {
		$testCase = $this;
		$mockFactory = $this->getMockBuilder( 'Wikibase\LanguageFallbackChainFactory' )
			->disableOriginalConstructor()
			->getMock();
		$mockFactory->expects( $this->any() )
			->method( 'newFromLanguageCode' )
			->will( $this->returnCallback( function( $langCode ) use ( $testCase )  {
				$mockFallbackChain = $testCase->getMockBuilder( 'Wikibase\LanguageFallbackChain' )
					->disableOriginalConstructor()
					->getMock();
				$mockFallbackChain->expects( $testCase->any() )
					->method( 'getFetchLanguageCodes' )
					->will( $this->returnCallback( function () use( $langCode ) {
						if ( $langCode === 'en-gb' || $langCode === 'en-ca' ) {
							return array( $langCode, 'en' );
						}
						return array( $langCode ); // no fallback for everything else...
					} ) );
				$mockFallbackChain->expects( $testCase->any() )
					->method( 'extractPreferredValue' )
					->will( $this->returnCallback( function( $data ) {
						foreach ( $data as $languageCode => $value ) {
							return array(
								'value' => $value,
								'language' => $languageCode,
								'source' => $languageCode,
							);
						}
						return null;
					} ) );
				return $mockFallbackChain;
			} ) );
		return $mockFactory;
	}

	/**
	 * @param bool $caseSensitive
	 * @param bool $prefixSearch
	 * @param int $limit
	 * @param bool $useFallback
	 *
	 * @return TermIndexSearchInteractor
	 */
	private function newTermSearchInteractor(
		$caseSensitive = null,
		$prefixSearch = null,
		$limit = null,
		$useFallback = null
	) {
		$interactor = new TermIndexSearchInteractor(
			$this->getMockTermIndex(),
			$this->getMockLanguageFallbackChainFactory(),
			$this->getMockBufferingTermLookup(),
			'pt'
		);
		if ( $caseSensitive !== null ) {
			$interactor->setIsCaseSensitive( $caseSensitive );
		}
		if ( $prefixSearch !== null ) {
			$interactor->setIsPrefixSearch( $prefixSearch );
		}
		if ( $limit !== null ) {
			$interactor->setLimit( $limit );
		}
		if ( $useFallback !== null ) {
			$interactor->setUseLanguageFallback( $useFallback );
		}
		return $interactor;
	}

	public function provideSearchForEntitiesTest() {
		$allTermTypes = array(
			TermIndexEntry::TYPE_LABEL,
			TermIndexEntry::TYPE_DESCRIPTION,
			TermIndexEntry::TYPE_ALIAS
		);
		return array(
			'No Results' => array(
				$this->newTermSearchInteractor( false, false, 5000 ),
				array( 'ABCDEFGHI',  array( 'en', 'de' ), 'item', $allTermTypes ),
				array()
			),
			'Foo, Item, EN, Label Only' => array(
				$this->newTermSearchInteractor( false, false, 5000 ),
				array( 'Foo',  array( 'en' ), 'item', array( TermIndexEntry::TYPE_LABEL ) ),
				array(
					array( 'entityId' => new ItemId( 'Q111' ), 'term' => new Term( 'en', 'Foo' ), ),
				)
			),
			'Foo, Item, EN-GB(fallback), Label Only' => array(
				$this->newTermSearchInteractor( false, false, 5000 ),
				array( 'Foo',  array( 'en-gb' ), 'item', array( TermIndexEntry::TYPE_LABEL ) ),
				array(
					array( 'entityId' => new ItemId( 'Q111' ), 'term' => new Term( 'en', 'Foo' ), ),
					array( 'entityId' => new ItemId( 'Q111' ), 'term' => new Term( 'en-gb', 'Foo' ), ),
				)
			),
			'Foo, Item, EN-CA(fallback), Label Only' => array(
				$this->newTermSearchInteractor( false, false, 5000 ),
				array( 'Foo',  array( 'en-ca' ), 'item', array( TermIndexEntry::TYPE_LABEL ) ),
				array(
					array( 'entityId' => new ItemId( 'Q111' ), 'term' => new Term( 'en', 'Foo' ), ),
				)
			),
			'Foo, Item, EN-CA(no-fallback), Label Only' => array(
				$this->newTermSearchInteractor( false, false, 5000, false ),
				array( 'Foo',  array( 'en-ca' ), 'item', array( TermIndexEntry::TYPE_LABEL ) ),
				array()
			),
			'No Results, fOO, Item, EN, All Terms, Case Sensitive' => array(
				$this->newTermSearchInteractor( true, false, 5000 ),
				array( 'fOO',  array( 'en' ), 'item', $allTermTypes ),
				array()
			),
			'Foo, Item, EN, Label, Prefix, Case Sensitive, Limit 2' => array(
				$this->newTermSearchInteractor( true, true, 2 ),
				array( 'Foo',  array( 'en' ), 'item', array( TermIndexEntry::TYPE_LABEL ) ),
				array(
					array( 'entityId' => new ItemId( 'Q111' ), 'term' => new Term( 'en', 'Foo' ), ),
					array( 'entityId' => new ItemId( 'Q333' ), 'term' => new Term( 'en', 'Food' ), ),
				)
			),
			'Foo, Item, EN, Label, Prefix, Limit 1' => array(
				$this->newTermSearchInteractor( false, true, 1 ),
				array( 'Foo',  array( 'en' ), 'item', array( TermIndexEntry::TYPE_LABEL ) ),
				array(
					array( 'entityId' => new ItemId( 'Q111' ), 'term' => new Term( 'en', 'Foo' ), ),
				)
			),
			'Foo, Item, EN, Label & Alias, Prefix' => array(
				$this->newTermSearchInteractor( false, true, 5000 ),
				array( 'Foo',  array( 'en' ), 'item', array( TermIndexEntry::TYPE_LABEL, TermIndexEntry::TYPE_ALIAS ) ),
				array(
					array( 'entityId' => new ItemId( 'Q111' ), 'term' => new Term( 'en', 'Foo' ), ),
					array( 'entityId' => new ItemId( 'Q111' ), 'term' => new Term( 'en', 'Foooooo' ), ),
					array( 'entityId' => new ItemId( 'Q333' ), 'term' => new Term( 'en', 'Food' ), ),
				)
			),
			'Bar, Item, EN & DE, All term types, Prefix' => array(
				$this->newTermSearchInteractor( false, true, 5000 ),
				array( 'Bar',  array( 'en', 'de' ), 'item', $allTermTypes ),
				array(
					array( 'entityId' => new ItemId( 'Q222' ), 'term' => new Term( 'en', 'Bar' ), ),
					array( 'entityId' => new ItemId( 'Q222' ), 'term' => new Term( 'de', 'BarGerman' ), ),
					array( 'entityId' => new ItemId( 'Q222' ), 'term' => new Term( 'de', 'BarGerman description' ), ),
					array( 'entityId' => new ItemId( 'Q222' ), 'term' => new Term( 'en', 'Barrrrr' ), ),
					array( 'entityId' => new ItemId( 'Q333' ), 'term' => new Term( 'en', 'Bar snacks' ), ),
				)
			),
			'La, Property, EN, Aliases, Prefix' => array(
				$this->newTermSearchInteractor( false, true, 5000 ),
				array( 'La',  array( 'en' ), 'property', array( TermIndexEntry::TYPE_ALIAS ) ),
				array(
					array( 'entityId' => new PropertyId( 'P22' ), 'term' => new Term( 'en', 'Laama' ), ),
					array( 'entityId' => new PropertyId( 'P22' ), 'term' => new Term( 'en', 'Lamama' ), ),
				)
			),
		);
	}

	/**
	 * @dataProvider provideSearchForEntitiesTest
	 *
	 * @param TermIndexSearchInteractor $interactor
	 * @param array $params
	 * @param array[] $expectedTermsDetails each element has a 'term' and a 'entityId' key
	 */
	public function testSearchForEntities_returnsExpectedResults( $interactor, $params, $expectedTermsDetails ) {
		// $interactor->searchForEntities() call
		$results = call_user_func_array( array( $interactor, 'searchForEntities' ), $params );

		$this->assertCount(
			count( $expectedTermsDetails ),
			$results,
			'Incorrect number of search results'
		);

		foreach ( $results as $key => $result ) {
			/** @var Term $resultTerm */
			$resultTerm = $result['matchedTerms'];
			$expectedTermDetails = $expectedTermsDetails[$key];

			/** @var EntityId $expectedEntityId */
			$expectedEntityId = $expectedTermDetails['entityId'];
			$this->assertTrue( $expectedEntityId->equals( $result['entityId'] ) );

			/** @var Term $expectedTerm */
			$expectedTerm = $expectedTermDetails['term'];
			$this->assertTrue( $expectedTerm->equals( $resultTerm ) );

			$expectedDisplayTerms = array(
				TermIndexEntry::TYPE_LABEL => $this->getDisplayTerm(
					$expectedEntityId,
					TermIndexEntry::TYPE_LABEL
				),
				TermIndexEntry::TYPE_DESCRIPTION => $this->getDisplayTerm(
					$expectedEntityId,
					TermIndexEntry::TYPE_DESCRIPTION
				),
			);
			if( $expectedEntityId->getSerialization() === 'P22' ) {
				// For P22 we also expect 2 display aliases
				$expectedDisplayTerms[TermIndexEntry::TYPE_ALIAS] = array(
					new Term( 'pt', 'La description' ),
					new Term( 'pt', 'LaLa description' ),
				);
			}
			$this->assertEquals( $expectedDisplayTerms, $result['displayTerms'] );
		}
	}

	public function provideLimitInputAndExpected() {
		return array(
			array( 1, 1 ),
			array( 5000, 5000 ),
			array( 999999, 5000 ),
		);
	}

	/**
	 * @dataProvider provideLimitInputAndExpected
	 */
	public function testSetLimit( $input, $expected ) {
		$interactor = $this->newTermSearchInteractor();
		$interactor->setLimit( $input );
		$this->assertEquals( $expected, $interactor->getLimit() );
	}

	public function provideBooleanOptions() {
		return array(
			array( true ),
			array( false ),
		);
	}

	/**
	 * @dataProvider provideBooleanOptions
	 */
	public function testSetIsCaseSensitive( $booleanValue ) {
		$interactor = $this->newTermSearchInteractor();
		$interactor->setIsCaseSensitive( $booleanValue );
		$this->assertEquals( $booleanValue, $interactor->getIsCaseSensitive() );
	}

	/**
	 * @dataProvider provideBooleanOptions
	 */
	public function testSetIsprefixSearch( $booleanValue ) {
		$interactor = $this->newTermSearchInteractor();
		$interactor->setIsPrefixSearch( $booleanValue );
		$this->assertEquals( $booleanValue, $interactor->getIsPrefixSearch() );
	}

}
