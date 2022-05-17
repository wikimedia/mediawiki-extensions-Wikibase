<?php

namespace Wikibase\Lib\Tests\Store;

use Language;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\TermLookup;
use Wikibase\DataModel\Services\Term\TermBuffer;
use Wikibase\Lib\LanguageFallbackChainFactory;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookupFactory;

/**
 * @covers \Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookupFactory
 *
 * @group Wikibase
 * @group WikibaseStore
 *
 * @license GPL-2.0-or-later
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class LanguageFallbackLabelDescriptionLookupFactoryTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @return TermLookup
	 */
	private function getTermLookupMock() {
		$termLookup = $this->createMock( TermLookup::class );
		$termLookup->method( 'getLabel' )
			->willReturnCallback( function( EntityId $id ) {
				return $id->getSerialization() . '\'s label';
			} );

		$termLookup->method( 'getLabels' )
			->willReturnCallback( function( EntityId $id ) {
				return [ 'en' => $id->getSerialization() . '\'s label' ];
			} );

		return $termLookup;
	}

	/**
	 * @return TermBuffer
	 */
	private function getTermBufferMock() {
		$termBuffer = $this->createMock( TermBuffer::class );
		$termBuffer->expects( $this->once() )
			->method( 'prefetchTerms' )
			->with(
				[ new ItemId( 'Q123' ), new ItemId( 'Q456' ) ],
				[ 'label' ],
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
