<?php

namespace Wikibase\Test;
use Wikibase\Snaks;
use Wikibase\EntityId;

/**
 * Unit tests for the Wikibase\class ApiSetReference class.
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
 * @since 0.3
 *
 * @ingroup WikibaseTest
 *
 * @group API
 * @group Database
 * @group Wikibase
 * @group WikibaseAPI
 * @group WikibaseRepo
 *
 * @group medium
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ApiSetReferenceTest extends \ApiTestCase {

	public function testValidRequests() {
		$item = \Wikibase\ItemObject::newEmpty();
		$content = new \Wikibase\ItemContent( $item );
		$content->save( '', null, EDIT_NEW );

		$statement = new \Wikibase\StatementObject( new \Wikibase\PropertyNoValueSnak( 42 ) );
		$statement->setGuid( $item->newClaimGuid() );

		$reference = new \Wikibase\ReferenceObject( new \Wikibase\SnakList(
			array( new \Wikibase\PropertySomeValueSnak( 1 ) )
		) );

		$statement->getReferences()->addReference( $reference );

		$item->addClaim( $statement );

		$content->save( '' );
	}

	protected function makeRequest( Snaks $snaks ) {
		$serializer = new Snak

		$params = array(
			'action' => 'wbsetreference',
			'snaks' => $claimGuid,
			'statement' => $value,
		);

		list( $resultArray, ) = $this->doApiRequest( $params );

		$this->assertInternalType( 'array', $resultArray, 'top level element is an array' );
		$this->assertArrayHasKey( 'claim', $resultArray, 'top level element has a claim key' );

		$claim = $resultArray['claim'];
	}

}
