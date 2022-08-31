<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Infrastructure;

use PHPUnit\Framework\TestCase;
use Swaggest\JsonDiff\JsonDiff;
use Wikibase\Repo\RestApi\Infrastructure\JsonDiffJsonPatchValidator;

/**
 * @covers \Wikibase\Repo\RestApi\Infrastructure\JsonDiffJsonPatchValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class JsonDiffJsonPatchValidatorTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		if ( !class_exists( JsonDiff::class ) ) {
			$this->markTestSkipped( 'Skipping while swaggest/json-diff has not made it to mediawiki/vendor yet (T316245).' );
		}
	}

	public function testInvalidJsonPatch(): void {
		$source = 'test source';
		$error = ( new JsonDiffJsonPatchValidator() )->validate( [ 'invalid JSON Patch' ], $source );

		$this->assertSame( $source, $error->getSource() );
		$this->assertEmpty( $error->getValue() );
	}

	public function testValidJsonPatch(): void {
		$validPatch = [ [
			'op' => 'replace',
			'path' => '/mainsnak/datavalue/value',
			'value' => 'patched',
		] ];
		$this->assertNull(
			( new JsonDiffJsonPatchValidator() )->validate( $validPatch, 'test source' )
		);
	}

}
