<?php

namespace Wikibase\Test;

use DataValues\StringValue;
use Title;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\View\ClaimsView;
use Wikibase\Repo\View\SectionEditLinkGenerator;

/**
 * @covers Wikibase\Repo\View\ClaimsView
 *
 * @group Wikibase
 * @group WikibaseRepo
 *
 * @group Database
 *		^---- needed because we rely on Title objects internally
 *
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class ClaimsViewTest extends \MediaWikiLangTestCase {

	public function getTitleForId( EntityId $id ) {
		$name = $id->getEntityType() . ':' . $id->getPrefixedId();
		return Title::makeTitle( NS_MAIN, $name );
	}

	public function formatSnak( Snak $snak ) {
		return $snak->getHash();
	}

	public function getHtmlProvider() {
		$claims = array(
			$this->makeClaim( new PropertyNoValueSnak(
				new PropertyId( 'P11' )
			) ),
			$this->makeClaim( new PropertyValueSnak(
				new PropertyId( 'P11' ),
				new EntityIdValue( new ItemId( 'Q22' ) )
			) ),
			$this->makeClaim( new PropertyValueSnak(
				new PropertyId( 'P23' ),
				new StringValue( 'test' )
			) ),
			$this->makeClaim( new PropertyValueSnak(
				new PropertyId( 'P43' ),
				new StringValue( 'File:Image.jpg' )
			) ),
			$this->makeClaim( new PropertySomeValueSnak(
				new PropertyId( 'P44' )
			) ),
			$this->makeClaim( new PropertyValueSnak(
				new PropertyId( 'P100' ),
				new EntityIdValue( new ItemId( 'Q555' ) )
			) ),
		);

		return array(
			array( $claims )
		);
	}

	/**
	 * @dataProvider getHtmlProvider
	 *
	 * @param Claim[] $claims
	 */
	public function testGetHtml( array $claims ) {
		$claimsView = $this->newClaimsView();

		$html = $claimsView->getHtml( $claims );

		foreach ( $claims as $claim ) {
			$this->assertContains( $claim->getPropertyId()->getSerialization(), $html );
			$this->assertContainsSnaks( $claim->getAllSnaks(), $html );
		}
	}

	private function assertContainsSnaks( array $snaks, $html ) {
		foreach ( $snaks as $snak ) {
			$this->assertContains( $snak->getHash(), $html );
		}
	}

	/**
	 * @return ClaimsView
	 */
	private function newClaimsView() {
		return new ClaimsView(
			new MockRepository(),
			$this->getEntityTitleLookupMock(),
			new SectionEditLinkGenerator(),
			$this->getSnakFormatterFactoryMock(),
			$this->getLanguageFallbackChainMock(),
			'en'
		);
	}

	/**
	 * @return EntityTitleLookup
	 */
	private function getEntityTitleLookupMock() {
		$lookup = $this->getMock( 'Wikibase\Lib\Store\EntityTitleLookup' );
		$lookup->expects( $this->any() )
			->method( 'getTitleForId' )
			->will( $this->returnCallback( array( $this, 'getTitleForId' ) ) );

		return $lookup;
	}

	private function getSnakFormatterFactoryMock() {
		$snakFormatterFactory = $this->getMockBuilder( 'Wikibase\Lib\OutputFormatSnakFormatterFactory' )
			->disableOriginalConstructor()
			->getMock();

		$snakFormatterFactory->expects( $this->any() )
			->method( 'getSnakFormatter' )
			->will( $this->returnValue( $this->getSnakFormatterMock() ) );

		return $snakFormatterFactory;
	}

	private function getSnakFormatterMock() {
		$snakFormatter = $this->getMock( 'Wikibase\Lib\SnakFormatter' );

		$snakFormatter->expects( $this->any() )
			->method( 'getFormat' )
			->will( $this->returnValue( SnakFormatter::FORMAT_HTML ) );

		$snakFormatter->expects( $this->any() )
			->method( 'formatSnak' )
			->will( $this->returnCallback( array( $this, 'formatSnak' ) ) );

		return $snakFormatter;
	}

	private function getLanguageFallbackChainMock() {
		return $this->getMockBuilder( 'Wikibase\LanguageFallbackChain' )
			->disableOriginalConstructor()
			->getMock();
	}

	protected function makeClaim( Snak $mainSnak, $guid = null ) {
		static $guidCounter = 0;

		if ( $guid === null ) {
			$guidCounter++;
			$guid = 'EntityViewTest$' . $guidCounter;
		}

		$claim = new Claim( $mainSnak );
		$claim->setGuid( $guid );

		return $claim;
	}

}
