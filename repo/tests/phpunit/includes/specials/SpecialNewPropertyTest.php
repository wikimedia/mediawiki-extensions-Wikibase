<?php

namespace Wikibase\Test;

/**
 * Tests for the SpecialNewProperty class.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @since 0.1
 *
 * @ingroup WikibaseRepoTest
 * @ingroup Test
 *
 * @group Wikibase
 * @group SpecialPage
 * @group WikibaseSpecialPage
 *
 * @licence GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Adam Shorland
 */
class SpecialNewPropertyTest extends SpecialPageTestBase {

	protected function newSpecialPage() {
		return new \SpecialNewProperty();
	}

	public function testExecute() {
		//TODO: Verify that more of the output is correct.
		//TODO: Verify that item creation works via a faux post request

		$expectedInputs = array(
			'label' => array(
				'id' => 'wb-newentity-label',
				'class' => 'wb-input',
				'name' => 'label' ),
			'description' => array(
				'id' => 'wb-newentity-description',
				'class' => 'wb-input',
				'name' => 'description' ),
			'submit' => array(
				'id' => 'wb-newentity-submit',
				'class' => 'wb-button',
				'type' => 'submit',
				'name' => 'submit' ),
		);

		list( $output, ) = $this->executeSpecialPage( '' );
		// -- Make sure the special page loads with expected input fields ----
		foreach( $expectedInputs as $expected ){
			$this->assertHasHtmlTagWithElements( $output, 'input', $expected );
		}

		list( $output, ) = $this->executeSpecialPage( 'LabelText/DescriptionText' );
		// -- Make sure the subpage values have been passed to the correct input fields ----
		$this->assertHasHtmlTagWithElements( $output, 'input',
			array_merge( $expectedInputs['label'], array( 'value' => 'LabelText' ) ) );
		$this->assertHasHtmlTagWithElements( $output, 'input',
			array_merge( $expectedInputs['description'], array( 'value' => 'DescriptionText' ) ) );
	}

}