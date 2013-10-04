<?php

namespace Wikibase\Test\Api;

use DataValues\StringValue;
use Wikibase\Item;
use Wikibase\ItemContent;
use Wikibase\Lib\ClaimGuidGenerator;
use Wikibase\PropertyNoValueSnak;
use Wikibase\PropertySomeValueSnak;
use Wikibase\PropertyValueSnak;
use Wikibase\Reference;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Snak;
use Wikibase\SnakList;
use Wikibase\Statement;
use Wikibase\Lib\Serializers\ClaimSerializer;

/**
 * @covers Wikibase\Api\SetStatementRank
 *
 * @since 0.3
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
class SetStatementRankTest extends WikibaseApiTestCase {

	/**
	 * @return Snak[]
	 */
	protected function snakProvider() {
		$snaks = array();

		$snaks[] = new PropertyNoValueSnak( 42 );
		$snaks[] = new PropertySomeValueSnak( 9001 );
		$snaks[] = new PropertyValueSnak( 7201010, new StringValue( 'o_O' ) );

		return $snaks;
	}

	/**
	 * @return Statement[]
	 */
	protected function statementProvider() {
		$statements = array();

		$mainSnak = new PropertyNoValueSnak( 42 );
		$statement = new Statement( $mainSnak );
		$statements[] = $statement;

		foreach ( $this->snakProvider() as $snak ) {
			$statement = clone $statement;
			$snaks = new SnakList( array( $snak ) );
			$statement->getReferences()->addReference( new Reference( $snaks ) );
			$statements[] = $statement;
		}

		$statement = clone $statement;
		$snaks = new SnakList( $this->snakProvider() );
		$statement->getReferences()->addReference( new Reference( $snaks ) );
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
			$item = Item::newEmpty();
			$content = new ItemContent( $item );
			$content->save( '', null, EDIT_NEW );

			$guidGenerator = new ClaimGuidGenerator( $item->getId() );
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
		);

		list( $resultArray, ) = $this->doApiRequestWithToken( $params );

		$this->assertInternalType( 'array', $resultArray, 'top level element is an array' );
		$this->assertArrayHasKey( 'pageinfo', $resultArray, 'top level element has a pageinfo key' );
		$this->assertArrayHasKey( 'statement', $resultArray, 'top level element has a statement key' );

		$statement = $resultArray['statement'];
		$this->assertArrayHasKey( 'rank', $statement, 'statement element has a rank key' );

		$this->assertEquals( $statementRank, $statement['rank'] );

		$itemContent = WikibaseRepo::getDefaultInstance()->getEntityContentFactory()->getFromId( $item->getId() );

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
		);

		try {
			$this->doApiRequestWithToken( $params );
			$this->fail( 'Invalid request should raise an exception' );
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
		$ranks = ClaimSerializer::getRanks();

		$params = array(
			'action' => 'wbsetstatementrank',
			'statement' => $claimGuid,
			'rank' => $ranks[0],
		);

		try {
			$this->doApiRequestWithToken( $params );
			$this->fail( 'Invalid claim guid did not throw an error' );
		} catch ( \UsageException $e ) {
			$this->assertEquals( 'invalid-guid', $e->getCodeString(), 'Invalid claim guid raised correct error' );
		}
	}

	public function invalidClaimProvider() {
		return array(
			array( 'xyz' ),
			array( 'x$y$z' )
		);
	}

}
