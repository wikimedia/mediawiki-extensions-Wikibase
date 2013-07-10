<?php

namespace Wikibase\Test;

use Wikibase\Claim;
use Wikibase\ClaimHtmlGenerator;
use Wikibase\PropertySomeValueSnak;

/**
 * @covers Wikibase\ClaimHtmlGenerator
 *
 * @since 0.4
 *
 * @group Wikibase
 * @group WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class ClaimHtmlGeneratorTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider getHtmlForClaimProvider
	 */
	public function testGetHtmlForClaim( $pattern, $snakFormatter, $claim ) {
		$claimHtmlGenerator = new ClaimHtmlGenerator( $snakFormatter );
		$html = $claimHtmlGenerator->getHtmlForClaim( $claim, 'edit' );
		$this->assertRegExp( $pattern, $html );
	}

	public function getHtmlForClaimProvider() {
		$expected = '/a snak!/';

		$snakFormatter = $this->getMockBuilder( 'Wikibase\Lib\DispatchingSnakFormatter' )
			->disableOriginalConstructor()
			->getMock();

		$snakFormatter->expects( $this->any() )
			->method( 'formatSnak' )
			->will( $this->returnValue( 'a snak!' ) );

		$claim = new Claim( new PropertySomeValueSnak( 42 ) );

		return array(
			array( $expected, $snakFormatter, $claim )
		);
	}

}
