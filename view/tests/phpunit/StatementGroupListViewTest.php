<?php

namespace Wikibase\Test;

use DataValues\StringValue;
use Html;
use MediaWikiLangTestCase;
use Wikibase\DataModel\Entity\EntityId;
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
use Wikibase\View\StatementGroupListView;
use Wikibase\View\Template\TemplateFactory;

/**
 * @covers Wikibase\View\StatementGroupListView
 *
 * @group Wikibase
 * @group WikibaseView
 *
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class StatementGroupListViewTest extends MediaWikiLangTestCase {

	protected function setUp() {
		parent::setUp();

		$this->setMwGlobals( array(
			'wgArticlePath' => '/wiki/$1'
		) );
	}

	/**
	 * @uses Wikibase\View\EditSectionGenerator
	 * @uses Wikibase\View\Template\Template
	 * @uses Wikibase\View\Template\TemplateFactory
	 * @uses Wikibase\View\Template\TemplateRegistry
	 */
	public function testGetHtml() {
		$propertyId = new PropertyId( 'P77' );
		$statements = $this->makeStatements( $propertyId );

		$propertyIdFormatter = $this->getEntityIdFormatter();
		$link = $this->getLinkForId( $propertyId );

		$statementGroupListView = $this->newStatementGroupListView( $propertyIdFormatter );

		$html = $statementGroupListView->getHtml( $statements );

		foreach ( $statements as $statement ) {
			$this->assertContains( $statement->getGuid(), $html );
		}

		$this->assertContains( $link, $html );
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
	 * @param string|null $guid
	 *
	 * @return Statement
	 */
	private function makeStatement( Snak $mainSnak, $guid = null ) {
		static $guidCounter = 0;

		if ( $guid === null ) {
			$guidCounter++;
			$guid = 'EntityViewTest$' . $guidCounter;
		}

		$statement = new Statement( $mainSnak );
		$statement->setGuid( $guid );

		return $statement;
	}

	/**
	 * @param EntityIdFormatter $propertyIdFormatter
	 *
	 * @return StatementGroupListView
	 */
	private function newStatementGroupListView( EntityIdFormatter $propertyIdFormatter ) {
		$templateFactory = TemplateFactory::getDefaultInstance();

		return new StatementGroupListView(
			$templateFactory,
			$propertyIdFormatter,
			$this->getMock( 'Wikibase\View\EditSectionGenerator' ),
			$this->getClaimHtmlGenerator()
		);
	}

	/**
	 * @return ClaimHtmlGenerator
	 */
	private function getClaimHtmlGenerator() {
		$claimHtmlGenerator = $this->getMockBuilder( 'Wikibase\View\ClaimHtmlGenerator' )
			->disableOriginalConstructor()
			->getMock();

		$claimHtmlGenerator->expects( $this->any() )
			->method( 'getHtmlForClaim' )
			->will( $this->returnCallback( function( Statement $statement ) {
				return $statement->getGuid();
			} ) );

		return $claimHtmlGenerator;
	}

	/**
	 * @param EntityId $id
	 *
	 * @return string HTML
	 */
	public function getLinkForId( EntityId $id ) {
		$name = $id->getEntityType() . ':' . $id->getSerialization();
		$url = 'http://wiki.acme.com/wiki/' . urlencode( $name );
		return Html::element( 'a', array( 'href' => $url ), $name );
	}

	/**
	 * @return EntityIdFormatter
	 */
	private function getEntityIdFormatter() {
		$lookup = $this->getMock( 'Wikibase\DataModel\Services\EntityId\EntityIdFormatter' );

		$lookup->expects( $this->any() )
			->method( 'formatEntityId' )
			->will( $this->returnCallback( array( $this, 'getLinkForId' ) ) );

		return $lookup;
	}

}
