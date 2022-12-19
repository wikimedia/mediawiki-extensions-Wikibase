<?php

namespace Wikibase\DataModel\Services\Tests\Statement;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Services\Statement\StatementGuidValidator;

/**
 * @covers \Wikibase\DataModel\Services\Statement\StatementGuidValidator
 *
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class StatementGuidValidatorTest extends TestCase {

	private function newStatementGuidValidator() {
		return new StatementGuidValidator( new ItemIdParser() );
	}

	/**
	 * @dataProvider validateProvider
	 */
	public function testValidate( $guid ) {
		$validator = $this->newStatementGuidValidator();
		$isValid = $validator->validate( $guid );

		$this->assertTrue( $isValid, "Assert that statement guid $guid is valid" );
	}

	public function validateProvider() {
		return [
			[ 'q60$5083E43C-228B-4E3E-B82A-4CB20A22A3FB' ],
			[ 'q604192$5672A3B1-7693-4DF9-ADE8-8FC13E095604' ],
			[ 'q37$a212184b-434c-7e90-dd26-29eda5ee2580' ],
			[ 'Q60$5083E43C-228B-4E3E-B82A-4CB20A22A3FA' ],
			[ 'Q604192$5672A3B1-7693-4DF9-ADE8-8FC13E095603' ],
			[ 'Q37$a212184b-434c-7e90-dd26-29eda5ee2581' ],
		];
	}

	/**
	 * @dataProvider validateInvalidProvider
	 */
	public function testValidateInvalid( $guid ) {
		$validator = $this->newStatementGuidValidator();
		$isValid = $validator->validate( $guid );

		$this->assertFalse( $isValid, "Assert that statement guid $guid is invalid" );
	}

	public function validateInvalidProvider() {
		return [
			[ "Q1$00000000-0000-0000-0000-000000000000\n" ],
			[ 'q60$5083E43C-228B-4E3E-B82A-4CB20A22A3F' ],
			[ 'q60$5083E43C-228B-4E3E-B82A-$4CB20A22A3FB' ],
			[ '$q60$5083E43C-228B-4E3E-B82A-4CB20A22A3FB' ],
			[ '5083E43C-228B-4E3E-B82A-4CB20A22A3FB' ],
			[ 9000 ],
			[ 'q604192$56723B1-7693-4DF9-ADE8-8FC13E095604' ],
			[ 'q604192$5672w3B1-693-4DF9-ADE8-8FC13E095604' ],
			[ 'q604192$5672w3B1-6935-4F9-ADE8-8FC13E095604' ],
			[ 'q604192$5672w3B1-6935-4DF9-AD8-8FC13E095604' ],
			[ 'q604192$5672w3B1-6935-4DF9-ADE8-8FC13E09604' ],
			[ 'q604192$5672A3B1--7693-4DF9-ADE8-8FC13E095604' ],
			[ 'foo' ],
			[ 'q12345' ],
		];
	}

	/**
	 * @dataProvider validateProvider
	 */
	public function testValidateFormat( $guid ) {
		$validator = $this->newStatementGuidValidator();
		$isValid = $validator->validate( $guid );

		$this->assertTrue( $isValid, "Assert that statement guid $guid has a valid format." );
	}

	/**
	 * @dataProvider invalidFormatProvider
	 */
	public function testInvalidFormat( $guid ) {
		$validator = $this->newStatementGuidValidator();
		$isValid = $validator->validate( $guid );

		$this->assertFalse( $isValid, "Assert that statement guid $guid has an invalid format." );
	}

	public function invalidFormatProvider() {
		return [
			[ 'q12345' ],
			[ 'q$1$2$3' ],
			[ '$q60$5083E43C-228B-4E3E-B82A-4CB20A22A3FB' ],
		];
	}

	/**
	 * @dataProvider validateInvalidPrefixedIdProvider
	 */
	public function testValidateInvalidPrefixedId( $guid ) {
		$validator = $this->newStatementGuidValidator();

		$isValid = $validator->validate( $guid );

		$this->assertFalse( $isValid, 'Assert that statement guid prefix is invalid' );
	}

	public function validateInvalidPrefixedIdProvider() {
		return [
			[ '060$5083E43C-228B-4E3E-B82A-4CB20A22A3FB' ],
			[ 'a060$5083E43C-228B-4E3E-B82A-4CB20A22A3FB' ],
		];
	}

}
