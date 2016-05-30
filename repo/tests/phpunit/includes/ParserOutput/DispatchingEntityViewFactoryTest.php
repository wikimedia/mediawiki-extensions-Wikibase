<?php

namespace Wikibase\Repo\Tests\ParserOutput;

use InvalidArgumentException;
use LogicException;
use OutOfBoundsException;
use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\LanguageFallbackChain;
use Wikibase\Repo\ParserOutput\DispatchingEntityViewFactory;
use Wikibase\View\EditSectionGenerator;
use Wikibase\View\EntityView;

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
	public function testInvalidConstructorArgument() {
		new DispatchingEntityViewFactory(
			array( 'invalid' )
		);
	}

	/**
	 * @expectedException OutOfBoundsException
	 */
	public function testUnknownEntityType() {
		$factory = new DispatchingEntityViewFactory(
			array()
		);

		$factory->newEntityView(
			'unknown',
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
		$factory = new DispatchingEntityViewFactory(
			array(
				'foo' => function() {
					return null;
				}
			)
		);

		$factory->newEntityView(
			'foo',
			'en',
			$this->getMock( LabelDescriptionLookup::class ),
			new LanguageFallbackChain( array() ),
			$this->getMock( EditSectionGenerator::class )
		);
	}

	public function testNewEntityView() {
		$labelDescriptionLookup = $this->getMock( LabelDescriptionLookup::class );
		$languageFallbackChain = new LanguageFallbackChain( array() );
		$editSectionGenerator = $this->getMock( EditSectionGenerator::class );
		$entityView = $this->getMockBuilder( EntityView::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$factory = new DispatchingEntityViewFactory(
			array(
				'foo' => function(
					$languageCodeParam,
					LabelDescriptionLookup $labelDescriptionLookupParam,
					LanguageFallbackChain $languageFallbackChainParam,
					EditSectionGenerator $editSectionGeneratorParam,
				) use(
					$labelDescriptionLookup,
					$languageFallbackChain,
					$editSectionGenerator,
					$entityView
				) {
					$this->assertEquals( 'en', $languageCodeParam );
					$this->assertSame( $labelDescriptionLookup, $labelDescriptionLookupParam );
					$this->assertSame( $languageFallbackChain, $languageFallbackChainParam );
					$this->assertSame( $editSectionGenerator, $editSectionGeneratorParam );

					return $entityView;
				}
			)
		);

		$newEntityView = $factory->newEntityView(
			'foo',
			'en',
			$labelDescriptionLookup,
			$languageFallbackChain,
			$editSectionGenerator,
			$entityTermsView
		);

		$this->assertSame( $entityView, $newEntityView );
	}

}
