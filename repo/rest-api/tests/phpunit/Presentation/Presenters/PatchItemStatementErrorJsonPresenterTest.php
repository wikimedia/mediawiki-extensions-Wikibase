<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Presentation\Presenters;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Repo\RestApi\Presentation\Presenters\PatchItemStatementErrorJsonPresenter;
use Wikibase\Repo\RestApi\Serialization\StatementSerializer;
use Wikibase\Repo\RestApi\UseCases\PatchItemStatement\PatchItemStatementErrorResponse;

/**
 * @covers \Wikibase\Repo\RestApi\Presentation\Presenters\PatchItemStatementErrorJsonPresenter
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PatchItemStatementErrorJsonPresenterTest extends TestCase {

	private StatementSerializer $statementSerializer;

	protected function setUp(): void {
		parent::setUp();
		$this->statementSerializer = $this->createStub( StatementSerializer::class );
	}

	public function testGetJson_withoutContext(): void {
		$error = new PatchItemStatementErrorResponse( 'test-code', 'Test message' );

		$this->assertJsonStringEqualsJsonString(
			'{ "code": "test-code", "message": "Test message" }',
			$this->newErrorJsonPresenter()->getJson( $error )
		);
	}

	public function testGetJson_withContext(): void {
		$error = new PatchItemStatementErrorResponse(
			'test-code',
			'Test message',
			[ 'test' => 'with', 'context' => 42 ]
		);

		$this->assertJsonStringEqualsJsonString(
			'{"code":"test-code","message":"Test message","context":{"test":"with","context":42}}',
			$this->newErrorJsonPresenter()->getJson( $error )
		);
	}

	public function testGetJson_withPatchedStatementContext(): void {
		$statement = NewStatement::noValueFor( 'P1' )->build();
		$this->statementSerializer = $this->createMock( StatementSerializer::class );
		$this->statementSerializer->expects( $this->once() )
			->method( 'serialize' )
			->with( $statement )
			->willReturn( [ 'mock' => [ 'statement' => 'serialization' ] ] );

		$error = new PatchItemStatementErrorResponse(
			'test-code',
			'Test message',
			[ 'test' => 'with', 'patched-statement' => $statement ]
		);

		$this->assertJsonStringEqualsJsonString(
			'{
			  "code":"test-code","message":"Test message",
			  "context":{"test":"with","patched-statement":{"mock":{"statement":"serialization"}}}
			}',
			$this->newErrorJsonPresenter()->getJson( $error )
		);
	}

	private function newErrorJsonPresenter(): PatchItemStatementErrorJsonPresenter {
		return new PatchItemStatementErrorJsonPresenter( $this->statementSerializer );
	}

}
