<?php

namespace Wikibase\Test;

use DataValues\StringValue;
use TestUser;
use Title;
use Wikibase\ClaimHtmlGenerator;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\View\ClaimsView;
use Wikibase\Repo\View\SectionEditLinkGenerator;
use Wikibase\Repo\WikibaseRepo;

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
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class ClaimsViewTest extends \MediaWikiLangTestCase {

	public function testGetHtml() {
		$claims = $this->makeClaims();
		$claimsView = $this->newClaimsView();

		$html = $claimsView->getHtml( $claims, array() );

		foreach ( $claims as $claim ) {
			$this->assertContains( $claim->getGuid(), $html );
		}

		$this->assertContains( 'property-link', $html );
	}

	private function makeClaims() {
		$claims = array(
			$this->makeClaim( new PropertyNoValueSnak(
				new PropertyId( 'P10' )
			) ),
			$this->makeClaim( new PropertyValueSnak(
				new PropertyId( 'P10' ),
				new EntityIdValue( new ItemId( 'Q22' ) )
			) ),
			$this->makeClaim( new PropertyValueSnak(
				new PropertyId( 'P10' ),
				new StringValue( 'test' )
			) ),
			$this->makeClaim( new PropertyValueSnak(
				new PropertyId( 'P10' ),
				new StringValue( 'File:Image.jpg' )
			) ),
			$this->makeClaim( new PropertySomeValueSnak(
				new PropertyId( 'P10' )
			) ),
			$this->makeClaim( new PropertyValueSnak(
				new PropertyId( 'P10' ),
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
	 * @return ClaimsView
	 */
	private function newClaimsView() {
		return new ClaimsView(
			new SectionEditLinkGenerator(),
			$this->getClaimHtmlGeneratorMock(),
			$this->getPropertyLinkFormatter(),
			'en'
		);
	}

	/**
	 * @return ClaimHtmlGenerator
	 */
	private function getClaimHtmlGeneratorMock() {
		$claimHtmlGenerator = $this->getMockBuilder( 'Wikibase\ClaimHtmlGenerator' )
			->disableOriginalConstructor()
			->getMock();

		$claimHtmlGenerator->expects( $this->any() )
			->method( 'getHtmlForClaim' )
			->will( $this->returnCallback( function( Claim $claim, array $entityInfo, $htmlForEditSection ) {
				return $claim->getGuid();
			} ) );

		return $claimHtmlGenerator;
	}

	private function getPropertyLinkFormatter() {
		$propertyLinkFormatter = $this->getMockBuilder(
				'Wikibase\Repo\View\EntityInfoPropertyLinkFormatter'
			)
			->disableOriginalConstructor()
			->getMock();

		$propertyLinkFormatter->expects( $this->any() )
			->method( 'makePropertyLink' )
			->will( $this->returnValue( 'property-link' ) );

		return $propertyLinkFormatter;
	}

}
