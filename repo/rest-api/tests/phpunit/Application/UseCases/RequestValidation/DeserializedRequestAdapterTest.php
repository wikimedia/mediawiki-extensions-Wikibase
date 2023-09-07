<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\RequestValidation;

use LogicException;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\Repo\RestApi\Application\UseCases\ItemIdRequest;
use Wikibase\Repo\RestApi\Application\UseCases\LanguageCodeRequest;
use Wikibase\Repo\RestApi\Application\UseCases\PropertyIdFilterRequest;
use Wikibase\Repo\RestApi\Application\UseCases\PropertyIdRequest;
use Wikibase\Repo\RestApi\Application\UseCases\RequestValidation\DeserializedRequestAdapter;
use Wikibase\Repo\RestApi\Application\UseCases\StatementIdRequest;

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
		$requestAdapter = new DeserializedRequestAdapter( [ ItemIdRequest::class => $itemId ] );
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
		$requestAdapter = new DeserializedRequestAdapter( [ PropertyIdRequest::class => $propertyId ] );
		$this->assertSame(
			$propertyId,
			$requestAdapter->getPropertyId()
		);
	}

	public function testGivenNoPropertyId_getPropertyIdThrows(): void {
		$this->expectException( LogicException::class );
		( new DeserializedRequestAdapter( [] ) )->getPropertyId();
	}

	public function testGetPropertyIdFilter(): void {
		$propertyId = new NumericPropertyId( 'P123' );
		$requestAdapter = new DeserializedRequestAdapter( [ PropertyIdFilterRequest::class => $propertyId ] );
		$this->assertSame(
			$propertyId,
			$requestAdapter->getPropertyIdFilter()
		);
	}

	public function testGivenNoPropertyIdFilter_getPropertyIdFilterThrows(): void {
		$this->expectException( LogicException::class );
		( new DeserializedRequestAdapter( [] ) )->getPropertyIdFilter();
	}

	public function testGetStatementId(): void {
		$statementId = new StatementGuid( new ItemId( 'Q123' ), 'AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' );
		$requestAdapter = new DeserializedRequestAdapter( [ StatementIdRequest::class => $statementId ] );
		$this->assertSame(
			$statementId,
			$requestAdapter->getStatementId()
		);
	}

	public function testGivenNoStatementId_getStatementIdThrows(): void {
		$this->expectException( LogicException::class );
		( new DeserializedRequestAdapter( [] ) )->getStatementId();
	}

	public function testGetLanguageCode(): void {
		$languageCode = 'en';
		$requestAdapter = new DeserializedRequestAdapter( [ LanguageCodeRequest::class => $languageCode ] );
		$this->assertSame( $languageCode, $requestAdapter->getLanguageCode() );
	}

	public function testGivenNoLanguageCode_getLanguageCodeThrows(): void {
		$this->expectException( LogicException::class );
		( new DeserializedRequestAdapter( [] ) )->getLanguageCode();
	}

}
