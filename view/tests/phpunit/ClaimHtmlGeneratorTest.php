<?php

namespace Wikibase\Test;

use DataValues\StringValue;
use Html;
use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\ReferenceList;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Lib\EntityIdFormatter;
use Wikibase\View\ClaimHtmlGenerator;
use Wikibase\View\SnakHtmlGenerator;
use Wikibase\View\Template\TemplateFactory;

/**
 * @covers Wikibase\View\ClaimHtmlGenerator
 *
 * @todo more specific tests for all parts of claim html formatting
 *
 * @group Wikibase
 * @group WikibaseView
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Daniel Kinzler
 * @author H. Snater < mediawiki@snater.com >
 */
class ClaimHtmlGeneratorTest extends PHPUnit_Framework_TestCase {

	/**
	 * @return SnakHtmlGenerator
	 */
	protected function getSnakHtmlGeneratorMock() {
		$snakHtmlGenerator = $this->getMockBuilder( 'Wikibase\View\SnakHtmlGenerator' )
			->disableOriginalConstructor()
			->getMock();

		$snakHtmlGenerator->expects( $this->any() )
			->method( 'getSnakHtml' )
			->will( $this->returnValue( 'SNAK HTML' ) );

		return $snakHtmlGenerator;
	}

	/**
	 * @return EntityIdFormatter
	 */
	protected function getPropertyIdFormatterMock() {
		$lookup = $this->getMock( 'Wikibase\Lib\EntityIdFormatter' );

		$lookup->expects( $this->any() )
			->method( 'formatEntityId' )
			->will( $this->returnCallback( function( EntityId $id ) {
				$name = $id->getEntityType() . ':' . $id->getSerialization();
				$url = 'http://wiki.acme.com/wiki/' . urlencode( $name );
				return Html::element( 'a', array( 'href' => $url ), $name );
			} ) );

		return $lookup;
	}

	/**
	 * @uses Wikibase\View\Template\Template
	 * @uses Wikibase\View\Template\TemplateFactory
	 * @uses Wikibase\View\Template\TemplateRegistry
	 * @dataProvider getHtmlForClaimProvider
	 */
	public function testGetHtmlForClaim(
		SnakHtmlGenerator $snakHtmlGenerator,
		Claim $claim,
		$patterns
	) {
		$templateFactory = TemplateFactory::getDefaultInstance();
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
		$snakHtmlGenerator = $this->getSnakHtmlGeneratorMock();

		$testCases = array();

		$testCases[] = array(
			$snakHtmlGenerator,
			new Claim( new PropertySomeValueSnak( 42 ) ),
			array(
				'snak html' => '/SNAK HTML/',
			)
		);

		$testCases[] = array(
			$snakHtmlGenerator,
			new Claim(
				new PropertySomeValueSnak( 42 ),
				new SnakList( array(
					new PropertyValueSnak( 50, new StringValue( 'second snak' ) ),
				) )
			),
			array(
				'snak html' => '/SNAK HTML.*SNAK HTML/s',
			)
		);

		$testCases[] = array(
			$snakHtmlGenerator,
			new Statement(
				new PropertyValueSnak( 50, new StringValue( 'chocolate!' ) ),
				new SnakList(),
				new ReferenceList( array( new Reference( new SnakList( array (
					new PropertyValueSnak( 50, new StringValue( 'second snak' ) )
				) ) ) ) )
			),
			array(
				'snak html' => '/SNAK HTML.*SNAK HTML/s',
			)
		);

		return $testCases;
	}

}
