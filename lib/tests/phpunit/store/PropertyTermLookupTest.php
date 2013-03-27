<?php

namespace Wikibase\Test;
use Wikibase\PropertyTermLookup;
use Wikibase\EntityFactory;
use Wikibase\EntityId;
use Wikibase\Property;
use Wikibase\Item;

/**
 * Tests for the Wikibase\PropertyTermLookup class.
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
 * @since 0.4
 *
 * @ingroup WikibaseLib
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Daniel Kinzler
 */
class PropertyTermLookupTest extends PropertyLookupTest {

	/**
	 * @var \Wikibase\TermIndex
	 */
	protected $termIndex;

	/**
	 * @var \Wikibase\PropertyTermLookup
	 */
	protected $propertyLookup;

	public function setUp() {
		parent::setUp();

		$this->termIndex = new \Wikibase\Test\MockTermIndex();

		foreach( $this->entities as $entity ) {
			$this->termIndex->saveTermsOfEntity( $property );
		}

		$this->propertyLookup = new PropertyTermLookup( $this->termIndex );
	}

	public function testConstructor() {
		$instance = new PropertyTermLookup( $this->termIndex );
		$this->assertInstanceOf( '\Wikibase\PropertyTermLookup', $instance );
	}
}
