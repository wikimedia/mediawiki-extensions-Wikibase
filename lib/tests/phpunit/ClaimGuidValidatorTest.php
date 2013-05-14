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

	public function validateProvider() {
		return array(
			array( 'q60$5083E43C-228B-4E3E-B82A-4CB20A22A3FB', true ),
			array( 'q60$5083E43C-228B-4E3E-B82A-$4CB20A22A3FB', false ),
			array( '$q60$5083E43C-228B-4E3E-B82A-4CB20A22A3FB', false )
		);
	}

	/**
	 * @dataProvider validateProvider
	 */
	public function testValidate( $guid, $expected ) {
		$claimGuidValidator = new ClaimGuidValidator();
		$isValid = $claimGuidValidator->validate( $guid );

		$this->assertEquals( $expected, $isValid );
	}

}
