<?php

namespace Wikibase\QueryEngine\Tests\SQLStore;

use Ask\Language\Description\AnyValue;
use Ask\Language\Description\SomeProperty;
use Ask\Language\Option\QueryOptions;
use DataValues\PropertyValue;
use Wikibase\Database\FieldDefinition;
use Wikibase\Database\TableDefinition;
use Wikibase\EntityId;
use Wikibase\QueryEngine\SQLStore\DataValueTable;
use Wikibase\QueryEngine\SQLStore\Engine\DescriptionMatchFinder;
use Wikibase\QueryEngine\SQLStore\EntityIdTransformer;

/**
 * @covers Wikibase\QueryEngine\SQLStore\Engine\DescriptionMatchFinder
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
class DescriptionMatchFinderTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {
		$this->newInstanceWithMocks();
		$this->assertTrue( true );
	}

	protected function newInstanceWithMocks() {
		return new DescriptionMatchFinder(
			$this->getMock( 'Wikibase\Database\QueryInterface' ),
			$this->getMockBuilder( 'Wikibase\QueryEngine\SQLStore\Schema' )
				->disableOriginalConstructor()->getMock(),
			$this->getMock( 'Wikibase\QueryEngine\SQLStore\PropertyDataValueTypeLookup' ),
			$this->getMock( 'Wikibase\QueryEngine\SQLStore\InternalEntityIdFinder' )
		);
	}

	public function testFindMatchingEntitiesWithSomePropertyAnyValue() {
		$description = new SomeProperty( new EntityId( 'item', 42 ), new AnyValue() );
		$queryOptions = new QueryOptions( 100, 0 );

		$queryEngine = $this->getMock( 'Wikibase\Database\QueryInterface' );

		$queryEngine->expects( $this->once() )
			->method( 'select' )
			->with(
				$this->equalTo( 'tablename' ),
				$this->equalTo( array( 'subject_id' ) )
			)
			->will( $this->returnValue( array(
				(object)array( 'subject_id' => 10 )
			) ) );

		$schema = $this->getMockBuilder( 'Wikibase\QueryEngine\SQLStore\Schema' )
			->disableOriginalConstructor()->getMock();

		$dvHandler = $this->getMockBuilder( 'Wikibase\QueryEngine\SQLStore\DataValueHandler' )
			->disableOriginalConstructor()->getMock();

		$dvHandler->expects( $this->any() )
			->method( 'getWhereConditions' )
			->will( $this->returnValue( array() ) );

		$dvTable = new DataValueTable(
			new TableDefinition( 'tablename', array( new FieldDefinition( 'dsfdfdsfds', FieldDefinition::TYPE_BOOLEAN ) ) ),
			'foo',
			'bar'
		);

		$dvHandler->expects( $this->any() )
			->method( 'getDataValueTable' )
			->will( $this->returnValue( $dvTable ) );

		$schema->expects( $this->once() )
			->method( 'getDataValueHandler' )
			->will( $this->returnValue( $dvHandler ) );

		$dvTypeLookup = $this->getMock( 'Wikibase\QueryEngine\SQLStore\PropertyDataValueTypeLookup' );

		$idTransformer = $this->getMock( 'Wikibase\QueryEngine\SQLStore\InternalEntityIdFinder' );

		$matchFinder = new DescriptionMatchFinder(
			$queryEngine,
			$schema,
			$dvTypeLookup,
			$idTransformer
		);

		$matchingInternalIds = $matchFinder->findMatchingEntities( $description, $queryOptions );

		$this->assertInternalType( 'array', $matchingInternalIds );
		$this->assertContainsOnly( 'int', $matchingInternalIds );
		$this->assertEquals( array( 10 ), $matchingInternalIds );
	}

}
