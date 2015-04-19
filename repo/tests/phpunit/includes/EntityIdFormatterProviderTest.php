<?php

namespace Wikibase\Test;

use Language;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Repo\EntityIdFormatterProvider;
use Wikibase\Repo\EntityIdLabelFormatterFactory;

/**
 * @covers Wikibase\Repo\EntityIdFormatterProvider
 *
 * @license GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class EntityIdFormatterProviderTest extends \PHPUnit_Framework_TestCase {

	private function getTermLookupMock() {
		$termLookup = $this->getMock( 'Wikibase\Lib\Store\TermLookup' );
		$termLookup->expects( $this->any() )
			->method( 'getLabel' )
			->will( $this->returnCallback( function( EntityId $entityId ) {
				return $entityId->getSerialization() . '\'s label';
			} ) );

		$termLookup->expects( $this->any() )
			->method( 'getLabels' )
			->will( $this->returnCallback( function( EntityId $entityId ) {
				return array( 'en' => $entityId->getSerialization() . '\'s label' );
			} ) );

		return $termLookup;
	}

	private function getTermBufferMock() {
		$termBuffer = $this->getMock( 'Wikibase\Store\TermBuffer' );
		$termBuffer->expects( $this->once() )
			->method( 'prefetchTerms' )
			->with(
				$this->equalTo( array( new ItemId( 'Q123' ), new ItemId( 'Q456' ) ) ),
				$this->equalTo( array( 'label' ) ),
				$this->anything()
			);

		return $termBuffer;
	}

	public function testNewFormatter() {
		$formatterProvider = new EntityIdFormatterProvider(
			new LanguageFallbackChainFactory(),
			$this->getTermLookupMock(),
			$this->getTermBufferMock()
		);

		$formatter = $formatterProvider->newFormatter(
			Language::factory( 'en' ),
			array( new ItemId( 'Q123' ), new ItemId( 'Q456' ) ),
			new EntityIdLabelFormatterFactory()
		);

		$this->assertEquals( 'Q123\'s label', $formatter->formatEntityId( new ItemId( 'Q123' ) ) );
		$this->assertEquals( 'Q456\'s label', $formatter->formatEntityId( new ItemId( 'Q456' ) ) );
	}
	
}

