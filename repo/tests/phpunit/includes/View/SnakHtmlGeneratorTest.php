<?php

namespace Wikibase\Test;

use DataValues\StringValue;
use Html;
use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\Lib\EntityIdFormatter;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Repo\View\SnakHtmlGenerator;
use Wikibase\Template\TemplateFactory;
use Wikibase\Template\TemplateRegistry;

/**
 * @covers Wikibase\Repo\View\SnakHtmlGenerator
 *
 * @group Wikibase
 * @group WikibaseRepo
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
		$snakHtmlGenerator = new SnakHtmlGenerator(
			new TemplateFactory( TemplateRegistry::getDefaultInstance() ),
			$snakFormatter,
			$propertyIdFormatter
		);

		$html = $snakHtmlGenerator->getSnakHtml( $snak );

		foreach( $patterns as $message => $pattern ) {
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
		$lookup = $this->getMockBuilder( 'Wikibase\Lib\EntityIdFormatter' )
			->disableOriginalConstructor()
			->getMock();

		$lookup->expects( $this->any() )
			->method( 'format' )
			->will( $this->returnCallback( function( EntityId $id ) {
				$name = $id->getEntityType() . ':' . $id->getSerialization();
				$url = 'http://wiki.acme.com/wiki/' . urlencode( $name );
				return Html::element( 'a', array( 'href' => $url ), $name );
			} ) );

		return $lookup;
	}

}
