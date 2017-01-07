<?php

namespace Wikibase\Repo\Tests\Validators;

use Wikibase\DataModel\Entity\Item;
use Wikibase\LabelDescriptionDuplicateDetector;
use Wikibase\Repo\Store\SiteLinkConflictLookup;
use Wikibase\Repo\Validators\EntityConstraintProvider;
use Wikibase\Repo\Validators\EntityValidator;

/**
 * @covers Wikibase\Repo\Validators\EntityConstraintProvider
 *
 * @group Wikibase
 * @group WikibaseValidators
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class EntityConstraintProviderTest extends \PHPUnit_Framework_TestCase {

	private function getEntityConstraintProvider() {
		$duplicateDetector = $this->getMockBuilder( LabelDescriptionDuplicateDetector::class )
			->disableOriginalConstructor()
			->getMock();

		$siteLinkConflictLookup = $this->getMock( SiteLinkConflictLookup::class );

		return new EntityConstraintProvider( $duplicateDetector, $siteLinkConflictLookup );
	}

	public function testGetUpdateValidators() {
		$provider = $this->getEntityConstraintProvider();
		$validators = $provider->getUpdateValidators( Item::ENTITY_TYPE );
		$this->assertValidators( $validators );
	}

	public function testGetCreationValidators() {
		$provider = $this->getEntityConstraintProvider();
		$validators = $provider->getCreationValidators( Item::ENTITY_TYPE );
		$this->assertValidators( $validators );

		// Creation validators must be a superset of update validators
		$updateValidators = $provider->getUpdateValidators( Item::ENTITY_TYPE );
		$this->assertContainsAll( $updateValidators, $validators );
	}

	private function assertValidators( $validators ) {
		$this->assertInternalType( 'array', $validators );

		foreach ( $validators as $validator ) {
			$this->assertInstanceOf( EntityValidator::class, $validator );
		}
	}

	private function assertContainsAll( $expected, $array ) {
		foreach ( $expected as $obj ) {
			$this->assertContains( $obj, $array, '', false, false );
		}
	}

}
