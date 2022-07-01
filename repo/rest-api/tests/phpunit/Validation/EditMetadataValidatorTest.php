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
		$source = 'comment';

		$result = $this->newEditMetadataValidator()->validateComment( "this is a valid comment", $source );

		$this->assertNull( $result );
	}

	public function testValidateCommentTooLong(): void {
		$source = 'comment';

		$result = $this->newEditMetadataValidator()->validateComment(
			"This comment is longer than 42 characters!!", $source
		);

		$this->assertInstanceOf( ValidationError::class, $result );
		$this->assertSame( $source, $result->getSource() );
		$this->assertSame( (string)self::MAX_COMMENT_LENGTH, $result->getValue() );
	}

	public function testValidateValidEditTags(): void {
		$tags = [ self::ALLOWED_TAGS[2], self::ALLOWED_TAGS[0] ];
		$source = 'tags';

		$result = $this->newEditMetadataValidator()->validateEditTags( $tags, $source );

		$this->assertNull( $result );
	}

	/**
	 * @dataProvider invalidEditTagsProvider
	 */
	public function testValidateInvalidEditTags( array $tags, string $invalidTag ): void {
		$source = 'tags';

		$result = $this->newEditMetadataValidator()->validateEditTags( $tags, $source );

		$this->assertInstanceOf( ValidationError::class, $result );
		$this->assertSame( $source, $result->getSource() );
		$this->assertSame( $invalidTag, $result->getValue() );
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
