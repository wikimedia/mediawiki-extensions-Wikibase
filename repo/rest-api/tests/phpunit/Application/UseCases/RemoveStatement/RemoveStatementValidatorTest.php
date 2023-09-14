<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\RemoveStatement;

use CommentStore;
use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\Repo\RestApi\Application\UseCases\RemoveStatement\RemoveStatementRequest;
use Wikibase\Repo\RestApi\Application\UseCases\RemoveStatement\RemoveStatementValidator;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\EditMetadataValidator;
use Wikibase\Repo\RestApi\Application\Validation\StatementIdValidator;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\RemoveStatement\RemoveStatementValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class RemoveStatementValidatorTest extends TestCase {

	private const ALLOWED_TAGS = [ 'some', 'tags', 'are', 'allowed' ];

	/**
	 * @doesNotPerformAssertions
	 */
	public function testValidatePass(): void {
		$itemId = 'Q123';
		$statementId = $itemId . StatementGuid::SEPARATOR . 'AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';

		$this->newValidator()->assertValidRequest( $this->newUseCaseRequest( [
			'$statementId' => $statementId,
			'$editTags' => [],
			'$isBot' => false,
			'$comment' => null,
			'$username' => null,
		] ) );
	}

	/**
	 * @dataProvider provideInvalidRequest
	 */
	public function testValidateFails( array $requestData, string $errorCode, string $errorMessage ): void {
		try {
			$this->newValidator()->assertValidRequest( $this->newUseCaseRequest( $requestData ) );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $errorCode, $e->getErrorCode() );
			$this->assertSame( $errorMessage, $e->getErrorMessage() );
		}
	}

	public static function provideInvalidRequest(): Generator {
		$statementId = 'Q123' . StatementGuid::SEPARATOR . 'INVALID-STATEMENT-ID';
		yield 'Invalid statement ID (without item ID)' => [
			[
				'$statementId' => $statementId,
				'$editTags' => [],
				'$isBot' => false,
				'$comment' => null,
				'$username' => null,
			],
			StatementIdValidator::CODE_INVALID,
			"Not a valid statement ID: $statementId",
		];

		$itemId = 'Q42';
		$comment = str_repeat( 'x', CommentStore::COMMENT_CHARACTER_LIMIT + 1 );
		yield 'Comment too long' => [
			[
				'$statementId' => $itemId . StatementGuid::SEPARATOR . 'AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE',
				'$editTags' => [],
				'$isBot' => false,
				'$comment' => $comment,
				'$username' => null,
			],
			EditMetadataValidator::CODE_COMMENT_TOO_LONG,
			'Comment must not be longer than ' . CommentStore::COMMENT_CHARACTER_LIMIT . ' characters.',
		];

		$itemId = 'Q24';
		$invalidTag = 'invalid';
		yield 'Invalid edit tags' => [
			[
				'$statementId' => $itemId . StatementGuid::SEPARATOR . 'AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE',
				'$editTags' => [ 'some', 'tags', 'are', $invalidTag ],
				'$isBot' => false,
				'$comment' => null,
				'$username' => null,
			],
			EditMetadataValidator::CODE_INVALID_TAG,
			"Invalid MediaWiki tag: \"$invalidTag\"",
		];
	}

	private function newValidator(): RemoveStatementValidator {
		return new RemoveStatementValidator(
			new StatementIdValidator( new ItemIdParser() ),
			new EditMetadataValidator( CommentStore::COMMENT_CHARACTER_LIMIT, self::ALLOWED_TAGS )
		);
	}

	private function newUseCaseRequest( array $requestData ): RemoveStatementRequest {
		return new RemoveStatementRequest(
			$requestData['$statementId'],
			$requestData['$editTags'],
			$requestData['$isBot'],
			$requestData['$comment'] ?? null,
			$requestData['$username'] ?? null,
		);
	}
}
