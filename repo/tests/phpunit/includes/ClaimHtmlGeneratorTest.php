<?php

namespace Wikibase\Test;

use DataValues\StringValue;
use Title;
use ValueFormatters\FormatterOptions;
use Wikibase\Claim;
use Wikibase\ClaimHtmlGenerator;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\DispatchingSnakFormatter;
use Wikibase\PropertySomeValueSnak;
use Wikibase\PropertyValueSnak;
use Wikibase\Reference;
use Wikibase\ReferenceList;
use Wikibase\SnakList;
use Wikibase\Statement;

/**
 * @covers Wikibase\ClaimHtmlGenerator
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
	 * @return DispatchingSnakFormatter
	 */
	protected function getSnakFormatterMock() {
		$snakFormatter = $this->getMockBuilder( 'Wikibase\Lib\DispatchingSnakFormatter' )
			->disableOriginalConstructor()
			->getMock();

		$snakFormatter->expects( $this->any() )
			->method( 'formatSnak' )
			->will( $this->returnValue( 'a snak!' ) );

		return $snakFormatter;
	}

	/**
	 * @param EntityId $id
	 * @return Title
	 */
	public function getTitleForId( EntityId $id ) {
		$name = $id->getEntityType() . ':' . $id->getPrefixedId();
		return Title::makeTitle( NS_MAIN, $name );
	}

	/**
	 * @return \Wikibase\EntityTitleLookup
	 */
	protected function getEntityTitleLookupMock() {
		$lookup = $this->getMock( 'Wikibase\EntityTitleLookup' );
		$lookup->expects( $this->any() )
			->method( 'getTitleForId' )
			->will( $this->returnCallback( array( $this, 'getTitleForId' ) ) );

		return $lookup;
	}

	/**
	 * @return \Wikibase\Lib\EntityIdHtmlLinkFormatter
	 */
	protected function getEntityIdHtmlLinkFormatterMock() {
		$formatter = $this->getMockBuilder( 'Wikibase\Lib\EntityIdHtmlLinkFormatter' )
			->disableOriginalConstructor()
			->getMock();

		return $formatter;
	}

	/**
	 * @dataProvider getHtmlForClaimProvider
	 */
	public function testGetHtmlForClaim(
		$snakFormatter,
		$entityIdHtmlLinkFormatter,
		$propertyInfo,
		$claim,
		$pattern
	) {
		$claimHtmlGenerator = new ClaimHtmlGenerator(
			$snakFormatter,
			$entityIdHtmlLinkFormatter,
			$propertyInfo
		);
		$html = $claimHtmlGenerator->getHtmlForClaim( $claim, 'edit' );
		$this->assertRegExp( $pattern, $html );
	}

	public function getHtmlForClaimProvider() {
		$snakFormatter = $this->getSnakFormatterMock();

		$entityIdHtmlLinkFormatter = $this->getEntityIdHtmlLinkFormatterMock();

		$testCases = array();

		$testCases[] = array(
			$snakFormatter,
			$entityIdHtmlLinkFormatter,
			array(),
			new Claim( new PropertySomeValueSnak( 42 ) ),
			'/a snak!/'
		);

		$testCases[] = array(
			$snakFormatter,
			$entityIdHtmlLinkFormatter,
			array(),
			new Claim(
				new PropertySomeValueSnak( 42 ),
				new SnakList( array(
					new PropertyValueSnak( 50, new StringValue( 'second snak' ) ),
				) )
			),
			'/a snak!.*a snak!/s'
		);

		$testCases[] = array(
			$snakFormatter,
			$entityIdHtmlLinkFormatter,
			array(),
			new Statement(
				new PropertySomeValueSnak( 42 ),
				new SnakList(),
				new ReferenceList( array( new Reference( new SnakList( array (
					new PropertyValueSnak( 50, new StringValue( 'second snak' ) )
				) ) ) ) )
			),
			'/a snak!.*a snak!/s'
		);

		return $testCases;
	}

}
