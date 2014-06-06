<?php

namespace Wikibase\Test\Validators;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;
use Wikibase\Lib\Store\SiteLinkLookup;
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
		$this->assertInstanceOf( 'Wikibase\Validators\UniquenessViolation', $errors[0] );

		//NOTE: ChangeOpTestMockProvider::getSiteLinkConflictsForItem() uses 'Q666' as
		//      the conflicting item for all site links with the name 'DUPE'.
		$this->assertEquals( 'Q666', $errors[0]->getConflictingEntity() );
	}

}
