<?php

namespace Wikibase\Test;

use Wikibase\Badge\BadgeValidator;
use Wikibase\DataModel\Entity\ItemId;

/**
 * @covers Wikibase\Badge\BadgeValidator
 *
 * @group Wikibase
 * @group WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class BadgeValidatorTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider validateProvider
	 */
	public function testValidate( $badgeItemId, $itemExists, $allowedBadgeItems ) {
		$badgeValidator = new BadgeValidator(
			$this->getEntityLookup( $itemExists ),
			$allowedBadgeItems
		);

		$badgeValidator->validate( $badgeItemId );

		// no exception thrown, badge is valid
		$this->assertTrue( true );
	}

	public function validateProvider() {
		$allowedBadgeItems = array(
			'Q9000' => '',
			'Q9001' => ''
		);

		$cases = array(
			array( new ItemId( 'Q9000' ), true, $allowedBadgeItems )
		);

		return $cases;
	}

	/**
	 * @dataProvider validateInvalidProvider
	 */
	public function testValidateInvalid( $badgeItemId, $itemExists, $allowedBadgeItems ) {
		$badgeValidator = new BadgeValidator(
			$this->getEntityLookup( $itemExists ),
			$allowedBadgeItems
		);

		$this->setExpectedException( 'Wikibase\Badge\BadgeException' );

		$badgeValidator->validate( $badgeItemId );
	}

	public function validateInvalidProvider() {
		$allowedBadgeItems = array(
			'Q9000' => '',
			'Q9001' => ''
		);

		$cases = array(
			array( new ItemId( 'Q338' ), true, $allowedBadgeItems ),
			array( new ItemId( 'Q338' ), true, $allowedBadgeItems ),
			array( new ItemId( 'Q9000' ), false, $allowedBadgeItems )
		);

		return $cases;
	}

	private function getEntityLookup( $itemExists ) {
		$entityLookup = $this->getMockBuilder( 'Wikibase\WikiPageEntityLookup' )
			->disableOriginalConstructor()
			->getMock();

		$entityLookup->expects( $this->any() )
			->method( 'hasEntity' )
			->will( $this->returnValue( $itemExists ) );

		return $entityLookup;
	}

}
