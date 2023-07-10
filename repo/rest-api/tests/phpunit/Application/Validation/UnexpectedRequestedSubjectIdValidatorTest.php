<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\Validation;

use LogicException;
use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\Application\Validation\UnexpectedRequestedSubjectIdValidator;

/**
 * @covers \Wikibase\Repo\RestApi\Application\Validation\UnexpectedRequestedSubjectIdValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class UnexpectedRequestedSubjectIdValidatorTest extends TestCase {

	public function testGivenSubjectIdIsNull_returnsNull(): void {
		$this->assertNull( ( new UnexpectedRequestedSubjectIdValidator() )->validate( null ) );
	}

	public function testGivenSubjectIdIsNotNull_throwsLogicException(): void {
		$this->expectException( LogicException::class );
		( new UnexpectedRequestedSubjectIdValidator() )->validate( 'Q123' );
	}

}
