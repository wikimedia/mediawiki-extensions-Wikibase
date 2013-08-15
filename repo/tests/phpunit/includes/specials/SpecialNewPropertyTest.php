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

		$matchers['label'] = array(
			'tag' => 'input',
			'attributes' => array(
				'id' => 'wb-newentity-label',
				'class' => 'wb-input',
				'name' => 'label',
			) );
		$matchers['description'] = array(
			'tag' => 'input',
			'attributes' => array(
				'id' => 'wb-newentity-description',
				'class' => 'wb-input',
				'name' => 'description',
			) );
		$matchers['submit'] = array(
			'tag' => 'input',
			'attributes' => array(
				'id' => 'wb-newentity-submit',
				'class' => 'wb-button',
				'type' => 'submit',
				'name' => 'submit',
			) );

		list( $output, ) = $this->executeSpecialPage( '' );
		foreach( $matchers as $key => $matcher ){
			$this->assertTag( $matcher, $output, "Failed to match html output with tag '{$key}''" );
		}

		list( $output, ) = $this->executeSpecialPage( 'LabelText/DescriptionText' );
		foreach( $matchers as $key => $matcher ){
			if( $key === 'label' ){
				$matcher['attributes']['value'] = 'LabelText';
			}
			if( $key === 'description' ){
				$matcher['attributes']['value'] = 'DescriptionText';
			}
			$this->assertTag( $matcher, $output, "Failed to match html output with tag '{$key}''" );
		}
	}

}