<?php

namespace Wikibase\Lib\Tests\Store;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\TermLookupService;
use Wikibase\Lib\Store\TermsLookup;

class TermLookupTest extends \MediaWikiTestCase {

	public function testGetLabels() {
		$termsLookup = $this->getTermsLookup();
		$lookup = new TermLookupService( $termsLookup );

		$labels = $lookup->getLabels( new ItemId( 'Q116' ) );

		$expected = array(
			'en' => 'New York City',
			'es' => 'Nueva York'
		);

		$this->assertEquals( $expected, $labels );
	}

	public function testGetLabel() {
		$termsLookup = $this->getTermsLookup();
		$lookup = new TermLookupService( $termsLookup );

		$label = $lookup->getLabel( new ItemId( 'Q116' ), 'en' );

		$this->assertEquals( 'New York City', $label );
	}

	private function getTermsLookup() {
		$termsLookup = $this->getMockBuilder( 'Wikibase\Lib\Store\TermsLookup' )
			->disableOriginalConstructor()
			->getMock();

		$termsLookup->expects( $this->any() )
			->method( 'getTermsByTermType' )
			->will( $this->returnCallback( function( EntityId $entityId, $termType ) {
				if ( $termType === 'label' ) {
					return array(
						'en' => 'New York City',
						'es' => 'Nueva York'
					);
				} elseif ( $termType === 'description' ) {
					return array(
						'en' => 'Big Apple'
					);
				}
			} ) );

		return $termsLookup;
	}

}
