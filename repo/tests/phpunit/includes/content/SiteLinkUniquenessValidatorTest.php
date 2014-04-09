<?php

namespace Wikibase\Test;

use Site;
use Title;
use Wikibase\content\SiteLinkUniquenessValidator;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;
use Wikibase\EntityTitleLookup;
use Wikibase\SiteLinkLookup;

/**
 * @covers Wikibase\content\SiteLinkUniquenessValidator
 *
 * @group Database
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseContent
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class SiteLinkUniquenessValidatorTest extends \PHPUnit_Framework_TestCase {

	public function getTitleForId( EntityId $id ) {
		return Title::makeTitle( NS_MAIN, $id->getSerialization() );
	}

	public function getConflictsForItem( Item $item ) {
		$conflicts = array();

		foreach ( $item->getSiteLinks() as $link ) {
			if ( $link->getPageName() === 'DUPE' ) {
				$conflicts[] = array(
					'itemId' => 666,
					'siteId' => $link->getSiteId(),
					'sitePage' => $link->getPageName() );
			}
		}

		return $conflicts;
	}

	public function getSite( $siteId ) {
		$site = new Site();
		$site->setGlobalId( $siteId );
		$site->setLinkPath( "http//$siteId.acme.test/" );

		return $site;
	}

	/**
	 * @return EntityTitleLookup
	 */
	private function getMockTitleLookup() {
		$termIndex = $this->getMock( 'Wikibase\EntityTitleLookup' );

		$termIndex->expects( $this->any() )
			->method( 'getTitleForId' )
			->will( $this->returnCallback( array( $this, 'getTitleForId' ) ) );

		return $termIndex;
	}

	/**
	 * @return SiteLinkLookup
	 */
	private function getMockSiteLinkLookup() {
		$termIndex = $this->getMock( 'Wikibase\SiteLinkLookup' );

		$termIndex->expects( $this->any() )
			->method( 'getConflictsForItem' )
			->will( $this->returnCallback( array( $this, 'getConflictsForItem' ) ) );

		return $termIndex;
	}

	/**
	 * @return SiteLinkLookup
	 */
	private function getMockSiteStore() {
		$termIndex = $this->getMock( '\SiteStore' );

		$termIndex->expects( $this->any() )
			->method( 'getSite' )
			->will( $this->returnCallback( array( $this, 'getSite' ) ) );

		return $termIndex;
	}

	public function validEntityProvider() {
		$goodEntity = Item::newEmpty();
		$goodEntity->setId( new ItemId( 'Q5' ) );
		$goodEntity->addSiteLink( new SiteLink( 'testwiki', 'Foo' ) );

		return array(
			array( $goodEntity ),
		);
	}

	public function invalidEntityProvider() {
		$badEntity = Item::newEmpty();
		$badEntity->setId( new ItemId( 'Q7' ) );
		$badEntity->addSiteLink( new SiteLink( 'testwiki', 'DUPE' ) );

		return array(
			array( $badEntity, 'wikibase-error-sitelink-already-used' ),
		);
	}

	/**
	 * @dataProvider validEntityProvider
	 *
	 * @param Entity $entity
	 */
	public function testValidateEntity( Entity $entity ) {
		$titleLookup = $this->getMockTitleLookup();
		$siteLinkLookup = $this->getMockSiteLinkLookup();
		$siteStore = $this->getMockSiteStore();

		$validator = new SiteLinkUniquenessValidator( $titleLookup, $siteLinkLookup, $siteStore );

		$status = $validator->validateEntity( $entity );

		$this->assertTrue( $status->isOK(), 'isOK' );
	}

	/**
	 * @dataProvider invalidEntityProvider
	 *
	 * @param Entity $entity
	 * @param string $error
	 */
	public function testValidateEntity_failure( Entity $entity, $error ) {
		$titleLookup = $this->getMockTitleLookup();
		$siteLinkLookup = $this->getMockSiteLinkLookup();
		$siteStore = $this->getMockSiteStore();

		$validator = new SiteLinkUniquenessValidator( $titleLookup, $siteLinkLookup, $siteStore );

		$status = $validator->validateEntity( $entity );

		$this->assertFalse( $status->isOK(), 'isOK' );

		$errors = $status->getErrorsArray();
		$this->assertEquals( $error, $errors[0][0] );
	}

}
