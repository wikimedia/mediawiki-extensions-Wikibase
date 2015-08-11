<?php

namespace Wikibase\Test;

use DataValues\StringValue;
use Html;
use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\Lib\SnakFormatter;
use Wikibase\View\SnakHtmlGenerator;
use Wikibase\View\Template\TemplateFactory;

/**
 * @covers Wikibase\View\SnakHtmlGenerator
 *
 * @uses Wikibase\View\Template\Template
 * @uses Wikibase\View\Template\TemplateFactory
 * @uses Wikibase\View\Template\TemplateRegistry
 *
 * @group Wikibase
 * @group WikibaseView
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class SnakHtmlGeneratorTest extends PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider getSnakHtmlProvider
	 */
	public function testGetSnakHtml(
		SnakFormatter $snakFormatter,
		EntityIdFormatter $propertyIdFormatter,
		Snak $snak,
		$patterns
	) {
		$templateFactory = TemplateFactory::getDefaultInstance();
		$snakHtmlGenerator = new SnakHtmlGenerator(
			$templateFactory,
			$snakFormatter,
			$propertyIdFormatter
		);

		$html = $snakHtmlGenerator->getSnakHtml( $snak );

		foreach ( $patterns as $message => $pattern ) {
			$this->assertRegExp( $pattern, $html, $message );
		}
	}

	public function getSnakHtmlProvider() {
		$snakFormatter = $this->getSnakFormatter();

		$propertyIdFormatter = $this->getEntityIdFormatter();

		$testCases = array();

		$testCases[] = array(
			$snakFormatter,
			$propertyIdFormatter,
			new PropertySomeValueSnak( 42 ),
			array(
				'snak variation css' => '/wikibase-snakview-variation-somevalue/',
				'formatted snak' => '/a snak!/'
			)
		);

		$testCases[] = array(
			$snakFormatter,
			$propertyIdFormatter,
			new PropertySomeValueSnak( 42 ),
			array(
				'snak variation css' => '/wikibase-snakview-variation-somevalue/',
				'formatted snak' => '/a snak!/s'
			)
		);

		$testCases[] = array(
			$snakFormatter,
			$propertyIdFormatter,
			new PropertyValueSnak( 50, new StringValue( 'chocolate!' ) ),
			array(
				'snak variation css' => '/wikibase-snakview-variation-value/',
				'formatted snak' => '/a snak!/s'
			)
		);

		return $testCases;
	}

	/**
	 * @return SnakFormatter
	 */
	private function getSnakFormatter() {
		$snakFormatter = $this->getMock( 'Wikibase\Lib\SnakFormatter' );

		$snakFormatter->expects( $this->any() )
			->method( 'formatSnak' )
			->will( $this->returnValue( 'a snak!' ) );

		$snakFormatter->expects( $this->any() )
			->method( 'getFormat' )
			->will( $this->returnValue( SnakFormatter::FORMAT_HTML ) );

		return $snakFormatter;
	}

	/**
	 * @return EntityIdFormatter
	 */
	private function getEntityIdFormatter() {
		$lookup = $this->getMock( 'Wikibase\DataModel\Services\EntityId\EntityIdFormatter' );

		$lookup->expects( $this->any() )
			->method( 'formatEntityId' )
			->will( $this->returnCallback( function( EntityId $id ) {
				$name = $id->getEntityType() . ':' . $id->getSerialization();
				$url = 'http://wiki.acme.com/wiki/' . urlencode( $name );
				return Html::element( 'a', array( 'href' => $url ), $name );
			} ) );

		return $lookup;
	}

}
