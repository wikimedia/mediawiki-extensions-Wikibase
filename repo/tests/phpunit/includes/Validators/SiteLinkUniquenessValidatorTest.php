<?php

namespace Wikibase\Test\Validators;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;
use Wikibase\SiteLinkLookup;
use Wikibase\Test\ChangeOpTestMockProvider;
use Wikibase\Validators\SiteLinkUniquenessValidator;

/**
 * @covers Wikibase\Validators\SiteLinkUniquenessValidator
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

	/**
	 * @return SiteLinkLookup
	 */
	private function getMockSiteLinkLookup() {
		$mockProvider = new ChangeOpTestMockProvider( $this );
		return $mockProvider->getMockSitelinkCache();
	}

	public function testValidateEntity() {
		$goodEntity = Item::newEmpty();
		$goodEntity->setId( new ItemId( 'Q5' ) );
		$goodEntity->addSiteLink( new SiteLink( 'testwiki', 'Foo' ) );

		$siteLinkLookup = $this->getMockSiteLinkLookup();

		$validator = new SiteLinkUniquenessValidator( $siteLinkLookup );

		$result = $validator->validateEntity( $goodEntity );

		$this->assertTrue( $result->isValid(), 'isValid' );
	}

	public function testValidateEntity_conflict() {
		$badEntity = Item::newEmpty();
		$badEntity->setId( new ItemId( 'Q7' ) );
		$badEntity->addSiteLink( new SiteLink( 'testwiki', 'DUPE' ) );

		$siteLinkLookup = $this->getMockSiteLinkLookup();

		$validator = new SiteLinkUniquenessValidator( $siteLinkLookup );

		$result = $validator->validateEntity( $badEntity );

		$this->assertFalse( $result->isValid(), 'isValid' );

		$errors = $result->getErrors();
		$this->assertEquals( 'sitelink-conflict', $errors[0]->getCode() );
	}

	public function testValidateEntity_ignored_conflict() {
		$badEntity = Item::newEmpty();
		$badEntity->setId( new ItemId( 'Q7' ) );
		$badEntity->addSiteLink( new SiteLink( 'testwiki', 'DUPE' ) );

		$siteLinkLookup = $this->getMockSiteLinkLookup();

		$validator = new SiteLinkUniquenessValidator( $siteLinkLookup );

		//NOTE: ChangeOpTestMockProvider::getSiteLinkConmflictsForItem will report
		//      a conflict with item Q666 if the page name is "DUPE".
		$ignoredId = new ItemId( 'Q666' );
		$result = $validator->validateEntity( $badEntity, $ignoredId );

		$this->assertTrue( $result->isValid(), 'isValid' );
	}

}
