<?php

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Services\Statement\StatementGuidValidator;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class StatementGuidValidatorTest extends ServiceWiringTestCase {

	public function testConstruction() {
		$this->mockService( 'WikibaseRepo.EntityIdParser',
			new ItemIdParser() );

		/** @var StatementGuidValidator $statementGuidValidator */
		$statementGuidValidator = $this->getService( 'WikibaseRepo.StatementGuidValidator' );
		$this->assertInstanceOf( StatementGuidValidator::class, $statementGuidValidator );
		$this->assertTrue( $statementGuidValidator->validate( 'Q111$12345678-1234-1234-1234-123456789098' ) );
		$this->assertFalse( $statementGuidValidator->validate( 'P111$12345678-1234-1234-1234-123456789098' ) );
	}
}
