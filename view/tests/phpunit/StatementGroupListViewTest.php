<?php

namespace Wikibase\View\Tests;

use DataValues\StringValue;
use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\View\ClaimHtmlGenerator;
use Wikibase\View\EditSectionGenerator;
use Wikibase\View\StatementGroupListView;
use Wikibase\View\Template\TemplateFactory;

/**
 * @covers Wikibase\View\StatementGroupListView
 *
 * @uses Wikibase\View\Template\Template
 * @uses Wikibase\View\Template\TemplateFactory
 *
 * @group Wikibase
 * @group WikibaseView
 *
 * @license GPL-2.0+
 * @author Bene* < benestar.wikimedia@gmail.com >
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class StatementGroupListViewTest extends PHPUnit_Framework_TestCase {

	public function testGetHtml() {
		$propertyId = new PropertyId( 'P77' );
		$statements = $this->makeStatements( $propertyId );

		$propertyIdFormatter = $this->getEntityIdFormatter();

		$statementGroupListView = $this->newStatementGroupListView( $propertyIdFormatter );

		$html = $statementGroupListView->getHtml( $statements );

		$this->assertContains( 'id="P77', $html );
		$this->assertContains( '<PROPERTY><ID></PROPERTY>', $html );
		foreach ( $statements as $statement ) {
			$this->assertContains( $statement->getGuid(), $html );
		}
		$this->assertContains( '<TOOLBAR></TOOLBAR>', $html );
	}

	/**
	 * @param PropertyId $propertyId
	 *
	 * @return Statement[]
	 */
	private function makeStatements( PropertyId $propertyId ) {
		return array(
			$this->makeStatement( new PropertyNoValueSnak(
				$propertyId
			) ),
			$this->makeStatement( new PropertyValueSnak(
				$propertyId,
				new EntityIdValue( new ItemId( 'Q22' ) )
			) ),
			$this->makeStatement( new PropertyValueSnak(
				$propertyId,
				new StringValue( 'test' )
			) ),
			$this->makeStatement( new PropertyValueSnak(
				$propertyId,
				new StringValue( 'File:Image.jpg' )
			) ),
			$this->makeStatement( new PropertySomeValueSnak(
				$propertyId
			) ),
			$this->makeStatement( new PropertyValueSnak(
				$propertyId,
				new EntityIdValue( new ItemId( 'Q555' ) )
			) ),
		);
	}

	/**
	 * @param Snak $mainSnak
	 *
	 * @return Statement
	 */
	private function makeStatement( Snak $mainSnak ) {
		static $guidCounter = 0;

		$guidCounter++;

		$statement = new Statement( $mainSnak );
		$statement->setGuid( 'EntityViewTest$' . $guidCounter );

		return $statement;
	}

	/**
	 * @param EntityIdFormatter $propertyIdFormatter
	 *
	 * @return StatementGroupListView
	 */
	private function newStatementGroupListView( EntityIdFormatter $propertyIdFormatter ) {
		$templateFactory = new TemplateFactory( [
			'wikibase-statementgrouplistview' => '<SGLIST>$1</SGLIST>',
			'wikibase-listview' => '<LIST>$1</LIST>',
			'wikibase-statementgroupview' => '<SGROUP id="$3"><PROPERTY>$1</PROPERTY>$2</SGROUP>',
			'wikibase-statementlistview' => '<SLIST>$1<TOOLBAR>$2</TOOLBAR></SLIST>',
		] );

		return new StatementGroupListView(
			$templateFactory,
			$propertyIdFormatter,
			$this->getMock( EditSectionGenerator::class ),
			$this->getClaimHtmlGenerator()
		);
	}

	/**
	 * @return ClaimHtmlGenerator
	 */
	private function getClaimHtmlGenerator() {
		$claimHtmlGenerator = $this->getMockBuilder( ClaimHtmlGenerator::class )
			->disableOriginalConstructor()
			->getMock();

		$claimHtmlGenerator->expects( $this->any() )
			->method( 'getHtmlForClaim' )
			->will( $this->returnCallback( function( Statement $statement, $editSectionHtml = null ) {
				return $statement->getGuid() . "\n";
			} ) );

		return $claimHtmlGenerator;
	}

	/**
	 * @return EntityIdFormatter
	 */
	private function getEntityIdFormatter() {
		$lookup = $this->getMock( EntityIdFormatter::class );

		$lookup->expects( $this->once() )
			->method( 'formatEntityId' )
			->will( $this->returnValue( '<ID>' ) );

		return $lookup;
	}

}
