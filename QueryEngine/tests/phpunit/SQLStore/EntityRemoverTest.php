<?php

namespace Wikibase\QueryEngine\Tests\SQLStore;

use Wikibase\Claim;
use Wikibase\Database\FieldDefinition;
use Wikibase\Database\TableDefinition;
use Wikibase\Entity;
use Wikibase\Item;
use Wikibase\Property;
use Wikibase\PropertyNoValueSnak;
use Wikibase\QueryEngine\SQLStore\DataValueTable;
use Wikibase\QueryEngine\SQLStore\EntityRemover;

/**
 * @covers Wikibase\QueryEngine\SQLStore\EntityRemover
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
 * @ingroup WikibaseQueryEngineTest
 *
 * @group Wikibase
 * @group WikibaseQueryEngine
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class EntityRemoverTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider entityProvider
	 */
	public function testRemoveEntity( Entity $entity ) {
		$internalSubjectId = 9001;

		$claimTable = $this
			->getMockBuilder( 'Wikibase\QueryEngine\SQLStore\ClaimStore\ClaimsTable' )
			->disableOriginalConstructor()
			->getMock();

		$claimTable->expects( $this->once() )
			->method( 'removeClaimsOfSubject' )
			->with( $this->equalTo( $internalSubjectId ) );

		$snakRemover = $this->getMockBuilder( 'Wikibase\QueryEngine\SQLStore\SnakStore\SnakRemover' )
			->disableOriginalConstructor()
			->getMock();

		$snakRemover->expects( $this->once() )
			->method( 'removeSnaksOfSubject' )
			->with( $this->equalTo( $internalSubjectId ) );

		$idFinder = $this->getMock( 'Wikibase\QueryEngine\SQLStore\InternalEntityIdFinder' );

		$idFinder->expects( $this->any() )
			->method( 'getInternalIdForEntity' )
			->with(
				$entity->getId()
			)
			->will( $this->returnValue( $internalSubjectId ) );

		$remover = new EntityRemover( $claimTable, $snakRemover, $idFinder );

		$remover->removeEntity( $entity );
	}

	public function entityProvider() {
		$argLists = array();

		$item = Item::newEmpty();
		$item->setId( 42 );

		$argLists[] = array( $item );


		$item = Item::newEmpty();
		$item->setId( 31337 );

		$argLists[] = array( $item );


		$property = Property::newEmpty();
		$property->setDataTypeId( 'string' );
		$property->setId( 9001 );

		$argLists[] = array( $property );


		$property = Property::newEmpty();
		$property->setDataTypeId( 'string' );
		$property->setId( 1 );
		$property->addAliases( 'en', array( 'foo', 'bar', 'baz' ) );
		$property->addClaim( new Claim( new PropertyNoValueSnak( 42 ) ) );

		$argLists[] = array( $property );


		$item = Item::newEmpty();
		$item->setId( 2 );
		$item->addClaim( new Claim( new PropertyNoValueSnak( 42 ) ) );
		$item->addClaim( new Claim( new PropertyNoValueSnak( 43 ) ) );
		$item->addClaim( new Claim( new PropertyNoValueSnak( 44 ) ) );

		$argLists[] = array( $item );

		return $argLists;
	}

}
