<?php

namespace Wikibase\Lib\Test;

use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\Lib\ClaimGuidValidator;

/**
 * @covers Wikibase\Lib\ClaimGuidValidator
 *
 * @group Wikibase
 * @group WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class ClaimGuidValidatorTest extends \PHPUnit_Framework_TestCase {

	protected function newClaimGuidValidator() {
		$entityIdParser = new BasicEntityIdParser();
		return new ClaimGuidValidator( $entityIdParser );
	}

	/**
	 * @dataProvider validateProvider
	 */
	public function testValidate( $guid ) {
		$claimGuidValidator = $this->newClaimGuidValidator();
		$isValid = $claimGuidValidator->validate( $guid );

		$this->assertTrue( $isValid, "Assert that claim guid $guid is valid" );
	}

	public function validateProvider() {
		return array(
			array( 'q60$5083E43C-228B-4E3E-B82A-4CB20A22A3FB' ),
			array( 'q604192$5672A3B1-7693-4DF9-ADE8-8FC13E095604' ),
			array( 'q37$a212184b-434c-7e90-dd26-29eda5ee2580' ),
			array( 'Q60$5083E43C-228B-4E3E-B82A-4CB20A22A3FA' ),
			array( 'Q604192$5672A3B1-7693-4DF9-ADE8-8FC13E095603' ),
			array( 'Q37$a212184b-434c-7e90-dd26-29eda5ee2581' )
		);
	}

	/**
	 * @dataProvider validateInvalidProvider
	 */
	public function testValidateInvalid( $guid ) {
		$claimGuidValidator = $this->newClaimGuidValidator();
		$isValid = $claimGuidValidator->validate( $guid );

		$this->assertFalse( $isValid, "Assert that claim guid $guid is invalid" );
	}

	public function validateInvalidProvider() {
		return array(
			array( 'q60$5083E43C-228B-4E3E-B82A-4CB20A22A3F' ),
			array( 'q60$5083E43C-228B-4E3E-B82A-$4CB20A22A3FB' ),
			array( '$q60$5083E43C-228B-4E3E-B82A-4CB20A22A3FB' ),
			array( '5083E43C-228B-4E3E-B82A-4CB20A22A3FB' ),
			array( 9000 ),
			array( 'q604192$56723B1-7693-4DF9-ADE8-8FC13E095604' ),
			array( 'q604192$5672w3B1-693-4DF9-ADE8-8FC13E095604' ),
			array( 'q604192$5672w3B1-6935-4F9-ADE8-8FC13E095604' ),
			array( 'q604192$5672w3B1-6935-4DF9-AD8-8FC13E095604' ),
			array( 'q604192$5672w3B1-6935-4DF9-ADE8-8FC13E09604' ),
			array( 'q604192$5672A3B1--7693-4DF9-ADE8-8FC13E095604' ),
			array( 'foo' ),
			array( 'q12345' )
		);
	}

	/**
	 * @dataProvider validateProvider
	 */
	public function testValidateFormat( $guid ) {
		$claimGuidValidator = $this->newClaimGuidValidator();
		$isValid = $claimGuidValidator->validate( $guid );

		$this->assertTrue( $isValid, "Assert that claim guid $guid has a valid format." );
	}

	/**
	 * @dataProvider invalidFormatProvider
	 */
	public function  testInvalidFormat( $guid ) {
		$claimGuidValidator = $this->newClaimGuidValidator();
		$isValid = $claimGuidValidator->validate( $guid );

		$this->assertFalse( $isValid, "Assert that claim guid $guid has an invalid format." );
	}

	public function invalidFormatProvider() {
		return array(
			array( 'q12345' ),
			array( 'q$1$2$3' ),
			array( '$q60$5083E43C-228B-4E3E-B82A-4CB20A22A3FB' )
		);
	}

	/**
	 * @dataProvider validateInvalidPrefixedIdProvider
	 */
	public function testValidateInvalidPrefixedId( $guid ) {
		$claimGuidValidator = $this->newClaimGuidValidator();

		$isValid = $claimGuidValidator->validate( $guid );

		$this->assertFalse( $isValid, "Assert that claim guid prefix is invalid" );
	}

	public function validateInvalidPrefixedIdProvider() {
		return array(
			array( '060$5083E43C-228B-4E3E-B82A-4CB20A22A3FB' ),
			array( 'a060$5083E43C-228B-4E3E-B82A-4CB20A22A3FB' )
		);
	}
}
