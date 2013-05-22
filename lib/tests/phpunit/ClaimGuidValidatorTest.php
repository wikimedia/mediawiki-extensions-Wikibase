<?php

namespace Wikibase\Lib\Test;

use Wikibase\Lib\ClaimGuidValidator;

/**
 * Tests for the ClaimGuidValidator class.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA
 *
 * @file
 * @since 0.4
 *
 * @ingroup Wikibase
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class ClaimGuidValidatorTest extends \PHPUnit_Framework_TestCase {

	protected $entityPrefixes;

	public function setUp() {
		parent::setUp();

		$this->entityPrefixes = array(
			'q' => \Wikibase\Item::ENTITY_TYPE,
			'p' => \Wikibase\Property::ENTITY_TYPE,
		);
	}

	/**
	 * @dataProvider validateProvider
	 */
	public function testValidate( $guid ) {
		$claimGuidValidator = new ClaimGuidValidator( $this->entityPrefixes );
		$isValid = $claimGuidValidator->validate( $guid );

		$this->assertTrue( $isValid, "Assert that claim guid $guid is valid" );
	}

	public function validateProvider() {
		return array(
			array( 'q60$5083E43C-228B-4E3E-B82A-4CB20A22A3FB' ),
			array( 'q604192$5672A3B1-7693-4DF9-ADE8-8FC13E095604' ),
			array( 'q37$a212184b-434c-7e90-dd26-29eda5ee2580' )
		);
	}

	/**
	 * @dataProvider validateInvalidProvider
	 */
	public function testValidateInvalid( $guid ) {
		$claimGuidValidator = new ClaimGuidValidator( $this->entityPrefixes );
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
		$claimGuidValidator = new ClaimGuidValidator( $this->entityPrefixes );
		$isValid = $claimGuidValidator->validate( $guid );

		$this->assertTrue( $isValid, "Assert that claim guid $guid has a valid format." );
	}

	/**
	 * @dataProvider invalidFormatProvider
	 */
	public function  testInvalidFormat( $guid ) {
		$claimGuidValidator = new ClaimGuidValidator( $this->entityPrefixes );
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
		$claimGuidValidator = new ClaimGuidValidator( $this->entityPrefixes );

		$this->setExpectedException( 'ValueParsers\ParseException' );

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
