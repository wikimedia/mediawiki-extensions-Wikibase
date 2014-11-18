<?php

namespace Wikibase\Test\Api;

use ApiMain;
use DataValues\StringValue;
use UsageException;
use ValueFormatters\ValueFormatter;
use Wikibase\Api\ApiErrorReporter;
use Wikibase\Api\ClaimModificationHelper;
use Wikibase\Api\CreateClaim;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Lib\Localizer\DispatchingExceptionLocalizer;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers Wikibase\Api\ClaimModificationHelper
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseAPI
 *
 * @licence GNU GPL v2+
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class ClaimModificationHelperTest extends \PHPUnit_Framework_TestCase {

	public function testValidGetEntityIdFromString() {
		$validEntityIdString = 'q55';

		$claimModificationHelper = $this->getNewInstance();
		$this->assertInstanceOf(
			'Wikibase\DataModel\Entity\EntityId',
			$claimModificationHelper->getEntityIdFromString( $validEntityIdString )
		);
	}

	/**
	 * @expectedException UsageException
	 */
	public function testInvalidGetEntityIdFromString() {
		$invalidEntityIdString = 'no!';
		$claimModificationHelper = $this->getNewInstance();
		$claimModificationHelper->getEntityIdFromString( $invalidEntityIdString );
	}

	public function testCreateSummary() {
		$apiMain = new ApiMain();
		$claimModificationHelper = $this->getNewInstance();
		$customSummary = 'I did it!';

		$summary = $claimModificationHelper->createSummary(
			array( 'summary' => $customSummary ),
			new CreateClaim( $apiMain, 'wbcreateclaim' )
		);
		$this->assertEquals( 'wbcreateclaim', $summary->getModuleName() );
		$this->assertEquals( $customSummary, $summary->getUserSummary() );

		$summary = $claimModificationHelper->createSummary(
			array(),
			new CreateClaim( $apiMain, 'wbcreateclaim' )
		);
		$this->assertEquals( 'wbcreateclaim', $summary->getModuleName() );
		$this->assertNull( $summary->getUserSummary() );
	}

	public function testGetClaimFromEntity() {
		$claimModificationHelper = $this->getNewInstance();

		$item = Item::newEmpty();
		$item->setId( 42 );

		$snak = new PropertyValueSnak( 2754236, new StringValue( 'test' ) );
		$claim = $item->newClaim( $snak );
		$claim->setGuid( 'q42$D8404CDA-25E4-4334-AF13-A3290BCD9C0F' );
		$item->getStatements()->addStatement( new Statement( $claim ) );
		$claimGuid = $claim->getGuid();

		$this->assertEquals( $claim, $claimModificationHelper->getClaimFromEntity( $claimGuid, $item ) );
		$this->setExpectedException( '\UsageException' );
		$claimModificationHelper->getClaimFromEntity( 'q42$D8404CDA-25E4-4334-AF13-A3290BCD9C0N', $item );
	}

	private function getNewInstance() {
		$api = new ApiMain();

		$errorReporter = new ApiErrorReporter(
			$api,
			new DispatchingExceptionLocalizer( array() ),
			$api->getLanguage()
		);

		$claimModificationHelper = new ClaimModificationHelper(
			WikibaseRepo::getDefaultInstance()->getSnakConstructionService(),
			WikibaseRepo::getDefaultInstance()->getEntityIdParser(),
			WikibaseRepo::getDefaultInstance()->getClaimGuidValidator(),
			$errorReporter
		);

		return $claimModificationHelper;
	}

}
