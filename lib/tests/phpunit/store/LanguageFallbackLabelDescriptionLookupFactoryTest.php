<?php

namespace Wikibase\Test;

use Language;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookupFactory;

/**
 * @covers Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookupFactory
 *
 * @group Wikibase
 * @group WikibaseLib
 * @group WikibaseStore
 *
 * @license GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class LanguageFallbackLabelDescriptionLookupFactoryTest extends \PHPUnit_Framework_TestCase {

	private function getTermLookupMock() {
		$termLookup = $this->getMock( 'Wikibase\DataModel\Services\Lookup\TermLookup' );
		$termLookup->expects( $this->any() )
			->method( 'getLabel' )
			->will( $this->returnCallback( function( EntityId $id ) {
				return $id->getSerialization() . '\'s label';
			} ) );

		$termLookup->expects( $this->any() )
			->method( 'getLabels' )
			->will( $this->returnCallback( function( EntityId $id ) {
				return array( 'en' => $id->getSerialization() . '\'s label' );
			} ) );

		return $termLookup;
	}

	private function getTermBufferMock() {
		$termBuffer = $this->getMock( 'Wikibase\DataModel\Services\Term\TermBuffer' );
		$termBuffer->expects( $this->once() )
			->method( 'prefetchTerms' )
			->with(
				$this->equalTo( array( new ItemId( 'Q123' ), new ItemId( 'Q456' ) ) ),
				$this->equalTo( array( 'label' ) ),
				$this->anything()
			);

		return $termBuffer;
	}

	public function testNewLabelDescriptionLookup() {
		$factory = new LanguageFallbackLabelDescriptionLookupFactory(
			new LanguageFallbackChainFactory(),
			$this->getTermLookupMock(),
			$this->getTermBufferMock()
		);

		$labelDescriptionLookup = $factory->newLabelDescriptionLookup(
			Language::factory( 'en-gb' ),
			array( new ItemId( 'Q123' ), new ItemId( 'Q456' ) )
		);

		$label = $labelDescriptionLookup->getLabel( new ItemId( 'Q123' ) );

		$this->assertEquals( 'Q123\'s label', $label->getText() );
		$this->assertEquals( 'en-gb', $label->getLanguageCode() );
		$this->assertEquals( 'en', $label->getActualLanguageCode() );
	}

}
