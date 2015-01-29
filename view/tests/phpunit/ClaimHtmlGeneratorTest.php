<?php

namespace Wikibase\View\Tests;

use DataValues\StringValue;
use PHPUnit_Framework_TestCase;
use ValueFormatters\BasicNumberLocalizer;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\ReferenceList;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\View\ClaimHtmlGenerator;
use Wikibase\View\DummyLocalizedTextProvider;
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
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Daniel Kinzler
 * @author H. Snater < mediawiki@snater.com >
 */
class ClaimHtmlGeneratorTest extends PHPUnit_Framework_TestCase {

	/**
	 * @return SnakHtmlGenerator
	 */
	private function getSnakHtmlGeneratorMock() {
		$snakHtmlGenerator = $this->getMockBuilder( SnakHtmlGenerator::class )
			->disableOriginalConstructor()
			->getMock();

		$snakHtmlGenerator->expects( $this->any() )
			->method( 'getSnakHtml' )
			->will( $this->returnValue( 'SNAK HTML' ) );

		return $snakHtmlGenerator;
	}

	/**
	 * @dataProvider getHtmlForClaimProvider
	 *
	 * @uses Wikibase\View\Template\Template
	 * @uses Wikibase\View\Template\TemplateFactory
	 */
	public function testGetHtmlForClaim(
		SnakHtmlGenerator $snakHtmlGenerator,
		Statement $statement,
		$patterns
	) {
		$templateFactory = TemplateFactory::getDefaultInstance();
		$claimHtmlGenerator = new ClaimHtmlGenerator(
			$templateFactory,
			$snakHtmlGenerator,
			new BasicNumberLocalizer(),
			new DummyLocalizedTextProvider()
		);

		$html = $claimHtmlGenerator->getHtmlForClaim( $statement, 'edit' );

		foreach ( $patterns as $message => $pattern ) {
			$this->assertRegExp( $pattern, $html, $message );
		}
	}

	public function getHtmlForClaimProvider() {
		$snakHtmlGenerator = $this->getSnakHtmlGeneratorMock();

		$testCases = array();

		$testCases[] = array(
			$snakHtmlGenerator,
			new Statement( new PropertySomeValueSnak( 42 ) ),
			array(
				'snak html' => '/SNAK HTML/',
			)
		);

		$testCases[] = array(
			$snakHtmlGenerator,
			new Statement(
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
				new ReferenceList( array( new Reference( new SnakList( array(
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
