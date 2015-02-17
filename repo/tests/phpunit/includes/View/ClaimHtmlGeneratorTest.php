<?php

namespace Wikibase\Test;

use DataValues\StringValue;
use Html;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\ReferenceList;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Lib\EntityIdFormatter;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Repo\View\ClaimHtmlGenerator;
use Wikibase\Repo\View\SnakHtmlGenerator;
use Wikibase\Template\TemplateFactory;
use Wikibase\Template\TemplateRegistry;

/**
 * @covers Wikibase\Repo\View\ClaimHtmlGenerator
 *
 * @todo more specific tests for all parts of claim html formatting,
 * and use mock SnakHtmlGenerator
 *
 * @group Wikibase
 * @group WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Daniel Kinzler
 * @author H. Snater < mediawiki@snater.com >
 */
class ClaimHtmlGeneratorTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @return SnakFormatter
	 */
	protected function getSnakFormatterMock() {
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
	protected function getPropertyIdFormatterMock() {
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

	/**
	 * @uses Wikibase\Repo\View\SnakHtmlGenerator
	 * @uses Wikibase\Template\Template
	 * @uses Wikibase\Template\TemplateFactory
	 * @uses Wikibase\Template\TemplateRegistry
	 * @dataProvider getHtmlForClaimProvider
	 */
	public function testGetHtmlForClaim(
		SnakFormatter $snakFormatter,
		EntityIdFormatter $propertyIdFormatter,
		Claim $claim,
		$patterns
	) {
		$templateFactory = new TemplateFactory(
			TemplateRegistry::getDefaultInstance()
		);

		$snakHtmlGenerator = new SnakHtmlGenerator(
			$templateFactory,
			$snakFormatter,
			$propertyIdFormatter
		);

		$claimHtmlGenerator = new ClaimHtmlGenerator(
			$templateFactory,
			$snakHtmlGenerator
		);

		$html = $claimHtmlGenerator->getHtmlForClaim( $claim, 'edit' );

		foreach( $patterns as $message => $pattern ) {
			$this->assertRegExp( $pattern, $html, $message );
		}
	}

	public function getHtmlForClaimProvider() {
		$snakFormatter = $this->getSnakFormatterMock();

		$propertyIdFormatterMock = $this->getPropertyIdFormatterMock();

		$testCases = array();

		$testCases[] = array(
			$snakFormatter,
			$propertyIdFormatterMock,
			new Claim( new PropertySomeValueSnak( 42 ) ),
			array(
				'snak variation css' => '/wikibase-snakview-variation-somevalue/',
				'formatted snak' => '/a snak!/'
			)
		);

		$testCases[] = array(
			$snakFormatter,
			$propertyIdFormatterMock,
			new Claim(
				new PropertySomeValueSnak( 42 ),
				new SnakList( array(
					new PropertyValueSnak( 50, new StringValue( 'second snak' ) ),
				) )
			),
			array(
				'snak variation css' => '/wikibase-snakview-variation-somevalue/',
				'formatted snak' => '/a snak!.*a snak!/s'
			)
		);

		$testCases[] = array(
			$snakFormatter,
			$propertyIdFormatterMock,
			new Statement(
				new Claim(
					new PropertyValueSnak( 50, new StringValue( 'chocolate!' ) ),
					new SnakList()
				),
				new ReferenceList( array( new Reference( new SnakList( array (
					new PropertyValueSnak( 50, new StringValue( 'second snak' ) )
				) ) ) ) )
			),
			array(
				'snak variation css' => '/wikibase-snakview-variation-value/',
				'formatted snak' => '/a snak!.*a snak!/s'
			)
		);

		return $testCases;
	}

}
