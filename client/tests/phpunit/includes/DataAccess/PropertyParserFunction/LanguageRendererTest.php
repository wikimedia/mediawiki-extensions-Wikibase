<?php

namespace Wikibase\DataAccess\Tests\PropertyParserFunction;

use DataValues\StringValue;
use Language;
use Wikibase\DataAccess\PropertyParserFunction\LanguageRenderer;
use Wikibase\DataAccess\PropertyParserFunction\PropertyIdResolver;
use Wikibase\DataAccess\PropertyParserFunction\SnaksFinder;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Item;
use Wikibase\Lib\PropertyLabelNotResolvedException;
use Wikibase\Property;
use Wikibase\PropertyValueSnak;

/**
 * @covers Wikibase\DataAccess\PropertyParserFunction\Renderer
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseDataAccess
 * @group PropertyParserFunctionTest
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class LanguageRendererTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @param PropertyIdResolver $propertyIdResolver
	 * @param string $languageCode
	 */
	private function getRenderer( PropertyIdResolver $propertyIdResolver, $languageCode ) {
		$targetLanguage = Language::factory( $languageCode );

		return new LanguageRenderer(
			$targetLanguage,
			$propertyIdResolver,
			$this->getSnaksFinder(),
			$this->getSnakFormatter()
		);
	}

	public function testRender() {
		$renderer = $this->getRenderer( $this->getPropertyIdResolver(), 'en' );

		$result = $renderer->render(
			new ItemId( 'Q42' ),
			'p1337'
		);

		$expected = '(a kitten), (a kitten)';
		$this->assertEquals( $expected, $result );
	}

	private function getPropertyIdResolver() {
		$propertyIdResolver = $this->getMockBuilder(
				'Wikibase\DataAccess\PropertyParserFunction\PropertyIdResolver'
			)
			->disableOriginalConstructor()
			->getMock();

		$propertyIdResolver->expects( $this->any() )
			->method( 'resolvePropertyId' )
			->will( $this->returnValue( new PropertyId( 'P1337' ) ) );

		return $propertyIdResolver;
	}

	private function getSnaksFinder() {
		$snaksFinder = $this->getMockBuilder(
				'Wikibase\DataAccess\PropertyParserFunction\SnaksFinder'
			)
			->disableOriginalConstructor()
			->getMock();

		$propertyId = new PropertyId( 'P1337' );
		$snaks = array(
			'Q42$1' => new PropertyValueSnak( $propertyId, new StringValue( 'a kitten!' ) ),
			'Q42$2' => new PropertyValueSnak( $propertyId, new StringValue( 'two kittens!!' ) )
		);

		$snaksFinder->expects( $this->any() )
			->method( 'findSnaks' )
			->will( $this->returnValue( $snaks ) );

		return $snaksFinder;
	}

	public function testRenderForPropertyNotFound() {
		$renderer = $this->getRenderer( $this->getPropertyIdResolverForPropertyNotFound(), 'qqx' );
		$result = $renderer->render( new ItemId( 'Q4' ), 'invalidLabel' );

		$this->assertRegExp(
			'/<(?:strong|span|p|div)\s(?:[^\s>]*\s+)*?class="(?:[^"\s>]*\s+)*?error(?:\s[^">]*)?"/',
			$result
		);

		$this->assertRegExp(
			'/wikibase-property-render-error.*invalidLabel.*qqx/',
			$result
		);
	}

	private function getPropertyIdResolverForPropertyNotFound() {
		$propertyIdResolver = $this->getMockBuilder(
				'Wikibase\DataAccess\PropertyParserFunction\PropertyIdResolver'
			)
			->disableOriginalConstructor()
			->getMock();

		$propertyIdResolver->expects( $this->any() )
			->method( 'resolvePropertyId' )
			->will( $this->returnCallback( function( $propertyLabelOrId, $languageCode ) {
				throw new PropertyLabelNotResolvedException( $propertyLabelOrId, $languageCode );
			} )
		);

		return $propertyIdResolver;
	}

	private function getSnakFormatter() {
		$snakFormatter = $this->getMock( 'Wikibase\Lib\SnakFormatter' );

		$snakFormatter->expects( $this->any() )
			->method( 'formatSnak' )
			->will( $this->returnValue( '(a kitten)' ) );

		return $snakFormatter;
	}

}
