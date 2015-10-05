<?php

namespace Wikibase\Test;

use FauxRequest;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Term;
use Wikibase\ItemDisambiguation;
use Wikibase\Lib\Interactors\TermIndexSearchInteractor;
use Wikibase\Repo\Specials\SpecialItemDisambiguation;
use Wikibase\TermIndexEntry;

/**
 * @covers Wikibase\Repo\Specials\SpecialItemDisambiguation
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group SpecialPage
 * @group WikibaseSpecialPage
 *
 * @group Database
 *        ^---- needed because we rely on Title objects internally
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Adam Shorland
 */
class SpecialItemDisambiguationTest extends SpecialPageTestBase {

	/**
	 * @return ItemDisambiguation
	 */
	private function getMockItemDisambiguation() {
		$mock = $this->getMockBuilder( 'Wikibase\ItemDisambiguation' )
			->disableOriginalConstructor()
			->getMock();
		$mock->expects( $this->any() )
			->method( 'getHTML' )
			->will( $this->returnCallback( function ( $searchResult ) {
				return '<span class="mock-span" >ItemDisambiguationHTML-' . count( $searchResult ) . '</span>';
			} ) );
		return $mock;
	}

	/**
	 * @return TermIndexSearchInteractor
	 */
	private function getMockSearchInteractor() {
		$returnResults = array(
			array(
				'entityId' => new ItemId( 'Q2' ),
				'matchedTermType' => 'label',
				'matchedTerm' => new Term( 'fr', 'Foo' ),
				'displayTerms' => array(
					TermIndexEntry::TYPE_DESCRIPTION => new Term( 'en', 'DisplayDescription' ),
				),
			),
			array(
				'entityId' => new ItemId( 'Q3' ),
				'matchedTermType' => 'label',
				'matchedTerm' => new Term( 'fr', 'Foo' ),
				'displayTerms' => array(
					TermIndexEntry::TYPE_LABEL => new Term( 'en', 'DisplayLabel' ),
				),
			),
		);
		$mock = $this->getMockBuilder( 'Wikibase\Lib\Interactors\TermIndexSearchInteractor' )
			->disableOriginalConstructor()
			->getMock();
		$mock->expects( $this->any() )
			->method( 'searchForEntities' )
			->with(
				$this->equalTo( 'Foo' ),
				$this->equalTo( 'fr' ),
				$this->equalTo( 'item' ),
				$this->equalTo( array( TermIndexEntry::TYPE_LABEL, TermIndexEntry::TYPE_ALIAS ) )
			)
			->will( $this->returnValue( $returnResults ) );

		$mock->expects( $this->any() )
			->method( 'setIsCaseSensitive' )
			->with( false );

		$mock->expects( $this->any() )
			->method( 'setPrefixMatch' )
			->with( false );

		$mock->expects( $this->any() )
			->method( 'setUseLanguageFallback' )
			->with( true );

		return $mock;
	}

	protected function newSpecialPage() {
		$page = new SpecialItemDisambiguation();
		$page->initServices(
			$this->getMockItemDisambiguation(),
			$this->getMockSearchInteractor()
		);
		return $page;
	}

	public function requestProvider() {
		$cases = array();
		$matchers = array();

		$matchers['language'] = array(
			'tag' => 'input',
			'attributes' => array(
				'id' => 'wb-itemdisambiguation-languagename',
				'name' => 'language',
			) );
		$matchers['label'] = array(
			'tag' => 'input',
			'attributes' => array(
				'id' => 'labelname',
				'name' => 'label',
			) );
		$matchers['submit'] = array(
			'tag' => 'input',
			'attributes' => array(
				'id' => 'wb-itembytitle-submit',
				'type' => 'submit',
				'name' => '',
			) );

		$cases['empty'] = array( '', array(), null, $matchers );

		// fr/Foo
		$matchers['language']['attributes']['value'] = 'fr';
		$matchers['label']['attributes']['value'] = 'Foo';
		$matchers['matches'] = array(
			'tag' => 'span',
			'content' => 'ItemDisambiguationHTML-2',
			'attributes' => array( 'class' => 'mock-span' ),
		);
		$cases['fr/Foo'] = array( 'fr/Foo', array(), 'en', $matchers );

		return $cases;
	}

	/**
	 * @dataProvider requestProvider
	 */
	public function testExecute( $sub, array $data, $languageCode, array $matchers ) {
		$request = new FauxRequest( $data );

		list( $output, ) = $this->executeSpecialPage( $sub, $request, $languageCode );
		foreach ( $matchers as $key => $matcher ) {
			$this->assertTag( $matcher, $output, "Failed to match html output with tag '{$key}''" );
		}
	}

}
