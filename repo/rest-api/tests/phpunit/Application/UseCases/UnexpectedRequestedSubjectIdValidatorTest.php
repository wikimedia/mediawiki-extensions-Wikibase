<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases;

use LogicException;
use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\Application\UseCases\UnexpectedRequestedSubjectIdValidator;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\UnexpectedRequestedSubjectIdValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class UnexpectedRequestedSubjectIdValidatorTest extends TestCase {

	/**
	 * @doesNotPerformAssertions
	 */
	public function testGivenSubjectIdIsNull_noErrorIsThrown(): void {
		( new UnexpectedRequestedSubjectIdValidator() )->assertValid( null );
	}

	public function testGivenSubjectIdIsNotNull_throwsLogicException(): void {
		$this->expectException( LogicException::class );
		( new UnexpectedRequestedSubjectIdValidator() )->assertValid( 'Q123' );
	}

}
