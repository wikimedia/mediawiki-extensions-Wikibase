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
	public function testGetHtmlForClaim( $expected, $snakFormatter, $claim ) {
		$claimHtmlGenerator = new ClaimHtmlGenerator( $snakFormatter );
		$html = $claimHtmlGenerator->getHtmlForClaim( $claim, 'edit' );
		$this->assertEquals( $expected, $html );
	}

	public function getHtmlForClaimProvider() {
		$expected = '<div class="wb-statement wb-statementview ">
<div class="wb-statement-claim">
<div class="wb-claim wb-claim-">
<div class="wb-claim-mainsnak" dir="auto">
<div class="wb-snak wb-mainsnak">
<div class="wb-snak-property-container">
<div class="wb-snak-property" dir="auto"></div>
</div>
<div class="wb-snak-value-container" dir="auto">
<div class="wb-snak-typeselector"></div>
<div class="wb-snak-value">a snak!</div>
</div>
</div> <!-- wb-snak (Main Snak) -->
</div>
<div class="wb-claim-qualifiers wb-statement-qualifiers"></div>
</div>
edit
</div>
<div class="wb-statement-references-container">
<div class="wb-statement-references-heading"></div>
<div class="wb-statement-references"> <!-- [0,*] wb-referenceview --></div>
</div>
</div>';

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
