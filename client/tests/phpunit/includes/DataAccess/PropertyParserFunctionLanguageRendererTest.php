<?php

namespace Wikibase\Test;

use InvalidArgumentException;
use Language;
use Status;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataAccess\PropertyParserFunctionLanguageRenderer;
use Wikibase\Lib\PropertyLabelNotResolvedException;

/**
 * @covers Wikibase\DataAccess\PropertyParserFunctionLanguageRenderer
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group PropertyParserFunctionTest
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@wiki.gmail.com>
 */
class PropertyParserFunctionLanguageRendererTest extends \PHPUnit_Framework_TestCase {

	public function testRender() {
		$languageRenderer = new PropertyParserFunctionLanguageRenderer(
			$this->getEntityRendererForId(),
			Language::factory( 'es' )
		);

		$result = $languageRenderer->render( new ItemId( 'Q3' ), 'gato' );
		$this->assertEquals( 'meow!', $result );
	}

	private function getEntityRenderer() {
		$entityRenderer = $this->getMockBuilder(
				'Wikibase\DataAccess\PropertyParserFunctionEntityRenderer'
			)
			->disableOriginalConstructor()
			->getMock();

		return $entityRenderer;
	}

	private function getEntityRendererForId() {
		$entityRenderer = $this->getEntityRenderer();

		$entityRenderer->expects( $this->any() )
			->method( 'renderForEntityId' )
			->will( $this->returnValue( Status::newGood( 'meow!' ) ) );

		return $entityRenderer;
	}

	public function testRenderForPropertyNotFound() {
		$languageRenderer = new PropertyParserFunctionLanguageRenderer(
			$this->getEntityRendererForPropertyNotFound(),
			Language::factory( 'qqx' )
		);

		$result = $languageRenderer->render( new ItemId( 'Q4' ), 'gato' );

		$this->assertRegExp(
			'/<(?:strong|span|p|div)\s(?:[^\s>]*\s+)*?class="(?:[^"\s>]*\s+)*?error(?:\s[^">]*)?"/',
			$result
		);

		$this->assertRegExp(
			'/wikibase-property-render-error.*invalidLabel.*qqx/',
			$result
		);
	}

	private function getEntityRendererForPropertyNotFound() {
		$entityRenderer = $this->getEntityRenderer();

		$entityRenderer->expects( $this->any() )
			->method( 'renderForEntityId' )
			->will( $this->returnCallback( function() {
				throw new PropertyLabelNotResolvedException( 'invalidLabel', 'qqx' );
			} ) );

		return $entityRenderer;
	}

}
