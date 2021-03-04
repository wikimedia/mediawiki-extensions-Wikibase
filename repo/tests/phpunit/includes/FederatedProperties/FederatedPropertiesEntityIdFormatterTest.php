<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Test\FederatedProperties;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Repo\FederatedProperties\ApiRequestExecutionException;
use Wikibase\Repo\FederatedProperties\FederatedPropertiesEntityIdFormatter;

/**
 * @covers \Wikibase\Repo\FederatedProperties\FederatedPropertiesEntityIdFormatter
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 */
class FederatedPropertiesEntityIdFormatterTest extends TestCase {

	private function getThrowingInnerService() {
		$mock = $this->createMock( FederatedPropertiesEntityIdFormatter::class );
		$mock->expects( $this->once() )
		->method( 'formatEntityId' )
		->willThrowException( new ApiRequestExecutionException() );
		return $mock;
	}

	private function getReturningInnerService() {
		$mock = $this->createMock( FederatedPropertiesEntityIdFormatter::class );
		$mock->expects( $this->once() )
		->method( 'formatEntityId' )
		->willReturn( "RETURNED" );
		return $mock;
	}

	public function testInnerValueWhenNoException() {
		$sot = new FederatedPropertiesEntityIdFormatter( $this->getReturningInnerService() );
		$result = $sot->formatEntityId( new PropertyId( 'P123' ) );
		$this->assertEquals( 'RETURNED', $result );
	}

	public function testSerializedIdWhenExceptionThrown() {
		$sot = new FederatedPropertiesEntityIdFormatter( $this->getThrowingInnerService() );
		$result = $sot->formatEntityId( new PropertyId( 'P123' ) );
		$this->assertEquals( 'P123', $result );
	}

}
