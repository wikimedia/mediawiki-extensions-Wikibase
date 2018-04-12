<?php

namespace Wikibase\Lib\Tests\Store;

use Language;
use PHPUnit4And6Compat;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\TermLookup;
use Wikibase\DataModel\Services\Term\TermBuffer;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookupFactory;

/**
 * @covers Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookupFactory
 *
 * @group Wikibase
 * @group WikibaseStore
 *
 * @license GPL-2.0-or-later
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class LanguageFallbackLabelDescriptionLookupFactoryTest extends \PHPUnit\Framework\TestCase {
	use PHPUnit4And6Compat;

	/**
	 * @return TermLookup
	 */
	private function getTermLookupMock() {
		$termLookup = $this->getMock( TermLookup::class );
		$termLookup->expects( $this->any() )
			->method( 'getLabel' )
			->will( $this->returnCallback( function( EntityId $id ) {
				return $id->getSerialization() . '\'s label';
			} ) );

		$termLookup->expects( $this->any() )
			->method( 'getLabels' )
			->will( $this->returnCallback( function( EntityId $id ) {
				return [ 'en' => $id->getSerialization() . '\'s label' ];
			} ) );

		return $termLookup;
	}

	/**
	 * @return TermBuffer
	 */
	private function getTermBufferMock() {
		$termBuffer = $this->getMock( TermBuffer::class );
		$termBuffer->expects( $this->once() )
			->method( 'prefetchTerms' )
			->with(
				$this->equalTo( [ new ItemId( 'Q123' ), new ItemId( 'Q456' ) ] ),
				$this->equalTo( [ 'label' ] ),
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
			[ new ItemId( 'Q123' ), new ItemId( 'Q456' ) ]
		);

		$label = $labelDescriptionLookup->getLabel( new ItemId( 'Q123' ) );

		$this->assertEquals( 'Q123\'s label', $label->getText() );
		$this->assertEquals( 'en-gb', $label->getLanguageCode() );
		$this->assertEquals( 'en', $label->getActualLanguageCode() );
	}

}
