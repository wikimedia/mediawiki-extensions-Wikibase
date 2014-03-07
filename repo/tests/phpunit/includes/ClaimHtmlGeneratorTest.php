<?php

namespace Wikibase\Test;

use DataValues\StringValue;
use Title;
use ValueFormatters\FormatterOptions;
use Wikibase\Claim;
use Wikibase\ClaimHtmlGenerator;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\EntityTitleLookup;
use Wikibase\Lib\DispatchingSnakFormatter;
use Wikibase\Lib\EntityIdHtmlLinkFormatter;
use Wikibase\PropertySomeValueSnak;
use Wikibase\PropertyValueSnak;
use Wikibase\Reference;
use Wikibase\ReferenceList;
use Wikibase\SnakList;
use Wikibase\Statement;
use Wikibase\View\SnakHtmlGenerator;

/**
 * @covers Wikibase\ClaimHtmlGenerator
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
	 * @return EntityTitleLookup
	 */
	protected function getEntityTitleLookupMock() {
		$lookup = $this->getMock( 'Wikibase\EntityTitleLookup' );
		$lookup->expects( $this->any() )
			->method( 'getTitleForId' )
			->will( $this->returnCallback( array( $this, 'getTitleForId' ) ) );

		return $lookup;
	}

	/**
	 * @return EntityIdHtmlLinkFormatter
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
		$patterns
	) {
		$snakHtmlGenerator = new SnakHtmlGenerator(
			$snakFormatter,
			$entityIdHtmlLinkFormatter
		);

		$claimHtmlGenerator = new ClaimHtmlGenerator(
			$snakHtmlGenerator,
			$entityIdHtmlLinkFormatter,
			$propertyInfo
		);

		$html = $claimHtmlGenerator->getHtmlForClaim( $claim, array(), 'edit' );

		foreach( $patterns as $message => $pattern ) {
			$this->assertRegExp( $pattern, $html, $message );
		}
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
			array(
				'snak variation css' => '/wb-snakview-variation-somevalue/',
				'formatted snak' => '/a snak!/'
			)
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
			array(
				'snak variation css' => '/wb-snakview-variation-somevalue/',
				'formatted snak' => '/a snak!.*a snak!/s'
			)
		);

		$testCases[] = array(
			$snakFormatter,
			$entityIdHtmlLinkFormatter,
			array(),
			new Statement(
				new PropertyValueSnak( 50, new StringValue( 'chocolate!' ) ),
				new SnakList(),
				new ReferenceList( array( new Reference( new SnakList( array (
					new PropertyValueSnak( 50, new StringValue( 'second snak' ) )
				) ) ) ) )
			),
			array(
				'snak variation css' => '/wb-snakview-variation-value/',
				'formatted snak' => '/a snak!.*a snak!/s'
			)
		);

		return $testCases;
	}

}
