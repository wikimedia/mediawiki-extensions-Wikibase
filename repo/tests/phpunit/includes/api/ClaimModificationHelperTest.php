<?php

namespace Wikibase\Test\Api;

use Wikibase\Api\CreateClaim;
use Wikibase\Claim;
use Wikibase\EntityId;
use ApiMain;
use Wikibase\Api\ClaimModificationHelper;
use Wikibase\Api\SnakValidationHelper;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\ItemContent;
use Wikibase\Claims;
use Wikibase\PropertyContent;
use Wikibase\Lib\ClaimGuidValidator;
use Wikibase\Validators\ValidatorErrorLocalizer;

/**
 * @covers Wikibase\Api\ClaimModificationHelper
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA
 *
 * @file
 * @since 0.4
 *
 * @ingroup Wikibase
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseAPI
 *
 * @licence GNU GPL v2+
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */

class ClaimModificationHelperTest extends \PHPUnit_Framework_TestCase {

	public function testAddClaimToApiResult() {
		$apiMain = new ApiMain();
		$snak = new \Wikibase\PropertyValueSnak( 7201010, new \DataValues\StringValue( 'o_O' ) );
		$item = ItemContent::newFromArray( array( 'entity' => 'q42' ) )->getEntity();
		$claim = new Claim( $snak );
		$claim->setGuid( 'q42$D8404CDA-25E4-4334-AF13-A3290BCD9C0F' );
		$item->addClaim( $claim );

		$claimModificationHelper = $this->getNewInstance( $apiMain );
		$claimModificationHelper->addClaimToApiResult( $claim );

		$resultData = $apiMain->getResultData();
		$this->assertArrayHasKey( 'claim', $resultData );
		$this->assertEquals( $claim->getGuid(), $resultData['claim']['id'] );
	}

	public function testGetEntityTitle() {
		$item = ItemContent::newFromArray( array( 'entity' => 'q42' ) )->getEntity();
		$entityId = $item->getId();

		$claimModificationHelper = $this->getNewInstance();
		$this->assertInstanceOf( '\Title', $claimModificationHelper->getEntityTitle( $entityId ) );
	}

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

	public function testGetPossibleErrors() {
		$claimModificationHelper = $this->getNewInstance();
		$this->assertInternalType( 'array', $claimModificationHelper->getPossibleErrors() );
	}

	public function testGetClaimFromEntity() {
		$claimModificationHelper = $this->getNewInstance();
		$entity = ItemContent::newFromArray( array( 'entity' => 'q42' ) )->getEntity();
		$snak = new \Wikibase\PropertyValueSnak( 2754236, new \DataValues\StringValue( 'test' ) );
		$claim = new Claim( $snak );
		$claim->setGuid( 'q42$D8404CDA-25E4-4334-AF13-A3290BCD9C0F' );
		$claims = new Claims();
		$claims->addClaim( $claim );
		$entity->setClaims( $claims );
		$claimGuid = $claim->getGuid();

		$this->assertEquals( $claim, $claimModificationHelper->getClaimFromEntity( $claimGuid, $entity ) );
		$this->setExpectedException( '\UsageException' );
		$claimModificationHelper->getClaimFromEntity( 'q42$D8404CDA-25E4-4334-AF13-A3290BCD9C0N', $entity );
	}

	private function getNewInstance( $apiMain = null ) {
		if ($apiMain === null) {
			$apiMain = new ApiMain();
		}

		$snakValidation = new SnakValidationHelper(
			$apiMain,
			WikibaseRepo::getDefaultInstance()->getPropertyDataTypeLookup(),
			WikibaseRepo::getDefaultInstance()->getDataTypeFactory(),
			new ValidatorErrorLocalizer()
		);

		$claimModificationHelper = new ClaimModificationHelper(
			$apiMain,
			WikibaseRepo::getDefaultInstance()->getEntityContentFactory(),
			WikibaseRepo::getDefaultInstance()->getSnakConstructionService(),
			WikibaseRepo::getDefaultInstance()->getEntityIdParser(),
			WikibaseRepo::getDefaultInstance()->getClaimGuidValidator(),
			$snakValidation
		);

		return $claimModificationHelper;
	}

}
