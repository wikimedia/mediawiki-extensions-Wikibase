<?php

namespace Wikibase\View\Tests;

use DataValues\StringValue;
use PHPUnit4And6Compat;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Lib\Store\PropertyOrderProvider;
use Wikibase\View\StatementHtmlGenerator;
use Wikibase\View\EditSectionGenerator;
use Wikibase\View\StatementGroupListView;
use Wikibase\View\Template\TemplateFactory;
use Wikibase\View\Template\TemplateRegistry;

/**
 * @covers Wikibase\View\StatementGroupListView
 *
 * @uses Wikibase\View\Template\Template
 * @uses Wikibase\View\Template\TemplateFactory
 * @uses Wikibase\View\Template\TemplateRegistry
 *
 * @group Wikibase
 * @group WikibaseView
 *
 * @license GPL-2.0-or-later
 * @author Bene* < benestar.wikimedia@gmail.com >
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class StatementGroupListViewTest extends \PHPUnit\Framework\TestCase {
	use PHPUnit4And6Compat;

	public function testGetHtml() {
		$propertyId = new PropertyId( 'P77' );
		$statements = $this->makeStatements( $propertyId );

		$statementGroupListView = $this->newStatementGroupListView();

		$html = $statementGroupListView->getHtml( $statements );

		$this->assertContains( 'id="P77', $html );
		$this->assertContains( '<PROPERTY><ID></PROPERTY>', $html );
		foreach ( $statements as $statement ) {
			$this->assertContains( $statement->getGuid(), $html );
		}
		$this->assertContains( '<TOOLBAR></TOOLBAR>', $html );
	}

	public function testPropertyOrdering() {
		$statements = [
			$this->makeNoValueStatement( 'P2' ),
			$this->makeNoValueStatement( 'P103' ),
			$this->makeNoValueStatement( 'P1' ),
			$this->makeNoValueStatement( 'P101' ),
			$this->makeNoValueStatement( 'P102' ),
		];
		$view = $this->newStatementGroupListView();
		$html = $view->getHtml( $statements );
		$this->assertRegExp( '/^[^$]*\$' . implode( '\n[^$]*\$', [
			'P101',
			'P102',
			'P103',
			'P2',
			'P1',
		] ) . '\n[^$]*$/s', $html );
	}

	/**
	 * @param string $propertyId
	 *
	 * @return Statement
	 */
	private function makeNoValueStatement( $propertyId ) {
		$statement = new Statement( new PropertyNoValueSnak( new PropertyId( $propertyId ) ) );
		$statement->setGuid( 'GUID$' . $propertyId );
		return $statement;
	}

	/**
	 * @param PropertyId $propertyId
	 *
	 * @return Statement[]
	 */
	private function makeStatements( PropertyId $propertyId ) {
		return [
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
		];
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
	 * @return StatementGroupListView
	 */
	private function newStatementGroupListView() {
		$templateFactory = new TemplateFactory( new TemplateRegistry( [
			'wikibase-statementgrouplistview' => '<SGLIST>$1</SGLIST>',
			'wikibase-listview' => '<LIST>$1</LIST>',
			'wikibase-statementgroupview' => '<SGROUP id="$3"><PROPERTY>$1</PROPERTY>$2</SGROUP>',
			'wikibase-statementlistview' => '<SLIST>$1<TOOLBAR>$2</TOOLBAR></SLIST>',
		] ) );

		return new StatementGroupListView(
			$this->getPropertyOrderProvider(),
			$templateFactory,
			$this->getEntityIdFormatter(),
			$this->getMock( EditSectionGenerator::class ),
			$this->getStatementHtmlGenerator()
		);
	}

	/**
	 * @return PropertyOrderProvider
	 */
	private function getPropertyOrderProvider() {
		$propertyOrderProvider = $this->getMock( PropertyOrderProvider::class );

		$propertyOrderProvider->method( 'getPropertyOrder' )
			->will( $this->returnValue( [
				'P101' => 0,
				'P102' => 1,
				'P103' => 2,
			] ) );

		return $propertyOrderProvider;
	}

	/**
	 * @return StatementHtmlGenerator
	 */
	private function getStatementHtmlGenerator() {
		$statementHtmlGenerator = $this->getMockBuilder( StatementHtmlGenerator::class )
			->disableOriginalConstructor()
			->getMock();

		$statementHtmlGenerator->method( 'getHtmlForStatement' )
			->will( $this->returnCallback( function( Statement $statement, $editSectionHtml = null ) {
				return $statement->getGuid() . "\n";
			} ) );

		return $statementHtmlGenerator;
	}

	/**
	 * @return EntityIdFormatter
	 */
	private function getEntityIdFormatter() {
		$entityIdFormatter = $this->getMock( EntityIdFormatter::class );

		$entityIdFormatter->method( 'formatEntityId' )
			->will( $this->returnValue( '<ID>' ) );

		return $entityIdFormatter;
	}

}
