<?php

namespace Wikibase\Test\Interactors;

use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\Store\LanguageLabelDescriptionLookup;
use Wikibase\Repo\Interactors\TermSearchInteractor;
use Wikibase\Term;
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
				$this->getTerm( 'Foo', 'en', Term::TYPE_LABEL, new ItemId( 'Q111' ) ),
				$this->getTerm( 'Foo german decription', 'de', Term::TYPE_DESCRIPTION, new ItemId( 'Q111' ) ),
				$this->getTerm( 'Foooooo', 'en', Term::TYPE_ALIAS, new ItemId( 'Q111' ) ),
				//Q222
				$this->getTerm( 'Bar', 'en', Term::TYPE_LABEL, new ItemId( 'Q222' ) ),
				$this->getTerm( 'BarGerman', 'de', Term::TYPE_LABEL, new ItemId( 'Q222' ) ),
				$this->getTerm( 'BarGerman description', 'de', Term::TYPE_DESCRIPTION, new ItemId( 'Q222' ) ),
				$this->getTerm( 'Barrrrr', 'en', Term::TYPE_ALIAS, new ItemId( 'Q222' ) ),
				//Q333
				$this->getTerm( 'Food', 'en', Term::TYPE_LABEL, new ItemId( 'Q333' ) ),
				$this->getTerm( 'Lebensmittel', 'de', Term::TYPE_LABEL, new ItemId( 'Q333' ) ),
				$this->getTerm( 'You eat me...', 'en', Term::TYPE_DESCRIPTION, new ItemId( 'Q333' ) ),
				$this->getTerm( 'Nom Noms', 'en', Term::TYPE_ALIAS, new ItemId( 'Q333' ) ),
				$this->getTerm( 'Eating Stuff', 'en', Term::TYPE_ALIAS, new ItemId( 'Q333' ) ),
				$this->getTerm( 'Bar snacks', 'en', Term::TYPE_ALIAS, new ItemId( 'Q333' ) ),
				//P11
				$this->getTerm( 'Foo', 'en', Term::TYPE_LABEL, new PropertyId( 'P11' ) ),
				$this->getTerm( 'Description', 'en', Term::TYPE_DESCRIPTION, new PropertyId( 'P11' ) ),
				$this->getTerm( 'DEscription', 'de', Term::TYPE_DESCRIPTION, new PropertyId( 'P11' ) ),
				//P22
				$this->getTerm( 'Lama', 'en', Term::TYPE_LABEL, new PropertyId( 'P22' ) ),
				$this->getTerm( 'Laama', 'en', Term::TYPE_ALIAS, new PropertyId( 'P22' ) ),
				$this->getTerm( 'Lamama', 'en', Term::TYPE_ALIAS, new PropertyId( 'P22' ) ),
			)
		);
	}

	/**
	 * @param string $text
	 * @param string $languageCode
	 * @param string $termType
	 * @param EntityId|ItemId|PropertyId $entityId
	 *
	 * @returns Term
	 */
	private function getTerm( $text, $languageCode, $termType, EntityId $entityId ) {
		return new Term( array(
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
				return $testCase->getDisplayTerm( $entityId, Term::TYPE_LABEL );
			}
			) );
		$mock->expects( $this->any() )
			->method( 'getDescription' )
			->will( $this->returnCallback( function( EntityId $entityId ) use ( $testCase ) {
				return $testCase->getDisplayTerm( $entityId, Term::TYPE_DESCRIPTION );
			}
			) );
		return $mock;
	}

	private function getDisplayTerm( EntityId $entityId, $termType ) {
		return $this->getTerm(
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
		return array(
			'No Results' => array(
				$this->getTermSearchInteractor( false, false, 5000 ),
				array( 'ABCDEFGHI',  array( 'en' ), 'item', array( Term::TYPE_LABEL, Term::TYPE_DESCRIPTION, Term::TYPE_ALIAS ) ),
				array()
			),
			'Foo Label Only' => array(
				$this->getTermSearchInteractor( false, false, 5000 ),
				array( 'Foo',  array( 'en' ), 'item', array( Term::TYPE_LABEL ) ),
				array(
					$this->getTerm( 'Foo', 'en', Term::TYPE_LABEL, new ItemId( 'Q111' ) ),
				)
			),
			'Foo Label Prefix' => array(
				$this->getTermSearchInteractor( false, true, 5000 ),
				array( 'Foo',  array( 'en' ), 'item', array( Term::TYPE_LABEL ) ),
				array(
					$this->getTerm( 'Foo', 'en', Term::TYPE_LABEL, new ItemId( 'Q111' ) ),
					$this->getTerm( 'Food', 'en', Term::TYPE_LABEL, new ItemId( 'Q333' ) ),
				)
			),
			'Foo Label & Alias Prefix' => array(
				$this->getTermSearchInteractor( false, true, 5000 ),
				array( 'Foo',  array( 'en' ), 'item', array( Term::TYPE_LABEL, Term::TYPE_ALIAS ) ),
				array(
					$this->getTerm( 'Foo', 'en', Term::TYPE_LABEL, new ItemId( 'Q111' ) ),
					$this->getTerm( 'Foooooo', 'en', Term::TYPE_ALIAS, new ItemId( 'Q111' ) ),
					$this->getTerm( 'Food', 'en', Term::TYPE_LABEL, new ItemId( 'Q333' ) ),
				)
			),
		);
	}

	/**
	 * @dataProvider provideSearchForTermsTest
	 *
	 * @param TermSearchInteractor $interactor
	 * @param array $methodParams
	 * @param Term[] $expectedTerms
	 */
	public function testSearchForTerms( $interactor, $methodParams, $expectedTerms ) {
		// $interactor->searchForTerms() call
		$results = call_user_func_array( array( $interactor, 'searchForTerms' ), $methodParams );

		$this->assertCount( count( $expectedTerms ), $results, 'Incorrect number of search results' );
		foreach ( $results as $key => $result ) {
			$expectedTerm = $expectedTerms[$key];
			/** @var Term $resultTerm */
			$resultTerm = $result[1];

			$this->assertTrue( $expectedTerm->getEntityId()->equals( $result[0] ) );

			$this->assertTrue( $expectedTerm->getEntityId()->equals( $resultTerm->getEntityId() ) );
			$this->assertEquals( $expectedTerm->getEntityType(), $resultTerm->getEntityType() );
			$this->assertEquals( $expectedTerm->getLanguage(), $resultTerm->getLanguage() );
			$this->assertEquals( $expectedTerm->getText(), $resultTerm->getText() );
			$this->assertEquals( $expectedTerm->getType(), $resultTerm->getType() );

			$expectedDisplayTerms = array(
				$this->getDisplayTerm( $resultTerm->getEntityId(), Term::TYPE_LABEL ),
				$this->getDisplayTerm( $resultTerm->getEntityId(), Term::TYPE_DESCRIPTION ),
			);
			$this->assertEquals( $expectedDisplayTerms, $result[2] );
		}
	}

}