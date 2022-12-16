<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Validation;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\Validation\EditMetadataValidator;
use Wikibase\Repo\RestApi\Validation\ValidationError;

/**
 * @covers \Wikibase\Repo\RestApi\Validation\EditMetadataValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EditMetadataValidatorTest extends TestCase {

	private const MAX_COMMENT_LENGTH = 42;
	private const ALLOWED_TAGS = [ 'tag1', 'tag2', 'tag3' ];

	public function testValidateValidComment(): void {
		$result = $this->newEditMetadataValidator()->validateComment( 'this is a valid comment' );

		$this->assertNull( $result );
	}

	public function testValidateCommentTooLong(): void {
		$result = $this->newEditMetadataValidator()->validateComment(
			'This comment is longer than 42 characters!!'
		);

		$this->assertInstanceOf( ValidationError::class, $result );
		$this->assertSame( EditMetadataValidator::CODE_COMMENT_TOO_LONG, $result->getCode() );
		$this->assertSame(
			(string)self::MAX_COMMENT_LENGTH,
			$result->getContext()[EditMetadataValidator::CONTEXT_COMMENT_MAX_LENGTH]
		);
	}

	public function testValidateValidEditTags(): void {
		$tags = [ self::ALLOWED_TAGS[2], self::ALLOWED_TAGS[0] ];

		$result = $this->newEditMetadataValidator()->validateEditTags( $tags );

		$this->assertNull( $result );
	}

	/**
	 * @dataProvider invalidEditTagsProvider
	 */
	public function testValidateInvalidEditTags( array $tags, string $invalidTag ): void {
		$result = $this->newEditMetadataValidator()->validateEditTags( $tags );

		$this->assertInstanceOf( ValidationError::class, $result );
		$this->assertSame( EditMetadataValidator::CODE_INVALID_TAG, $result->getCode() );
		$this->assertSame( $invalidTag, $result->getContext()[EditMetadataValidator::CONTEXT_TAG_VALUE] );
	}

	public function invalidEditTagsProvider(): Generator {
		yield 'disallowed tag' => [
			[ 'bad tag' ],
			'"bad tag"',
		];
		yield 'non-string array element' => [
			[ self::ALLOWED_TAGS[0], 123 ],
			'123',
		];
		yield 'complex non-string array element' => [
			[ self::ALLOWED_TAGS[1], [ 'very', 'bad' ] ],
			'["very","bad"]',
		];
	}

	private function newEditMetadataValidator(): EditMetadataValidator {
		return ( new EditMetadataValidator( self::MAX_COMMENT_LENGTH, self::ALLOWED_TAGS ) );
	}

}
