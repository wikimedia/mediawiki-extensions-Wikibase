<?php

namespace Wikibase\Test;

use DataValues\StringValue;
use Title;
use Wikibase\ClaimHtmlGenerator;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\ReferenceList;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Lib\DispatchingSnakFormatter;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\View\SnakHtmlGenerator;

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

		$snakFormatter->expects( $this->any() )
			->method( 'getFormat' )
			->will( $this->returnValue( SnakFormatter::FORMAT_HTML ) );

		return $snakFormatter;
	}

	/**
	 * @param EntityId $id
	 * @return Title
	 */
	public function getTitleForId( EntityId $id ) {
		$name = $id->getEntityType() . ':' . $id->getSerialization();
		return Title::makeTitle( NS_MAIN, $name );
	}

	/**
	 * @return EntityTitleLookup
	 */
	protected function getEntityTitleLookupMock() {
		$lookup = $this->getMock( 'Wikibase\Lib\Store\EntityTitleLookup' );
		$lookup->expects( $this->any() )
			->method( 'getTitleForId' )
			->will( $this->returnCallback( array( $this, 'getTitleForId' ) ) );

		return $lookup;
	}

	/**
	 * @dataProvider getHtmlForClaimProvider
	 */
	public function testGetHtmlForClaim(
		$snakFormatter,
		$entityTitleLookup,
		$claim,
		$patterns
	) {
		$snakHtmlGenerator = new SnakHtmlGenerator(
			$snakFormatter,
			$entityTitleLookup
		);

		$claimHtmlGenerator = new ClaimHtmlGenerator(
			$snakHtmlGenerator,
			$entityTitleLookup
		);

		$html = $claimHtmlGenerator->getHtmlForClaim( $claim, array(), 'edit' );

		foreach( $patterns as $message => $pattern ) {
			$this->assertRegExp( $pattern, $html, $message );
		}
	}

	public function getHtmlForClaimProvider() {
		$snakFormatter = $this->getSnakFormatterMock();

		$entityTitleLookupMock = $this->getEntityTitleLookupMock();

		$testCases = array();

		$testCases[] = array(
			$snakFormatter,
			$entityTitleLookupMock,
			new Claim( new PropertySomeValueSnak( 42 ) ),
			array(
				'snak variation css' => '/wb-snakview-variation-somevalue/',
				'formatted snak' => '/a snak!/'
			)
		);

		$testCases[] = array(
			$snakFormatter,
			$entityTitleLookupMock,
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
			$entityTitleLookupMock,
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
				'snak variation css' => '/wb-snakview-variation-value/',
				'formatted snak' => '/a snak!.*a snak!/s'
			)
		);

		return $testCases;
	}

}
