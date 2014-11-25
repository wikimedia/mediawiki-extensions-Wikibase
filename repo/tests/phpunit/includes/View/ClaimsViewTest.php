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

	protected function setUp() {
		parent::setUp();

		$this->setMwGlobals( array(
			'wgArticlePath' => '/wiki/$1'
		) );
	}

	public function testGetHtml() {
		$property = $this->makeProperty();
		$entityInfo = $this->makeEntityInfo( $property );
		$claims = $this->makeClaims( $property->getId() );

		$entityTitleLookup = WikibaseRepo::getDefaultInstance()->getEntityContentFactory();
		$propertyTitle = $entityTitleLookup->getTitleForId( $property->getId() );

		$claimsView = $this->newClaimsView( $entityTitleLookup );

		$html = $claimsView->getHtml( $claims, $entityInfo );

		foreach ( $claims as $claim ) {
			$this->assertContains( $claim->getGuid(), $html );
		}

		$this->assertPropertyLink( $propertyTitle->getPrefixedText(), $html );
	}

	/**
	 * @return Property
	 */
	private function makeProperty() {
		$store = WikibaseRepo::getDefaultInstance()->getEntityStore();
		$testUser = new TestUser( 'WikibaseUser' );

		$property = Property::newEmpty();
		$property->setLabel( 'en', "<script>alert( 'omg!!!' );</script>" );
		$property->setDataTypeId( 'string' );

		$revision = $store->saveEntity( $property, 'test property', $testUser->getUser(), EDIT_NEW );

		return $revision->getEntity();
	}

	private function makeEntityInfo( Property $property ) {
		$prefixedId = $property->getId()->getSerialization();

		$entityInfo = array(
			$prefixedId => array(
				'type' => 'property',
				'id' => 'P31',
				'descriptions' => array(),
				'labels' => array()
			)
		);

		foreach( $property->getLabels() as $languageCode => $label ) {
			$entityInfo[$prefixedId]['labels'][$languageCode] = array(
				'language' => $languageCode,
				'value' => $label
			);
		}

		return $entityInfo;
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
	 * @return ClaimsView
	 */
	private function newClaimsView( EntityTitleLookup $entityTitleLookup ) {
		return new ClaimsView(
			$entityTitleLookup,
			new SectionEditLinkGenerator(),
			$this->getClaimHtmlGeneratorMock(),
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

	private function assertPropertyLink( $titleText, $html ) {
		$regExp = '/<a href="\/wiki\/' . $titleText . '" title="' . $titleText . '">'
			. "&lt;script&gt;alert\( \'omg!!!\' \);&lt;\/script&gt;<\/a>/";

		$this->assertRegExp( $regExp, $html );
	}

}
