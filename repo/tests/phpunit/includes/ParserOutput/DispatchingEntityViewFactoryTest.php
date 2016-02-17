<?php

namespace Wikibase\Repo\Tests\ParserOutput;

use InvalidArgumentException;
use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\LanguageFallbackChain;
use Wikibase\Repo\ParserOutput\DispatchingEntityViewFactory;
use Wikibase\View\EditSectionGenerator;
use Wikibase\View\ItemView;
use Wikibase\View\PropertyView;
use Wikibase\View\ViewFactory;

/**
 * @covers Wikibase\Repo\ParserOutput\DispatchingEntityViewFactory
 *
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class DispatchingEntityViewFactoryTest extends PHPUnit_Framework_TestCase {

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testUnknownEntityType() {
		$factory = new DispatchingEntityViewFactory(
			$this->getMockBuilder( ViewFactory::class )
				->disableOriginalConstructor()
				->getMock()
		);

		$factory->newEntityView(
			'unknown',
			'en',
			$this->getMock( LabelDescriptionLookup::class ),
			new LanguageFallbackChain( array() ),
			$this->getMock( EditSectionGenerator::class )
		);
	}

	public function testNewItemView() {
		$labelDescriptionLookup = $this->getMock( LabelDescriptionLookup::class );
		$languageFallbackChain = new LanguageFallbackChain( array() );
		$editSectionGenerator = $this->getMock( EditSectionGenerator::class );
		$itemView = $this->getMockBuilder( ItemView::class )
			->disableOriginalConstructor()->getMockForAbstractClass();

		$viewFactory = $this->getMockBuilder( ViewFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$viewFactory->expects( $this->once() )
			->method( 'newItemView' )
			->with(
				$this->equalTo( 'en' ),
				$this->identicalTo( $labelDescriptionLookup ),
				$this->identicalTo( $languageFallbackChain ),
				$this->identicalTo( $editSectionGenerator )
			)
			->will( $this->returnValue( $itemView ) );

		$factory = new DispatchingEntityViewFactory( $viewFactory );

		$actual = $factory->newEntityView(
			'item',
			'en',
			$labelDescriptionLookup,
			$languageFallbackChain,
			$editSectionGenerator,
			$itemView
		);

		$this->assertSame( $itemView, $actual );
	}

	public function testNewPropertyView() {
		$labelDescriptionLookup = $this->getMock( LabelDescriptionLookup::class );
		$languageFallbackChain = new LanguageFallbackChain( array() );
		$editSectionGenerator = $this->getMock( EditSectionGenerator::class );
		$propertyView = $this->getMockBuilder( PropertyView::class )
			->disableOriginalConstructor()->getMockForAbstractClass();

		$viewFactory = $this->getMockBuilder( ViewFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$viewFactory->expects( $this->once() )
			->method( 'newPropertyView' )
			->with(
				$this->equalTo( 'en' ),
				$this->identicalTo( $labelDescriptionLookup ),
				$this->identicalTo( $languageFallbackChain ),
				$this->identicalTo( $editSectionGenerator )
			)
			->will( $this->returnValue( $propertyView ) );

		$factory = new DispatchingEntityViewFactory( $viewFactory );

		$actual = $factory->newEntityView(
			'property',
			'en',
			$labelDescriptionLookup,
			$languageFallbackChain,
			$editSectionGenerator,
			$propertyView
		);

		$this->assertSame( $propertyView, $actual );
	}

}
