<?php

namespace Wikibase\Repo\Tests\Validators;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\Validators\UniquenessViolation;

/**
 * @covers \Wikibase\Repo\Validators\UniquenessViolation
 *
 * @group Database
 * @group Wikibase
 * @group WikibaseContent
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class UniquenessViolationTest extends \PHPUnit\Framework\TestCase {

	public function testConstruct() {
		$conflictingEntity = new ItemId( 'Q4' );

		$violation = new UniquenessViolation(
			$conflictingEntity,
			'Just a Test',
			'test',
			[
				'stuff',
				$conflictingEntity,
			]
		);

		$this->assertEquals( $conflictingEntity, $violation->getConflictingEntity(), 'getConflictingEntity' );
		$this->assertEquals( 'Just a Test', $violation->getText(), 'getText' );
		$this->assertEquals( 'test', $violation->getCode(), 'getCode' );

		$params = $violation->getParameters();
		$this->assertEquals( 'stuff', $params[0], '$params[0]' );
		$this->assertEquals( $conflictingEntity, $params[1], '$params[1]' );
	}

}
