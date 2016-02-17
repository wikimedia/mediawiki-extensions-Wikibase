<?php

namespace Wikibase\View\Tests;

use PHPUnit_Framework_TestCase;
use Wikibase\LanguageFallbackChain;
use Wikibase\View\ViewFactory;

/**
 * @covers Wikibase\View\ViewFactory
 *
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class ViewFactoryTest extends PHPUnit_Framework_TestCase {

	private function newViewFactory( $methodName, $paramSelection = array( 0, 1, 2, 3 ) ) {
		$labelDescriptionLookup = $this->getMock( 'Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup' );
		$fallbackChain = new LanguageFallbackChain( array() );
		$editSectionGenerator = $this->getMock( 'Wikibase\View\EditSectionGenerator' );

		$params = array(
			$this->equalTo( 'en' ),
			$this->identicalTo( $labelDescriptionLookup ),
			$this->identicalTo( $fallbackChain ),
			$this->identicalTo( $editSectionGenerator )
		);

		$basicViewFactory = $this->getMockBuilder( 'Wikibase\View\BasicViewFactory' )
			->disableOriginalConstructor()
			->getMock();

		$method = $basicViewFactory->expects( $this->once() )
			->method( $methodName )
			->will( $this->returnValue( 'foo' ) );

		call_user_func_array(
			array( $method, 'with' ),
			array_intersect_key( $params, array_flip( $paramSelection ) )
		);

		return new ViewFactory(
			$basicViewFactory,
			'en',
			$labelDescriptionLookup,
			$fallbackChain,
			$editSectionGenerator
		);
	}

	public function testNewItemView() {
		$viewFactory = $this->newViewFactory( 'newItemView' );

		$this->assertEquals( 'foo', $viewFactory->newItemView() );
	}

	public function testNewPropertyView() {
		$viewFactory = $this->newViewFactory( 'newPropertyView' );

		$this->assertEquals( 'foo', $viewFactory->newPropertyView() );
	}

	public function testNewStatementSectionsView() {
		$viewFactory = $this->newViewFactory( 'newStatementSectionsView' );

		$this->assertEquals( 'foo', $viewFactory->newStatementsSectionView() );
	}

	public function testNewEntityTermsView() {
		$viewFactory = $this->newViewFactory( 'newEntityTermsView', array( 0, 3 ) );

		$this->assertEquals( 'foo', $viewFactory->newEntityTermsView() );
	}

}
