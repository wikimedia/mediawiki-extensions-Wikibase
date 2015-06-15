<?php

namespace Wikibase\Test\Api;

use ApiMain;
use DataValues\StringValue;
use UsageException;
use Wikibase\Api\ApiErrorReporter;
use Wikibase\Api\CreateClaim;
use Wikibase\Api\StatementModificationHelper;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Lib\Localizer\DispatchingExceptionLocalizer;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers Wikibase\Api\StatementModificationHelper
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseAPI
 * @group Database
 *
 * @licence GNU GPL v2+
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class StatementModificationHelperTest extends \MediaWikiTestCase {

	public function testValidGetEntityIdFromString() {
		$validEntityIdString = 'q55';

		$helper = $this->getNewInstance();
		$this->assertInstanceOf(
			'Wikibase\DataModel\Entity\EntityId',
			$helper->getEntityIdFromString( $validEntityIdString )
		);
	}

	/**
	 * @expectedException UsageException
	 */
	public function testInvalidGetEntityIdFromString() {
		$invalidEntityIdString = 'no!';
		$helper = $this->getNewInstance();
		$helper->getEntityIdFromString( $invalidEntityIdString );
	}

	public function testCreateSummary() {
		$apiMain = new ApiMain();
		$helper = $this->getNewInstance();
		$customSummary = 'I did it!';

		$summary = $helper->createSummary(
			array( 'summary' => $customSummary ),
			new CreateClaim( $apiMain, 'wbcreateclaim' )
		);
		$this->assertEquals( 'wbcreateclaim', $summary->getModuleName() );
		$this->assertEquals( $customSummary, $summary->getUserSummary() );

		$summary = $helper->createSummary(
			array(),
			new CreateClaim( $apiMain, 'wbcreateclaim' )
		);
		$this->assertEquals( 'wbcreateclaim', $summary->getModuleName() );
		$this->assertNull( $summary->getUserSummary() );
	}

	public function testGetStatementFromEntity() {
		$helper = $this->getNewInstance();

		$item = new Item( new ItemId( 'Q42' ) );

		$snak = new PropertyValueSnak( 2754236, new StringValue( 'test' ) );
		$statement = new Statement( $snak );
		$statement->setGuid( 'q42$D8404CDA-25E4-4334-AF13-A3290BCD9C0F' );
		$item->getStatements()->addStatement( $statement );
		$guid = $statement->getGuid();

		$this->assertEquals( $statement, $helper->getStatementFromEntity( $guid, $item ) );
		$this->setExpectedException( '\UsageException' );
		$helper->getStatementFromEntity( 'q42$D8404CDA-25E4-4334-AF13-A3290BCD9C0N', $item );
	}

	private function getNewInstance() {
		$api = new ApiMain();

		$errorReporter = new ApiErrorReporter(
			$api,
			new DispatchingExceptionLocalizer( array() ),
			$api->getLanguage()
		);

		$helper = new StatementModificationHelper(
			WikibaseRepo::getDefaultInstance()->getSnakConstructionService(),
			WikibaseRepo::getDefaultInstance()->getEntityIdParser(),
			WikibaseRepo::getDefaultInstance()->getClaimGuidValidator(),
			$errorReporter
		);

		return $helper;
	}

}
