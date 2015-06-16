<?php

namespace Wikibase\Test\Interactors;

use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lib\Store\LanguageLabelDescriptionLookup;
use Wikibase\Repo\Interactors\TermSearchInteractor;
use Wikibase\TermIndexEntry;
use Wikibase\Test\MockTermIndex;

/**
 * @covers Wikibase\Repo\Interactors\TermSearchInteractor
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseInteractor
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 */
class TermSearchInteractorTest extends PHPUnit_Framework_TestCase {

	private function getMockTermIndex() {
		return new MockTermIndex(
			array(
				//Q111
				$this->getTermIndexEntry( 'Foo', 'en', TermIndexEntry::TYPE_LABEL, new ItemId( 'Q111' ) ),
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
	 * @return LanguageLabelDescriptionLookup
	 */
	private function getMockLabelDescriptionLookup() {
		$mock = $this->getMockBuilder( 'Wikibase\Lib\Store\LanguageLabelDescriptionLookup' )
			->disableOriginalConstructor()
			->getMock();
		$testCase = $this;
		$mock->expects( $this->any() )
			->method( 'getLabel' )
			->will( $this->returnCallback( function( EntityId $entityId ) use ( $testCase ) {
				return $testCase->getDisplayTerm( $entityId, TermIndexEntry::TYPE_LABEL );
			}
			) );
		$mock->expects( $this->any() )
			->method( 'getDescription' )
			->will( $this->returnCallback( function( EntityId $entityId ) use ( $testCase ) {
				return $testCase->getDisplayTerm( $entityId, TermIndexEntry::TYPE_DESCRIPTION );
			}
			) );
		return $mock;
	}

	private function getDisplayTerm( EntityId $entityId, $termType ) {
		return $this->getTermIndexEntry(
			$termType . '-' . $entityId->getSerialization(),
			'pt',
			$termType,
			$entityId
		);
	}

	/**
	 * @param bool $caseSensitive
	 * @param bool $prefixSearch
	 * @param int $limit
	 *
	 * @return TermSearchInteractor
	 */
	private function getTermSearchInteractor( $caseSensitive, $prefixSearch, $limit ) {
		return new TermSearchInteractor(
			$this->getMockTermIndex(),
			$caseSensitive,
			$prefixSearch,
			$limit,
			$this->getMockLabelDescriptionLookup()
		);
	}

	public function provideSearchForTermsTest() {
		$allTermTypes = array(
			TermIndexEntry::TYPE_LABEL,
			TermIndexEntry::TYPE_DESCRIPTION,
			TermIndexEntry::TYPE_ALIAS
		);
		return array(
			'No Results' => array(
				$this->getTermSearchInteractor( false, false, 5000 ),
				array( 'ABCDEFGHI',  array( 'en', 'de' ), 'item', $allTermTypes ),
				array()
			),
			'Foo, Item, EN, Label Only' => array(
				$this->getTermSearchInteractor( false, false, 5000 ),
				array( 'Foo',  array( 'en' ), 'item', array( TermIndexEntry::TYPE_LABEL ) ),
				array(
					array( 'entityId' => new ItemId( 'Q111' ), 'term' => new Term( 'en', 'Foo' ), ),
				)
			),
			'No Results, fOO, Item, EN, All Terms, Case Sensitive' => array(
				$this->getTermSearchInteractor( true, false, 5000 ),
				array( 'fOO',  array( 'en' ), 'item', $allTermTypes ),
				array()
			),
			'Foo, Item, EN, Label, Prefix, Case Sensitive, Limit 2' => array(
				$this->getTermSearchInteractor( true, true, 2 ),
				array( 'Foo',  array( 'en' ), 'item', array( TermIndexEntry::TYPE_LABEL ) ),
				array(
					array( 'entityId' => new ItemId( 'Q111' ), 'term' => new Term( 'en', 'Foo' ), ),
					array( 'entityId' => new ItemId( 'Q333' ), 'term' => new Term( 'en', 'Food' ), ),
				)
			),
			'Foo, Item, EN, Label, Prefix, Limit 1' => array(
				$this->getTermSearchInteractor( false, true, 1 ),
				array( 'Foo',  array( 'en' ), 'item', array( TermIndexEntry::TYPE_LABEL ) ),
				array(
					array( 'entityId' => new ItemId( 'Q111' ), 'term' => new Term( 'en', 'Foo' ), ),
				)
			),
			'Foo, Item, EN, Label & Alias, Prefix' => array(
				$this->getTermSearchInteractor( false, true, 5000 ),
				array( 'Foo',  array( 'en' ), 'item', array( TermIndexEntry::TYPE_LABEL, TermIndexEntry::TYPE_ALIAS ) ),
				array(
					array( 'entityId' => new ItemId( 'Q111' ), 'term' => new Term( 'en', 'Foo' ), ),
					array( 'entityId' => new ItemId( 'Q111' ), 'term' => new Term( 'en', 'Foooooo' ), ),
					array( 'entityId' => new ItemId( 'Q333' ), 'term' => new Term( 'en', 'Food' ), ),
				)
			),
			'Bar, Item, EN & DE, All term types, Prefix' => array(
				$this->getTermSearchInteractor( false, true, 5000 ),
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
				$this->getTermSearchInteractor( false, true, 5000 ),
				array( 'La',  array( 'en' ), 'property', array( TermIndexEntry::TYPE_ALIAS ) ),
				array(
					array( 'entityId' => new PropertyId( 'P22' ), 'term' => new Term( 'en', 'Laama' ), ),
					array( 'entityId' => new PropertyId( 'P22' ), 'term' => new Term( 'en', 'Lamama' ), ),
				)
			),
		);
	}

	/**
	 * @dataProvider provideSearchForTermsTest
	 *
	 * @param TermSearchInteractor $interactor
	 * @param array $params
	 * @param array[] $expectedTermsDetails each element has a 'term' and a 'entityId' key
	 */
	public function testSearchForTerms_returnsExpectedResults( $interactor, $params, $expectedTermsDetails ) {
		// $interactor->searchForTerms() call
		$results = call_user_func_array( array( $interactor, 'searchForTerms' ), $params );

		$this->assertCount(
			count( $expectedTermsDetails ),
			$results,
			'Incorrect number of search results'
		);

		foreach ( $results as $key => $result ) {
			/** @var Term $resultTerm */
			$resultTerm = $result['matchedTerm'];
			$expectedTermDetails = $expectedTermsDetails[$key];

			/** @var EntityId $expectedEntityId */
			$expectedEntityId = $expectedTermDetails['entityId'];
			$this->assertTrue( $expectedEntityId->equals( $result['entityId'] ) );

			/** @var Term $expectedTerm */
			$expectedTerm = $expectedTermDetails['term'];
			$this->assertTrue( $expectedTerm->equals( $resultTerm ) );

			$expectedDisplayTerms = array(
				TermIndexEntry::TYPE_LABEL => $this->getDisplayTerm( $expectedEntityId, TermIndexEntry::TYPE_LABEL ),
				TermIndexEntry::TYPE_DESCRIPTION => $this->getDisplayTerm( $expectedEntityId, TermIndexEntry::TYPE_DESCRIPTION ),
			);
			$this->assertEquals( $expectedDisplayTerms, $result['displayTerms'] );
		}
	}

	public function testMaxLimitIsEnforced() {
		$interactor = $this->getTermSearchInteractor( true, false, 999999 );
		$this->assertEquals( 5000, $interactor->getLimit() );
	}

}