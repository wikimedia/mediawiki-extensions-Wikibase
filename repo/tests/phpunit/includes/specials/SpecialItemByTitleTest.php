<?php

namespace Wikibase\Test;

/**
 * Tests for the SpecialItemByTitle class.
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
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 * @author Adam Shorland
 */
class SpecialItemByTitleTest extends SpecialPageTestBase {

	protected function newSpecialPage() {
		return new \SpecialItemByTitle();
	}

	public function testExecute() {
		//TODO: Verify that more of the output is correct.

		$matchers['site'] = array(
			'tag' => 'input',
			'attributes' => array(
				'id' => 'wb-itembytitle-sitename',
				'name' => 'site',
		) );
		$matchers['page'] = array(
			'tag' => 'input',
			'attributes' => array(
				'id' => 'pagename',
				'class' => 'wb-input-text',
				'name' => 'page',
		) );
		$matchers['submit'] = array(
			'tag' => 'input',
			'attributes' => array(
				'id' => 'wb-itembytitle-submit',
				'class' => 'wb-input-button',
				'type' => 'submit',
				'name' => 'submit',
		) );

		list( $output, ) = $this->executeSpecialPage( '' );
		foreach( $matchers as $key => $matcher ){
			$this->assertTag( $matcher, $output, "Failed to match html output with tag '{$key}''" );
		}

		list( $output, ) = $this->executeSpecialPage( 'SiteText/PageText' );
		foreach( $matchers as $key => $matcher ){
			if( $key === 'site' ){
				$matcher['attributes']['value'] = 'SiteText';
			}
			if( $key === 'page' ){
				$matcher['attributes']['value'] = 'PageText';
			}
			$this->assertTag( $matcher, $output, "Failed to match html output with tag '{$key}''" );
		}
	}

}