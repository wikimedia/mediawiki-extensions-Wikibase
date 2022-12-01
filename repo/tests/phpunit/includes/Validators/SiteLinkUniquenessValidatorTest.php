<?php

namespace Wikibase\Repo\Tests\Validators;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\Store\SiteLinkConflictLookup;
use Wikibase\Repo\Tests\ChangeOp\ChangeOpTestMockProvider;
use Wikibase\Repo\Validators\SiteLinkUniquenessValidator;
use Wikibase\Repo\Validators\UniquenessViolation;

/**
 * @covers \Wikibase\Repo\Validators\SiteLinkUniquenessValidator
 *
 * @group Database
 * @group Wikibase
 * @group WikibaseContent
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class SiteLinkUniquenessValidatorTest extends \PHPUnit\Framework\TestCase {

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

		$validator = new SiteLinkUniquenessValidator( $siteLinkConflictLookup, [] );

		$result = $validator->validateEntity( $goodEntity );

		$this->assertTrue( $result->isValid(), 'isValid' );
	}

	/** @dataProvider provideRedirectsSupported */
	public function testValidateEntity_conflict( array $redirectBadgeItems, string $expectedCode ) {
		$badEntity = new Item( new ItemId( 'Q7' ) );
		$badEntity->getSiteLinkList()->addNewSiteLink( 'testwiki', 'DUPE' );
		$badEntity->getSiteLinkList()->addNewSiteLink( 'test2wiki', 'DUPE-UNKNOWN' );

		$siteLinkConflictLookup = $this->getMockSiteLinkConflictLookup();

		$validator = new SiteLinkUniquenessValidator( $siteLinkConflictLookup, $redirectBadgeItems );

		$result = $validator->validateEntity( $badEntity );

		$this->assertFalse( $result->isValid(), 'isValid' );
		$errors = $result->getErrors();

		$this->assertEquals( $expectedCode, $errors[0]->getCode() );
		$this->assertInstanceOf( UniquenessViolation::class, $errors[0] );
		//NOTE: ChangeOpTestMockProvider::getSiteLinkConflictsForItem() uses 'Q666' as
		//      the conflicting item for all site links with the name 'DUPE'.
		$this->assertEquals( 'Q666', $errors[0]->getConflictingEntity() );

		// the "unknown" error code does not depend on $badgeItemIds
		$this->assertSame( 'sitelink-conflict-unknown', $errors[1]->getCode() );
		$this->assertInstanceOf( UniquenessViolation::class, $errors[1] );
		// similarly, all sitelinks with the name 'DUPE-UNKNOWN' get the conflicting item null
		$this->assertNull( $errors[1]->getConflictingEntity() );
	}

	public function provideRedirectsSupported(): iterable {
		yield 'sitelinks to redirects not supported' => [ [], 'sitelink-conflict' ];
		yield 'sitelinks to redirects supported' => [ [ 'Q1' ], 'sitelink-conflict-redirects-supported' ];
	}

}
