<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\RequestValidation;

use LogicException;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\Repo\RestApi\Application\UseCases\RequestValidation\DeserializedRequestAdapter;
use Wikibase\Repo\RestApi\Application\UseCases\RequestValidation\ItemIdRequestValidatingDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\RequestValidation\PropertyIdRequestValidatingDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\RequestValidation\StatementIdRequestValidatingDeserializer;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\RequestValidation\DeserializedRequestAdapter
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class DeserializedRequestAdapterTest extends TestCase {

	public function testGetItemId(): void {
		$itemId = new ItemId( 'Q123' );
		$requestAdapter = new DeserializedRequestAdapter( [ ItemIdRequestValidatingDeserializer::DESERIALIZED_VALUE => $itemId ] );
		$this->assertSame(
			$itemId,
			$requestAdapter->getItemId()
		);
	}

	public function testGivenNoItemId_getItemIdThrows(): void {
		$this->expectException( LogicException::class );
		( new DeserializedRequestAdapter( [] ) )->getItemId();
	}

	public function testGetPropertyId(): void {
		$propertyId = new NumericPropertyId( 'P123' );
		$requestAdapter = new DeserializedRequestAdapter( [ PropertyIdRequestValidatingDeserializer::DESERIALIZED_VALUE => $propertyId ] );
		$this->assertSame(
			$propertyId,
			$requestAdapter->getPropertyId()
		);
	}

	public function testGivenNoPropertyId_getPropertyIdThrows(): void {
		$this->expectException( LogicException::class );
		( new DeserializedRequestAdapter( [] ) )->getPropertyId();
	}

	public function testGetStatementId(): void {
		$statementId = new StatementGuid( new ItemId( 'Q123' ), 'AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' );
		$requestAdapter = new DeserializedRequestAdapter(
			[ StatementIdRequestValidatingDeserializer::DESERIALIZED_VALUE => $statementId ]
		);
		$this->assertSame(
			$statementId,
			$requestAdapter->getStatementId()
		);
	}

	public function testGivenNoStatementId_getStatementIdThrows(): void {
		$this->expectException( LogicException::class );
		( new DeserializedRequestAdapter( [] ) )->getStatementId();
	}

}
