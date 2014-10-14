<?php

namespace Wikibase\Lib\Tests\Store\SQL;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\Store\SQL\TermsSQLLookup;

class TermsSQLLookupTest extends \MediaWikiTestCase {

	/**
	 * @dataProvider getTermsByTermTypeProvider
	 */
	public function testGetTermsByTermType( EntityId $entityId, $termType, $expected ) {
		$lookup = $this->getTermsSQLLookup();
		$labels = $lookup->getTermsByTermType( $entityId, $termType );

		$this->assertEquals( $expected, $labels );
	}

	public function getTermsByTermTypeProvider() {
		return array(
			array(
				new ItemId( 'Q116' ),
				'label',
				array(
					'en' => 'New York City',
					'es' => 'Nueva York'
				)
			),
			array(
				new ItemId( 'Q118' ),
				'label',
				array()
			),
			array(
				new ItemId( 'Q116' ),
				'description',
				array(
					'en' => 'Big Apple'
				)
			),
			array(
				new PropertyId( 'P20' ),
				'label',
				array(
					'en' => 'capital'
				)
			),
			array(
				new ItemId( 'Q116' ),
				'kittens',
				array()
			)
		);
	}

	private function getTermsSQLLookup() {
		$termIndex = $this->getTermIndex();
		return new TermsSQLLookup( $termIndex );
	}

	private function getTermIndex() {
		$terms = array();

		$terms[] = new \Wikibase\Term( array(
			'termType' => 'label',
			'termLanguage' => 'en',
			'entityId' => 116,
			'entityType' => 'item',
			'termText' => 'New York City'
		) );

		$terms[] = new \Wikibase\Term( array(
			'termType' => 'label',
			'termLanguage' => 'es',
			'entityId' => 116,
			'entityType' => 'item',
			'termText' => 'Nueva York'
		) );

		$terms[] = new \Wikibase\Term( array(
			'termType' => 'description',
			'termLanguage' => 'en',
			'entityId' => 116,
			'entityType' => 'item',
			'termText' => 'Big Apple'
		) );

		$terms[] = new \Wikibase\Term( array(
			'termType' => 'label',
			'termLanguage' => 'en',
			'entityId' => 20,
			'entityType' => 'property',
			'termText' => 'capital'
		) );

		$termIndex = $this->getMockBuilder( 'Wikibase\TermIndex' )
			->disableOriginalConstructor()
			->getMock();

		$termIndex->expects( $this->any() )
			->method( 'getTermsOfEntity' )
			->will( $this->returnCallback( function( EntityId $entityId ) use ( $terms ) {
					$matchingTerms = array();

					foreach( $terms as $term ) {
						if ( $entityId->equals( $term->getEntityId() ) ) {
							$matchingTerms[] = $term;
						}
					}

					return $matchingTerms;
				} ) );

		return $termIndex;
	}

}
