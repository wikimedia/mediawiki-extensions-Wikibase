<?php

namespace Wikibase\Test;

use Wikibase\Client\Usage\UsageAccumulator;
use Wikibase\DataAccess\PropertyParserFunction\VariantsAwareRenderer;
use Wikibase\DataModel\Entity\ItemId;

/**
 * @covers Wikibase\DataAccess\PropertyParserFunction\VariantsAwareRenderer
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group PropertyParserFunctionTest
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class VariantsAwareRendererTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @return UsageAccumulator
	 */
	private function getUsageAccumulator() {
		$mock = $this->getMockBuilder( 'Wikibase\Client\Usage\UsageAccumulator' )
			->disableOriginalConstructor()
			->getMock();

		$mock->expects( $this->any() )
			->method( 'addLabelUsage' );

		$mock->expects( $this->never() )
			->method( 'addAllUsage' );

		$mock->expects( $this->never() )
			->method( 'addSiteLinksUsage' );

		return $mock;
	}

	/**
	 * @dataProvider renderProvider
	 */
	public function testRender( $expected, $itemId, $variants, $propertyLabel ) {
		$languageRenderer = $this->getLanguageAwareRenderer();

		$rendererFactory = $this->getMockBuilder(
				'Wikibase\DataAccess\PropertyParserFunction\RendererFactory'
			)
			->disableOriginalConstructor()
			->getMock();

		$rendererFactory->expects( $this->any() )
			->method( 'newLanguageAwareRenderer' )
			->will( $this->returnValue( $languageRenderer ) );

		$usageAccumulator = $this->getUsageAccumulator();

		$variantsRenderer = new VariantsAwareRenderer(
			$rendererFactory,
			$variants,
			$usageAccumulator
		);

		$result = $variantsRenderer->render( $itemId, $propertyLabel );

		$this->assertEquals( $expected, $result );
	}

	public function renderProvider() {
		$itemId = new ItemId( 'Q3' );

		return array(
			array(
				'-{zh:mooooo;zh-hans:mooooo;zh-hant:mooooo;zh-cn:mooooo;zh-hk:mooooo;}-',
				$itemId,
				array ( 'zh', 'zh-hans', 'zh-hant', 'zh-cn', 'zh-hk' ),
				'cat'
			),
			// Don't create "-{}-" for empty input,
			// to keep the ability to check a missing property with {{#if: }}.
			array(
				'',
				$itemId,
				array(),
				'cat'
			)
		);
	}

	private function getLanguageAwareRenderer() {
		$languageRenderer = $this->getMockBuilder(
			'Wikibase\DataAccess\PropertyParserFunction\Renderer'
		)
		->disableOriginalConstructor()
		->getMock();

		$languageRenderer->expects( $this->any() )
			->method( 'render' )
			->will( $this->returnValue( 'mooooo' ) );

		return $languageRenderer;
	}

}
