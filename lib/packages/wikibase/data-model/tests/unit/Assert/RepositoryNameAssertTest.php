<?php

namespace Wikibase\DataModel\Tests\Assert;

use Wikibase\DataModel\Assert\RepositoryNameAssert;
use Wikimedia\Assert\ParameterAssertionException;

/**
 * @covers \Wikibase\DataModel\Assert\RepositoryNameAssert
 *
 * @license GPL-2.0-or-later
 */
class RepositoryNameAssertTest extends \PHPUnit\Framework\TestCase {

	public function provideInvalidRepositoryNames() {
		return [
			[ 'fo:o' ],
			[ 'foo:' ],
			[ ':foo' ],
			[ ':' ],
			[ 'fo.o' ],
			[ 'foo.' ],
			[ '.foo' ],
			[ '.' ],
			[ 123 ],
			[ null ],
			[ false ],
			[ [ 'foo' ] ],
		];
	}

	/**
	 * @dataProvider provideInvalidRepositoryNames
	 */
	public function testGivenInvalidValue_assertParameterIsValidRepositoryNameFails( $value ) {
		$this->expectException( ParameterAssertionException::class );
		RepositoryNameAssert::assertParameterIsValidRepositoryName( $value, 'test' );
	}

	public function provideValidRepositoryNames() {
		return [
			[ '' ],
			[ 'foo' ],
			[ '123' ],
		];
	}

	/**
	 * @dataProvider provideValidRepositoryNames
	 */
	public function testGivenValidValue_assertParameterIsValidRepositoryNamePasses( $value ) {
		RepositoryNameAssert::assertParameterIsValidRepositoryName( $value, 'test' );
		$this->addToAssertionCount( 1 );
	}

	public function provideInvalidRepositoryNameIndexedArrays() {
		return [
			[ 'foo' ],
			[ [ 0 => 'foo' ] ],
			[ [ 'fo:0' => 'bar' ] ],
			[ [ 'foo:' => 'bar' ] ],
			[ [ ':foo' => 'bar' ] ],
		];
	}

	/**
	 * @dataProvider provideInvalidRepositoryNameIndexedArrays
	 */
	public function testGivenInvalidValue_assertParameterKeysAreValidRepositoryNamesFails( $values ) {
		$this->expectException( ParameterAssertionException::class );
		RepositoryNameAssert::assertParameterKeysAreValidRepositoryNames( $values, 'test' );
	}

	public function provideValidRepositoryNameIndexedArrays() {
		return [
			[ [ 'foo' => 'bar' ] ],
			[ [ '' => 'bar' ] ],
			[ [ '' => 'bar', 'foo' => 'baz' ] ],
			[ [] ],
		];
	}

	/**
	 * @dataProvider provideValidRepositoryNameIndexedArrays
	 */
	public function testGivenValidValue_assertParameterKeysAreValidRepositoryNamesPasses( array $values ) {
		RepositoryNameAssert::assertParameterKeysAreValidRepositoryNames( $values, 'test' );
		$this->addToAssertionCount( 1 );
	}

}
