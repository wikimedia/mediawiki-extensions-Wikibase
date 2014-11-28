<?php

namespace Wikibase\Test;

use DataValues\StringValue;
use TestUser;
use Title;
use Html;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\Lib\EntityIdFormatter;
use Wikibase\Repo\View\ClaimHtmlGenerator;
use Wikibase\Repo\View\ClaimsView;
use Wikibase\Repo\View\SectionEditLinkGenerator;

/**
 * @covers Wikibase\Repo\View\ClaimsView
 *
 * @group Wikibase
 * @group WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class ClaimsViewTest extends \MediaWikiLangTestCase {

	protected function setUp() {
		parent::setUp();

		$this->setMwGlobals( array(
			'wgArticlePath' => '/wiki/$1'
		) );
	}

	public function testGetHtml() {
		$propertyId = new PropertyId( 'P77' );
		$claims = $this->makeClaims( $propertyId );

		$propertyIdFormatter = $this->getPropertyIdFormatterMock();
		$link = $this->getLinkForId( $propertyId );

		$claimsView = $this->newClaimsView( $propertyIdFormatter );

		$html = $claimsView->getHtml( $claims );

		foreach ( $claims as $claim ) {
			$this->assertContains( $claim->getGuid(), $html );
		}

		$this->assertContains( $link, $html );
	}

	private function makeClaims( PropertyId $propertyId ) {
		$claims = array(
			$this->makeClaim( new PropertyNoValueSnak(
				$propertyId
			) ),
			$this->makeClaim( new PropertyValueSnak(
				$propertyId,
				new EntityIdValue( new ItemId( 'Q22' ) )
			) ),
			$this->makeClaim( new PropertyValueSnak(
				$propertyId,
				new StringValue( 'test' )
			) ),
			$this->makeClaim( new PropertyValueSnak(
				$propertyId,
				new StringValue( 'File:Image.jpg' )
			) ),
			$this->makeClaim( new PropertySomeValueSnak(
				$propertyId
			) ),
			$this->makeClaim( new PropertyValueSnak(
				$propertyId,
				new EntityIdValue( new ItemId( 'Q555' ) )
			) ),
		);

		return $claims;
	}

	private function makeClaim( Snak $mainSnak, $guid = null ) {
		static $guidCounter = 0;

		if ( $guid === null ) {
			$guidCounter++;
			$guid = 'EntityViewTest$' . $guidCounter;
		}

		$claim = new Claim( $mainSnak );
		$claim->setGuid( $guid );

		return $claim;
	}

	/**
	 * @param EntityIdFormatter $propertyIdFormatter
	 *
	 * @return ClaimsView
	 */
	private function newClaimsView( EntityIdFormatter $propertyIdFormatter ) {
		return new ClaimsView(
			$propertyIdFormatter,
			new SectionEditLinkGenerator(),
			$this->getClaimHtmlGeneratorMock()
		);
	}

	/**
	 * @return ClaimHtmlGenerator
	 */
	private function getClaimHtmlGeneratorMock() {
		$claimHtmlGenerator = $this->getMockBuilder( 'Wikibase\Repo\View\ClaimHtmlGenerator' )
			->disableOriginalConstructor()
			->getMock();

		$claimHtmlGenerator->expects( $this->any() )
			->method( 'getHtmlForClaim' )
			->will( $this->returnCallback( function( Claim $claim, $htmlForEditSection ) {
				return $claim->getGuid();
			} ) );

		return $claimHtmlGenerator;
	}

	/**
	 * @param EntityId $id
	 * @return string
	 */
	public function getLinkForId( EntityId $id ) {
		$name = $id->getEntityType() . ':' . $id->getSerialization();
		$url = 'http://wiki.acme.com/wiki/' . urlencode( $name );

		return Html::element( 'a', array( 'href' => $url ), $name );
	}

	/**
	 * @return EntityIdFormatter
	 */
	protected function getPropertyIdFormatterMock() {
		$lookup = $this->getMockBuilder( 'Wikibase\Lib\EntityIdFormatter' )
			->disableOriginalConstructor()
			->getMock();

		$lookup->expects( $this->any() )
			->method( 'format' )
			->will( $this->returnCallback( array( $this, 'getLinkForId' ) ) );

		return $lookup;
	}

}
