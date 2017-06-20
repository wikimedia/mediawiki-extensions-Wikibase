<?php

namespace Wikibase\Lib\Tests\Interactors;

use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lib\Interactors\ConfigurableTermSearchInteractor;
use Wikibase\Lib\Interactors\DispatchingTermSearchInteractor;
use Wikibase\Lib\Interactors\TermSearchInteractor;
use Wikibase\Lib\Interactors\TermSearchOptions;
use Wikibase\Lib\Interactors\TermSearchResult;
use Wikibase\TermIndexEntry;
use Wikimedia\Assert\ParameterAssertionException;

/**
 * @covers Wikibase\Lib\Interactors\DispatchingTermSearchInteractor
 *
 * @group Wikibase
 *
 * @license GPL-2.0+
 */
class DispatchingTermSearchInteractorTest extends PHPUnit_Framework_TestCase {

	public function provideInvalidInteractorConfig() {
		return [
			'non-TermSearchInteractor values' => [
				[ 'item' => 'foo' ]
			],
			'non-string keys' => [
				[ 0 => $this->getTermSearchInteractor( [] ) ]
			]
		];
	}

	/**
	 * @dataProvider provideInvalidInteractorConfig
	 */
	public function testGivenInvalidInteractorConfig_exceptionIsThrown( array $interactors ) {
		$this->setExpectedException( ParameterAssertionException::class );
		new DispatchingTermSearchInteractor( $interactors );
	}

	private function getTermSearchInteractor( array $resultsByEntityType ) {
		$interactor = $this->getMock( TermSearchInteractor::class );
		$interactor->expects( $this->any() )
			->method( 'searchForEntities' )
			->will( $this->returnCallback(
				function ( $text, $language, $entityType ) use ( $resultsByEntityType ) {
					return isset( $resultsByEntityType[$entityType] ) ? $resultsByEntityType[$entityType] : [];
				}
			) );
		return $interactor;
	}

	public function testSearchForEntities_usesTheInteractorConfiguredForEntityType() {
		$interactorOne = $this->getTermSearchInteractor( [
			'item' => [
				new TermSearchResult(
					new Term( 'en', 'foot' ),
					'label',
					new ItemId( 'Q123' )
				),
				new TermSearchResult(
					new Term( 'en', 'fox' ),
					'label',
					new ItemId( 'Q321' )
				),
			],
			'property' => [
				new TermSearchResult(
					new Term( 'en', 'founder' ),
					'label',
					new PropertyId( 'P123' )
				),
			]
		] );
		$interactorTwo = $this->getTermSearchInteractor( [
			'property' => [
				new TermSearchResult(
					new Term( 'en', 'follows' ),
					'label',
					new PropertyId( 'baz:P322' )
				),
				new TermSearchResult(
					new Term( 'en', 'followed by' ),
					'label',
					new PropertyId( 'baz:P567' )
				),
			]
		] );

		$interactor = new DispatchingTermSearchInteractor( [
			'item' => $interactorOne,
			'property' => $interactorTwo,
		] );

		$this->assertEquals(
			[
				new TermSearchResult(
					new Term( 'en', 'foot' ),
					'label',
					new ItemId( 'Q123' )
				),
				new TermSearchResult(
					new Term( 'en', 'fox' ),
					'label',
					new ItemId( 'Q321' )
				),
			],
			$interactor->searchForEntities( 'fo', 'en', 'item', [ TermIndexEntry::TYPE_LABEL ] )
		);
		$this->assertEquals(
			[
				new TermSearchResult(
					new Term( 'en', 'follows' ),
					'label',
					new PropertyId( 'baz:P322' )
				),
				new TermSearchResult(
					new Term( 'en', 'followed by' ),
					'label',
					new PropertyId( 'baz:P567' )
				),
			],
			$interactor->searchForEntities( 'fo', 'en', 'property', [ TermIndexEntry::TYPE_LABEL ] )
		);
	}

	public function testTermSearchOptionsArePassedToInteractor() {
		$searchOptions = new TermSearchOptions();
		$searchOptions->setLimit( 1234 );
		$searchOptions->setIsPrefixSearch( true );
		$searchOptions->setIsCaseSensitive( true );

		$itemInteractor = $this->getMock( ConfigurableTermSearchInteractor::class );
		$itemInteractor->expects( $this->atLeastOnce() )
			->method( 'setTermSearchOptions' )
			->with( $searchOptions );

		$dispatchingInteractor = new DispatchingTermSearchInteractor( [ 'item' => $itemInteractor ] );

		$dispatchingInteractor->setTermSearchOptions( $searchOptions );

		$dispatchingInteractor->searchForEntities( 'foo', 'en', 'item', [ TermIndexEntry::TYPE_LABEL ] );
	}

}
