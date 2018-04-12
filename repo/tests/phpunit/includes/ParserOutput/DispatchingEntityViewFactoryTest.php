<?php

namespace Wikibase\Repo\Tests\ParserOutput;

use InvalidArgumentException;
use LogicException;
use OutOfBoundsException;
use PHPUnit4And6Compat;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\LanguageFallbackChain;
use Wikibase\Repo\ParserOutput\DispatchingEntityViewFactory;
use Wikibase\View\EditSectionGenerator;
use Wikibase\View\EntityTermsView;
use Wikibase\View\EntityView;

/**
 * @covers Wikibase\Repo\ParserOutput\DispatchingEntityViewFactory
 *
 * @group Wikibase
 *
 * @license GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class DispatchingEntityViewFactoryTest extends \PHPUnit\Framework\TestCase {
	use PHPUnit4And6Compat;

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testInvalidConstructorArgument() {
		new DispatchingEntityViewFactory(
			[ 'invalid' ]
		);
	}

	/**
	 * @expectedException OutOfBoundsException
	 */
	public function testUnknownEntityType() {
		$factory = new DispatchingEntityViewFactory(
			[]
		);
		$entityTermsView = $this->getMock( EntityTermsView::class );

		$factory->newEntityView(
			'unknown',
			'en',
			$this->getMock( LabelDescriptionLookup::class ),
			new LanguageFallbackChain( [] ),
			$this->getMock( EditSectionGenerator::class ),
			$entityTermsView
		);
	}

	/**
	 * @expectedException LogicException
	 */
	public function testNoEntityViewReturned() {
		$factory = new DispatchingEntityViewFactory(
			[
				'foo' => function() {
					return null;
				}
			]
		);
		$entityTermsView = $this->getMock( EntityTermsView::class );

		$factory->newEntityView(
			'foo',
			'en',
			$this->getMock( LabelDescriptionLookup::class ),
			new LanguageFallbackChain( [] ),
			$this->getMock( EditSectionGenerator::class ),
			$entityTermsView
		);
	}

	public function testNewEntityView() {
		$labelDescriptionLookup = $this->getMock( LabelDescriptionLookup::class );
		$languageFallbackChain = new LanguageFallbackChain( [] );
		$editSectionGenerator = $this->getMock( EditSectionGenerator::class );
		$entityTermsView = $this->getMock( EntityTermsView::class );
		$entityView = $this->getMockBuilder( EntityView::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$factory = new DispatchingEntityViewFactory(
			[
				'foo' => function(
					$languageCodeParam,
					LabelDescriptionLookup $labelDescriptionLookupParam,
					LanguageFallbackChain $languageFallbackChainParam,
					EditSectionGenerator $editSectionGeneratorParam,
					EntityTermsView $entityTermsViewParam
				) use(
					$labelDescriptionLookup,
					$languageFallbackChain,
					$editSectionGenerator,
					$entityTermsView,
					$entityView
				) {
					$this->assertEquals( 'en', $languageCodeParam );
					$this->assertSame( $labelDescriptionLookup, $labelDescriptionLookupParam );
					$this->assertSame( $languageFallbackChain, $languageFallbackChainParam );
					$this->assertSame( $editSectionGenerator, $editSectionGeneratorParam );
					$this->assertSame( $entityTermsView, $entityTermsViewParam );

					return $entityView;
				}
			]
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
