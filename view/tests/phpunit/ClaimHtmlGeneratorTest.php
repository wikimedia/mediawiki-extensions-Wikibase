<?php

namespace Wikibase\View\Tests;

use DataValues\StringValue;
use PHPUnit_Framework_TestCase;
use Prophecy\Argument;
use ValueFormatters\BasicNumberLocalizer;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\ReferenceList;
use Wikibase\DataModel\Serializers\StatementSerializer;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
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

		$snakHtmlGenerator->method( 'getSnakHtml' )
			->will( $this->returnValue( 'SNAK HTML' ) );

		return $snakHtmlGenerator;
	}

	/**
	 * @dataProvider getHtmlForClaimProvider
	 *
	 * @uses Wikibase\View\Template\Template
	 * @uses Wikibase\View\Template\TemplateFactory
	 * @uses Wikibase\View\Template\TemplateRegistry
	 */
	public function testGetHtmlForClaim(
		Statement $statement,
		$patterns
	) {
		$claimHtmlGenerator = $this->createClaimGenerator( $this->createDummyStatementSerializer() );

		$html = $claimHtmlGenerator->getHtmlForClaim( $statement, 'edit' );

		foreach ( $patterns as $message => $pattern ) {
			$this->assertRegExp( $pattern, $html, $message );
		}
	}

	public function getHtmlForClaimProvider() {
		$testCases = array();

		$testCases[] = array(
			new Statement( new PropertySomeValueSnak( 42 ) ),
			array(
				'snak html' => '/SNAK HTML/',
			)
		);

		$testCases[] = array(
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

	/**
	 * @dataProvider referencesProvider
	 */
	public function testCollapsedReferences(
		Statement $statement,
		$editSectionHtml,
		$expected
	) {
		$claimHtmlGenerator = $this->createClaimGenerator( $this->createDummyStatementSerializer() );

		$html = $claimHtmlGenerator->getHtmlForClaim( $statement, $editSectionHtml );

		$this->assertSame(
			$expected ? 1 : 0,
			substr_count( $html, 'wikibase-initially-collapsed' )
		);
	}

	public function testRendersDataAttributeWithSerializedStatement() {
		$snak = new PropertyNoValueSnak( 1 );
		$statement = new Statement( $snak );

		$statementSerializer = $this->prophesize( StatementSerializer::class );
		$statementSerializer->serialize( Argument::any() )->willReturn( 'statement-serialization' );
		$claimHtmlGenerator = $this->createClaimGenerator( $statementSerializer->reveal() );

		$output = $claimHtmlGenerator->getHtmlForClaim( $statement, '' );

		assertThat(
			$output,
			is( htmlPiece( havingChild( tagMatchingOutline( <<<'HTML'
				<div class="wikibase-statementview" data-statement='"statement-serialization"'/>
HTML
			) ) ) ) );
	}

	public function referencesProvider() {
		$snak = new PropertyNoValueSnak( 1 );
		$statement = new Statement( $snak );
		$referencedStatement = clone $statement;
		$referencedStatement->addNewReference( $snak );

		return [
			[ $statement, '', false ],
			[ $statement, '<EDIT SECTION>', false ],
			[ $referencedStatement, '', false ],
			[ $referencedStatement, '<EDIT SECTION>', true ],
		];
	}

	/**
	 * @param StatementSerializer $statementSerializer
	 * @return ClaimHtmlGenerator
	 */
	private function createClaimGenerator( StatementSerializer $statementSerializer ) {
		return new ClaimHtmlGenerator(
			TemplateFactory::getDefaultInstance(),
			$this->getSnakHtmlGeneratorMock(),
			new BasicNumberLocalizer(),
			new DummyLocalizedTextProvider(),
			$statementSerializer
		);
	}

	/**
	 * @return StatementSerializer
	 */
	private function createDummyStatementSerializer() {
		return $this->prophesize( StatementSerializer::class )->reveal();
	}

}
