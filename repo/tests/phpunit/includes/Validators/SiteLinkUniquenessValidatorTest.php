<?php

namespace Wikibase\Repo\Tests\Validators;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\Store\SiteLinkConflictLookup;
use Wikibase\Repo\Validators\SiteLinkUniquenessValidator;
use Wikibase\Repo\Validators\UniquenessViolation;
use Wikibase\Repo\Tests\ChangeOp\ChangeOpTestMockProvider;

/**
 * @covers Wikibase\Repo\Validators\SiteLinkUniquenessValidator
 *
 * @group Database
 * @group Wikibase
 * @group WikibaseContent
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class SiteLinkUniquenessValidatorTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @return SiteLinkConflictLookup
	 */
	private function getMockSiteLinkConflictLookup() {
		$mockProvider = new ChangeOpTestMockProvider( $this );
		return $mockProvider->getMockSiteLinkConflictLookup();
	}

	public function testValidateEntity() {
		$goodEntity = new Item( new ItemId( 'Q5' ) );
		$goodEntity->getSiteLinkList()->addNewSiteLink( 'testwiki', 'Foo' );

		$siteLinkConflictLookup = $this->getMockSiteLinkConflictLookup();

		$validator = new SiteLinkUniquenessValidator( $siteLinkConflictLookup );

		$result = $validator->validateEntity( $goodEntity );

		$this->assertTrue( $result->isValid(), 'isValid' );
	}

	public function testValidateEntity_conflict() {
		$badEntity = new Item( new ItemId( 'Q7' ) );
		$badEntity->getSiteLinkList()->addNewSiteLink( 'testwiki', 'DUPE' );

		$siteLinkConflictLookup = $this->getMockSiteLinkConflictLookup();

		$validator = new SiteLinkUniquenessValidator( $siteLinkConflictLookup );

		$result = $validator->validateEntity( $badEntity );

		$this->assertFalse( $result->isValid(), 'isValid' );

		$errors = $result->getErrors();
		$this->assertEquals( 'sitelink-conflict', $errors[0]->getCode() );
		$this->assertInstanceOf( UniquenessViolation::class, $errors[0] );

		//NOTE: ChangeOpTestMockProvider::getSiteLinkConflictsForItem() uses 'Q666' as
		//      the conflicting item for all site links with the name 'DUPE'.
		$this->assertEquals( 'Q666', $errors[0]->getConflictingEntity() );
	}

}
