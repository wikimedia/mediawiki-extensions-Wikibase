<?php

namespace Wikibase\Repo\Tests\Validators;

use DataValues\StringValue;
use InvalidArgumentException;
use Wikibase\Repo\CachingCommonsMediaFileNameLookup;
use Wikibase\Repo\Validators\CommonsMediaExistsValidator;

/**
 * @covers \Wikibase\Repo\Validators\CommonsMediaExistsValidator
 *
 * @group Wikibase
 * @group WikibaseValidators
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch
 */
class CommonsMediaExistsValidatorTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @return CachingCommonsMediaFileNameLookup
	 */
	private function getCachingCommonsMediaFileNameLookup() {
		$fileNameLookup = $this->createMock( CachingCommonsMediaFileNameLookup::class );

		$fileNameLookup->method( 'lookupFileName' )
			->with( $this->isType( 'string' ) )
			->willReturnCallback( function( $fileName ) {
				return strpos( $fileName, 'NOT-FOUND' ) === false ? $fileName : null;
			} );

		return $fileNameLookup;
	}

	/**
	 * @dataProvider provideValidate()
	 */
	public function testValidate( $expected, $value ) {
		$validator = new CommonsMediaExistsValidator( $this->getCachingCommonsMediaFileNameLookup() );

		$this->assertSame(
			$expected,
			$validator->validate( $value )->isValid()
		);
	}

	public function provideValidate() {
		return [
			"Valid, plain string" => [
				true, "Foo.png",
			],
			"Valid, StringValue" => [
				true, new StringValue( "Foo.png" ),
			],
			"Invalid, StringValue" => [
				false, new StringValue( "Foo.NOT-FOUND.png" ),
			],
		];
	}

	public function testValidate_noString() {
		$validator = new CommonsMediaExistsValidator( $this->getCachingCommonsMediaFileNameLookup() );

		$this->expectException( InvalidArgumentException::class );
		$validator->validate( 5 );
	}

}
