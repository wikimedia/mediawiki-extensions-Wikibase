<?php

namespace Wikibase\Test\Api;
use Wikibase\Item;
use Wikibase\Snak;
use Wikibase\Statement;
use Wikibase\Lib\Serializers\ClaimSerializer;

/**
 * Unit tests for the Wikibase\Api\SetStatementRank class.
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
 * @group SetStatementRankTest
 *
 * @group medium
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class SetStatementRankTest extends \ApiTestCase {

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

		$ranks = array(
			Statement::RANK_DEPRECATED,
			Statement::RANK_NORMAL,
			Statement::RANK_PREFERRED
		);

		/**
		 * @var Statement[] $statements
		 */
		foreach ( $statements as &$statement ) {
			$statement->setRank( $ranks[array_rand( $ranks )] );
		}

		return $statements;
	}

	public function testRequests() {
		$ranks = ClaimSerializer::getRanks();

		foreach ( $this->statementProvider() as $statement ) {
			$item = \Wikibase\Item::newEmpty();
			$content = new \Wikibase\ItemContent( $item );
			$content->save( '', null, EDIT_NEW );

			$guidGenerator = new \Wikibase\Lib\ClaimGuidGenerator( $item->getId() );
			$statement->setGuid( $guidGenerator->newGuid() );
			$item->addClaim( $statement );

			$content->save( '' );

			while ( true ) {
				$rank = $ranks[array_rand( $ranks )];

				if ( ClaimSerializer::unserializeRank( $rank ) !== $statement->getRank() ) {
					break;
				}
			}

			$this->makeValidRequest(
				$item,
				$statement->getGuid(),
				$rank
			);

			$this->makeInvalidRequest(
				$statement->getGuid(),
				'~=[,,_,,]:3'
			);
		}

		$this->makeInvalidRequest(
			'~=[,,_,,]:3',
			reset( $ranks )
		);
	}

	protected function makeValidRequest( Item $item, $statementGuid, $statementRank ) {
		$this->assertInternalType( 'string', $statementGuid );
		$this->assertInternalType( 'string', $statementRank );

		$params = array(
			'action' => 'wbsetstatementrank',
			'statement' => $statementGuid,
			'rank' => $statementRank,
			'token' => $GLOBALS['wgUser']->getEditToken()
		);

		list( $resultArray, ) = $this->doApiRequest( $params );

		$this->assertInternalType( 'array', $resultArray, 'top level element is an array' );
		$this->assertArrayHasKey( 'pageinfo', $resultArray, 'top level element has a pageinfo key' );
		$this->assertArrayHasKey( 'statement', $resultArray, 'top level element has a statement key' );

		$statement = $resultArray['statement'];
		$this->assertArrayHasKey( 'rank', $statement, 'statement element has a rank key' );

		$this->assertEquals( $statementRank, $statement['rank'] );

		$itemContent = \Wikibase\EntityContentFactory::singleton()->getFromId( $item->getId() );

		$this->assertInstanceOf( '\Wikibase\ItemContent', $itemContent );

		$freshItem = $itemContent->getEntity();

		$claims = new \Wikibase\Claims( $freshItem->getClaims() );

		$this->assertTrue( $claims->hasClaimWithGuid( $statementGuid ) );

		/**
		 * @var Statement $claim
		 */
		$claim = $claims->getClaimWithGuid( $statementGuid );

		$this->assertEquals(
			ClaimSerializer::unserializeRank( $statementRank ),
			$claim->getRank()
		);
	}

	protected function makeInvalidRequest( $statementGuid, $statementRank, $expectedError = null ) {
		$params = array(
			'action' => 'wbsetstatementrank',
			'statement' => $statementGuid,
			'rank' => $statementRank,
			'token' => $GLOBALS['wgUser']->getEditToken()
		);

		try {
			$this->doApiRequest( $params );
			$this->assertFalse( true, 'Invalid request should raise an exception' );
		}
		catch ( \Exception $e ) {
			if ( $e instanceof \UsageException ) {
				if ( $expectedError === null ) {
					$this->assertTrue( true, 'Invalid request raised error' );
				}
				else {
					$this->assertEquals( $expectedError, $e->getCodeString(), 'Invalid request raised correct error' );
				}
			}
			elseif ( $e instanceof \MWException ) {
				$this->assertTrue( true, 'Invalid request raised error' );
			}
			else {
				throw $e;
			}
		}
	}

	/**
	 * @dataProvider invalidClaimProvider
	 */
	public function testInvalidClaimGuid( $claimGuid ) {
		$caughtException = false;

		$ranks = ClaimSerializer::getRanks();

		$params = array(
			'action' => 'wbsetstatementrank',
			'statement' => $claimGuid,
			'rank' => $ranks[0],
			'token' => $GLOBALS['wgUser']->getEditToken()
		);

		try {
			$this->doApiRequest( $params );
		} catch ( \UsageException $e ) {
			$this->assertEquals( $e->getCodeString(), 'setstatementrank-invalid-guid', 'Invalid claim guid raised correct error' );
			$caughtException = true;
		}

		$this->assertTrue( $caughtException, 'Exception was caught' );
	}

	public function invalidClaimProvider() {
		return array(
			array( 'xyz' ),
			array( 'x$y$z' )
		);
	}

}
