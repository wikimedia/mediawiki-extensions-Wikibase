<?php

namespace Wikibase\Repo\Tests\ParserOutput;

use InvalidArgumentException;
use LogicException;
use OutOfBoundsException;
use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\LanguageFallbackChain;
use Wikibase\Repo\ParserOutput\EntityViewFactory;
use Wikibase\View\EditSectionGenerator;
use Wikibase\View\EntityView;
use Wikibase\View\ViewFactory;

/**
 * @covers Wikibase\Repo\ParserOutput\EntityViewFactory
 *
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class EntityViewFactoryTest extends PHPUnit_Framework_TestCase {

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testInvalidConstructorArgument() {
		new EntityViewFactory( array( 'invalid' ) );
	}

	/**
	 * @expectedException OutOfBoundsException
	 */
	public function testUnknownEntityType() {
		$factory = new EntityViewFactory( array() );
		$factory->newEntityView(
			'unknown',
			$this->getMockBuilder( ViewFactory::class )->disableOriginalConstructor()->getMock(),
			'en',
			$this->getMock( LabelDescriptionLookup::class ),
			new LanguageFallbackChain( array() ),
			$this->getMock( EditSectionGenerator::class )
		);
	}

	/**
	 * @expectedException LogicException
	 */
	public function testNoEntityViewReturned() {
		$viewFactory = $this->getMockBuilder( ViewFactory::class )->disableOriginalConstructor()->getMock();
		$labelDescriptionLookup = $this->getMock( LabelDescriptionLookup::class );
		$languageFallbackChain = new LanguageFallbackChain( array() );
		$editSectionGenerator = $this->getMock( EditSectionGenerator::class );

		$factory = new EntityViewFactory( array(
			'foo' => function() {
				return null;
			}
		) );

		$factory->newEntityView(
			'foo',
			$viewFactory,
			'en',
			$labelDescriptionLookup,
			$languageFallbackChain,
			$editSectionGenerator
		);
	}

	public function testNewEntityView() {
		$viewFactory = $this->getMockBuilder( ViewFactory::class )->disableOriginalConstructor()->getMock();
		$labelDescriptionLookup = $this->getMock( LabelDescriptionLookup::class );
		$languageFallbackChain = new LanguageFallbackChain( array() );
		$editSectionGenerator = $this->getMock( EditSectionGenerator::class );
		$entityView = $this->getMockBuilder( EntityView::class )->disableOriginalConstructor()->getMockForAbstractClass();

		$factory = new EntityViewFactory( array(
			'foo' => function(
				ViewFactory $viewFactoryParam,
				$languageCodeParam,
				LabelDescriptionLookup $labelDescriptionLookupParam,
				LanguageFallbackChain $languageFallbackChainParam,
				EditSectionGenerator $editSectionGeneratorParam
			) use ( $viewFactory, $labelDescriptionLookup, $languageFallbackChain, $editSectionGenerator, $entityView ) {
				$this->assertSame( $viewFactoryParam, $viewFactory );
				$this->assertEquals( 'en', $languageCodeParam );
				$this->assertSame( $labelDescriptionLookup, $labelDescriptionLookupParam );
				$this->assertSame( $languageFallbackChain, $languageFallbackChainParam );
				$this->assertSame( $editSectionGenerator, $editSectionGeneratorParam );

				return $entityView;
			}
		) );

		$newEntityView = $factory->newEntityView(
			'foo',
			$viewFactory,
			'en',
			$labelDescriptionLookup,
			$languageFallbackChain,
			$editSectionGenerator
		);

		$this->assertSame( $entityView, $newEntityView );
	}

}
