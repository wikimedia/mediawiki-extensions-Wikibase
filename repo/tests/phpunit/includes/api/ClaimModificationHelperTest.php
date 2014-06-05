<?php

namespace Wikibase\Test\Api;

use ApiMain;
use DataValues\StringValue;
use UsageException;
use ValueFormatters\ValueFormatter;
use Wikibase\Api\ApiErrorReporter;
use Wikibase\Api\ClaimModificationHelper;
use Wikibase\Api\CreateClaim;
use Wikibase\DataModel\Claim\Claims;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Snak\PropertyValueSnak;
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
			'\Wikibase\EntityId',
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
		$entity = Item::newFromArray( array( 'entity' => 'q42' ) );
		$snak = new PropertyValueSnak( 2754236, new StringValue( 'test' ) );
		$claim = $entity->newClaim( $snak );
		$claim->setGuid( 'q42$D8404CDA-25E4-4334-AF13-A3290BCD9C0F' );
		$claims = new Claims();
		$claims->addClaim( $claim );
		$entity->setClaims( $claims );
		$claimGuid = $claim->getGuid();

		$this->assertEquals( $claim, $claimModificationHelper->getClaimFromEntity( $claimGuid, $entity ) );
		$this->setExpectedException( '\UsageException' );
		$claimModificationHelper->getClaimFromEntity( 'q42$D8404CDA-25E4-4334-AF13-A3290BCD9C0N', $entity );
	}

	/**
	 * @return ValueFormatter
	 */
	private function getMockFormatter() {
		$mock = $this->getMock( 'ValueFormatters\ValueFormatter' );
		$mock->expects( $this->any() )
			->method( 'format' )
			->will( $this->returnCallback(
				function ( $param ) {
					if ( is_object( $param ) ) {
						$param = get_class( $param );
					}

					return strval( $param );
				}
			) );

		return $mock;
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
