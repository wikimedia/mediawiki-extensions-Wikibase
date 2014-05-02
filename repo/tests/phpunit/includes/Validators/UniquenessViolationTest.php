<?php

namespace Wikibase\Test\Validators;

use Wikibase\Validators\UniquenessViolation;
use Wikibase\DataModel\Entity\ItemId;

/**
 * @covers Wikibase\Validators\UniquenessViolation
 *
 * @group Database
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseContent
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class UniquenessViolationTest extends \PHPUnit_Framework_TestCase {

	public function testConstruct() {
		$conflictingEntity = new ItemId( 'Q4' );

		$violation = new UniquenessViolation(
			$conflictingEntity,
			'Just a Test',
			'test',
			array(
				'stuff',
				$conflictingEntity
			)
		);

		$this->assertEquals( $conflictingEntity, $violation->getConflictingEntity(), 'getConflictingEntity' );
		$this->assertEquals( 'Just a Test', $violation->getText(), 'getText' );
		$this->assertEquals( 'test', $violation->getCode(), 'getCode' );

		$params = $violation->getParameters();
		$this->assertEquals( 'stuff', $params[0], '$params[0]' );
		$this->assertEquals( $conflictingEntity, $params[1], '$params[1]' );
	}

}
