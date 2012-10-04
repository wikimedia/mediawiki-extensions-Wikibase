<?php

namespace Wikibase\Test;
use Wikibase\StatementObject as StatementObject;
use Wikibase\Statement as Statement;
use Wikibase\ClaimObject as ClaimObject;
use Wikibase\Claim as Claim;
use Wikibase\ReferenceObject as ReferenceObject;
use Wikibase\Reference as Reference;
use \DataValues\StringValue;

/**
 * Tests for the Wikibase\StatementObject class.
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
 * @ingroup Wikibase
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseLib
 * @group WikibaseStatement
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class StatementObjectTest extends \MediaWikiTestCase {

	public function testNewForEntity() {
		$entity = \Wikibase\ItemObject::newEmpty();
		$entity->setId( 42 );

		$instance = StatementObject::newForEntity( $entity, new ClaimObject( new \Wikibase\PropertyNoValueSnak( 42 ) ) );

		$this->assertInstanceOf( '\Wikibase\Statement', $instance );
	}

	public function instanceProvider() {
		$instances = array();

		$entity = \Wikibase\ItemObject::newEmpty();
		$entity->setId( 42 );

		$newEntity = clone $entity;
		$newEntity->setId( 43 );
		$newEntity->addAliases( 'en', array( 'foo', 'bar', 'baz' ) );

		$baseInstance = StatementObject::newForEntity( $entity, new ClaimObject( new \Wikibase\PropertyNoValueSnak( 42 ) ) );

		$instances[] = $baseInstance;

		$instance = clone $baseInstance;
		$instance->setRank( Statement::RANK_PREFERRED );

		$instances[] = $instance;

		$newInstance = clone $instance;
		$newInstance->setEntity( $newEntity );

		$instances[] = $newInstance;

		$instance = clone $baseInstance;

		$instance->setReferences( new \Wikibase\ReferenceList(
			new ReferenceObject( new \Wikibase\SnakList(
				new \Wikibase\PropertyValueSnak( 1, new StringValue( 'a' ) )
			) )
		) );

		$instances[] = $instance;

		return $this->arrayWrap( $instances );
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testGetReferences( Statement $statement ) {
		$this->assertInstanceOf( '\Wikibase\References', $statement->getReferences() );
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testSetReferences( Statement $statement ) {
		$references = new \Wikibase\ReferenceList(
			new ReferenceObject( new \Wikibase\SnakList(
				new \Wikibase\PropertyValueSnak( 1, new StringValue( 'a' ) )
			) )
		);


		$statement->setReferences( $references );

		$this->assertEquals( $references, $statement->getReferences() );
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testGetRank( Statement $statement ) {
		$rank = $statement->getRank();
		$this->assertInternalType( 'integer', $rank );

		$ranks = array( Statement::RANK_DEPRECATED, Statement::RANK_NORMAL, Statement::RANK_PREFERRED );
		$this->assertTrue( in_array( $rank, $ranks ), true );
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testSetRank( Statement $statement ) {
		$statement->setRank( Statement::RANK_DEPRECATED );
		$this->assertEquals( Statement::RANK_DEPRECATED, $statement->getRank() );

		$pokemons = null;

		try {
			$statement->setRank( 9001 );
		}
		catch ( \Exception $pokemons ) {}

		$this->assertInstanceOf( '\MWException', $pokemons );
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testSetClaim( Statement $statement ) {
		$claim = new ClaimObject( new \Wikibase\PropertyNoValueSnak( 50 ) );
		$statement->setClaim( $claim );
		$this->assertEquals( $claim, $statement->getClaim() );
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testGetClaim( Statement $statement ) {
		$this->assertInstanceOf( '\Wikibase\Claim', $statement->getClaim() );
	}

}
