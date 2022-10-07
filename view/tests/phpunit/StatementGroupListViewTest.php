<?php

namespace Wikibase\View\Tests;

use DataValues\StringValue;
use MediaWikiTestCaseTrait;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Lib\Store\PropertyOrderProvider;
use Wikibase\View\EditSectionGenerator;
use Wikibase\View\StatementGroupListView;
use Wikibase\View\StatementHtmlGenerator;
use Wikibase\View\Template\TemplateFactory;
use Wikibase\View\Template\TemplateRegistry;

/**
 * @covers \Wikibase\View\StatementGroupListView
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
	use MediaWikiTestCaseTrait;

	public function testGetHtml() {
		$propertyId = new NumericPropertyId( 'P77' );
		$statements = $this->makeStatements( $propertyId );

		$statementGroupListView = $this->newStatementGroupListView();

		$html = $statementGroupListView->getHtml( $statements );

		$this->assertStringContainsString( 'id="P77', $html );
		$this->assertStringContainsString( '<PROPERTY><ID></PROPERTY>', $html );
		foreach ( $statements as $statement ) {
			$this->assertStringContainsString( $statement->getGuid(), $html );
		}
		$this->assertStringContainsString( '<TOOLBAR></TOOLBAR>', $html );
	}

	public function testGivenIdPrefix_getHtmlPrefixesId() {
		$id = 'P78';
		$prefix = 'X1-Y5';
		$statements = $this->makeStatements( new NumericPropertyId( $id ) );

		$statementGroupListView = $this->newStatementGroupListView();

		$this->assertStringContainsString(
			'id="' . $prefix . StatementGroupListView::ID_PREFIX_SEPARATOR . $id . '"',
			$statementGroupListView->getHtml( $statements, $prefix )
		);
	}

	public function testAddsPropertyIdDataAttribute() {
		$id = 'P78';
		$statements = $this->makeStatements( new NumericPropertyId( $id ) );

		$statementGroupListView = $this->newStatementGroupListView();

		$this->assertStringContainsString(
			'data-property-id="' . $id . '"',
			$statementGroupListView->getHtml( $statements )
		);
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
		$this->assertMatchesRegularExpression( '/^[^$]*\$' . implode( '\n[^$]*\$', [
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
		$statement = new Statement( new PropertyNoValueSnak( new NumericPropertyId( $propertyId ) ) );
		$statement->setGuid( 'GUID$' . $propertyId );
		return $statement;
	}

	/**
	 * @param NumericPropertyId $propertyId
	 *
	 * @return Statement[]
	 */
	private function makeStatements( NumericPropertyId $propertyId ) {
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
			'wikibase-statementgroupview' => '<SGROUP data-property-id="$4" id="$3"><PROPERTY>$1</PROPERTY>$2</SGROUP>',
			'wikibase-statementlistview' => '<SLIST>$1<TOOLBAR>$2</TOOLBAR></SLIST>',
		] ) );

		return new StatementGroupListView(
			$this->getPropertyOrderProvider(),
			$templateFactory,
			$this->getEntityIdFormatter(),
			$this->createMock( EditSectionGenerator::class ),
			$this->getStatementHtmlGenerator()
		);
	}

	/**
	 * @return PropertyOrderProvider
	 */
	private function getPropertyOrderProvider() {
		$propertyOrderProvider = $this->createMock( PropertyOrderProvider::class );

		$propertyOrderProvider->method( 'getPropertyOrder' )
			->willReturn( [
				'P101' => 0,
				'P102' => 1,
				'P103' => 2,
			] );

		return $propertyOrderProvider;
	}

	/**
	 * @return StatementHtmlGenerator
	 */
	private function getStatementHtmlGenerator() {
		$statementHtmlGenerator = $this->createMock( StatementHtmlGenerator::class );

		$statementHtmlGenerator->method( 'getHtmlForStatement' )
			->willReturnCallback( function( Statement $statement, $editSectionHtml = null ) {
				return $statement->getGuid() . "\n";
			} );

		return $statementHtmlGenerator;
	}

	/**
	 * @return EntityIdFormatter
	 */
	private function getEntityIdFormatter() {
		$entityIdFormatter = $this->createMock( EntityIdFormatter::class );

		$entityIdFormatter->method( 'formatEntityId' )
			->willReturn( '<ID>' );

		return $entityIdFormatter;
	}

}
