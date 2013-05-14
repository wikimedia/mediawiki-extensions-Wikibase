<?php

namespace Wikibase\Test\Api;
use Wikibase\Reference;
use Wikibase\Snak;
use Wikibase\Statement;

/**
 * Unit tests for the Wikibase\Api\RemoveReferences class.
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
 * @ingroup WikibaseRepoTest
 *
 * @group API
 * @group Database
 * @group Wikibase
 * @group WikibaseAPI
 * @group WikibaseRepo
 * @group RemoveReferencesTest
 *
 * @group medium
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class RemoveReferencesTest extends \ApiTestCase {

	/**
	 * @return Snak[]
	 */
	protected function snakProvider() {
		$snaks = array();

		$snaks[] = new \Wikibase\PropertyNoValueSnak( 42 );
		$snaks[] = new \Wikibase\PropertySomeValueSnak( 9001 );
		$snaks[] = new \Wikibase\PropertyValueSnak( 7201010, new \DataValues\StringValue( 'o_O' ) );

		return $snaks;
	}

	/**
	 * @return Statement[]
	 */
	protected function statementProvider() {
		$statements = array();

		$mainSnak = new \Wikibase\PropertyNoValueSnak( 42 );
		$statement = new \Wikibase\Statement( $mainSnak );
		$statements[] = $statement;

		foreach ( $this->snakProvider() as $snak ) {
			$statement = clone $statement;
			$snaks = new \Wikibase\SnakList( array( $snak ) );
			$statement->getReferences()->addReference( new \Wikibase\Reference( $snaks ) );
			$statements[] = $statement;
		}

		$statement = clone $statement;
		$snaks = new \Wikibase\SnakList( $this->snakProvider() );
		$statement->getReferences()->addReference( new \Wikibase\Reference( $snaks ) );
		$statements[] = $statement;

		return $statements;
	}

	public function testRequests() {
		foreach ( $this->statementProvider() as $statement ) {
			$item = \Wikibase\Item::newEmpty();
			$content = new \Wikibase\ItemContent( $item );
			$content->save( '', null, EDIT_NEW );

			$guidGenerator = new \Wikibase\Lib\ClaimGuidGenerator( $item->getId() );
			$statement->setGuid( $guidGenerator->newGuid() );
			$item->addClaim( $statement );

			$content->save( '' );

			$references = $statement->getReferences();

			$hashes = array_map(
				function( Reference $reference ) {
					return $reference->getHash();
				},
				iterator_to_array( $references )
			);

			$this->assertInternalType( 'string', $statement->getGuid() );

			if ( count( $references ) === 0 ) {
				$this->makeInvalidRequest(
					$statement->getGuid(),
					array( '~=[,,_,,]:3' ),
					'removereferences-no-such-reference'
				);
			}
			else {
				$this->makeValidRequest(
					$statement->getGuid(),
					$hashes
				);
			}
		}
	}

	protected function makeValidRequest( $statementGuid, array $hashes ) {
		$params = array(
			'action' => 'wbremovereferences',
			'statement' => $statementGuid,
			'references' => implode( '|', $hashes ),
			'token' => $GLOBALS['wgUser']->getEditToken()
		);

		list( $resultArray, ) = $this->doApiRequest( $params );

		$this->assertInternalType( 'array', $resultArray, 'top level element is an array' );
		$this->assertArrayHasKey( 'pageinfo', $resultArray, 'top level element has a pageinfo key' );

		$this->makeInvalidRequest( $statementGuid, $hashes, 'removereferences-no-such-reference' );
	}

	protected function makeInvalidRequest( $statementGuid, array $hashes, $expectedError = null ) {
		$params = array(
			'action' => 'wbremovereferences',
			'statement' => $statementGuid,
			'references' => implode( '|', $hashes ),
			'token' => $GLOBALS['wgUser']->getEditToken()
		);

		try {
			$this->doApiRequest( $params );
			$this->assertFalse( true, 'Invalid request should raise an exception' );
		}
		catch ( \UsageException $e ) {
			if ( $expectedError === null ) {
				$this->assertTrue( true, 'Invalid request raised error' );
			}
			else {
				$this->assertEquals( $expectedError, $e->getCodeString(), 'Invalid request raised correct error' );
			}
		}
	}

	/**
	 * @dataProvider invalidGuidProvider
	 */
	public function testInvalidStatementGuid( $statementGuid, $hash ) {
		$caughtException = false;

		$params = array(
			'action' => 'wbremovereferences',
			'statement' => $statementGuid,
			'references' => $hash,
			'token' => $GLOBALS['wgUser']->getEditToken()
		);

		try {
			$this->doApiRequest( $params );
		} catch ( \UsageException $e ) {
			$this->assertEquals( $e->getCodeString(), 'removereferences-invalid-guid',  'Invalid statement guid raised correct error' );
			$caughtException = true;
		}

		$this->assertTrue( $caughtException );
	}

	public function invalidGuidProvider() {
		$snak = new \Wikibase\PropertyValueSnak( 722, new \DataValues\StringValue( 'abc') );
		$hash = $snak->getHash();

		return array(
			array( 'xyz', $hash ),
			array( 'x$y$z', $hash )
		);
	}

}
