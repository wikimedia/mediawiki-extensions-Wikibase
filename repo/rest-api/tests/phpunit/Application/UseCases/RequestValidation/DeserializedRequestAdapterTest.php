<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\RequestValidation;

use LogicException;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\DataModel\Term\Term;
use Wikibase\Repo\RestApi\Application\UseCases\EditMetadataRequest;
use Wikibase\Repo\RestApi\Application\UseCases\ItemFieldsRequest;
use Wikibase\Repo\RestApi\Application\UseCases\ItemIdRequest;
use Wikibase\Repo\RestApi\Application\UseCases\ItemLabelEditRequest;
use Wikibase\Repo\RestApi\Application\UseCases\LanguageCodeRequest;
use Wikibase\Repo\RestApi\Application\UseCases\PatchRequest;
use Wikibase\Repo\RestApi\Application\UseCases\PropertyIdFilterRequest;
use Wikibase\Repo\RestApi\Application\UseCases\PropertyIdRequest;
use Wikibase\Repo\RestApi\Application\UseCases\RequestValidation\DeserializedRequestAdapter;
use Wikibase\Repo\RestApi\Application\UseCases\StatementIdRequest;
use Wikibase\Repo\RestApi\Application\UseCases\StatementSerializationRequest;
use Wikibase\Repo\RestApi\Domain\Model\UserProvidedEditMetadata;

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

	public function testGetItemFields(): void {
		$fields = [ 'labels' ];
		$requestAdapter = new DeserializedRequestAdapter( [ ItemFieldsRequest::class => $fields ] );
		$this->assertSame( $fields, $requestAdapter->getItemFields() );
	}

	public function testGivenNoItemFields_getItemFieldsThrows(): void {
		$this->expectException( LogicException::class );
		( new DeserializedRequestAdapter( [] ) )->getItemFields();
	}

	public function testGetStatementSerialization(): void {
		$statement = $this->createStub( Statement::class );
		$requestAdapter = new DeserializedRequestAdapter( [ StatementSerializationRequest::class => $statement ] );
		$this->assertSame( $statement, $requestAdapter->getStatement() );
	}

	public function testGivenNoStatementSerialization_getStatementSerializationThrows(): void {
		$this->expectException( LogicException::class );
		( new DeserializedRequestAdapter( [] ) )->getStatement();
	}

	public function testGetEditMetadata(): void {
		$editMetadata = $this->createStub( UserProvidedEditMetadata::class );
		$requestAdapter = new DeserializedRequestAdapter( [ EditMetadataRequest::class => $editMetadata ] );
		$this->assertSame( $editMetadata, $requestAdapter->getEditMetadata() );
	}

	public function testGivenNoEditMetadata_getEditMetadataThrows(): void {
		$this->expectException( LogicException::class );
		( new DeserializedRequestAdapter( [] ) )->getEditMetadata();
	}

	public function testGetPatch(): void {
		$patch = [ [ 'op' => 'test', 'path' => '/some-path', 'value' => 'abc' ] ];
		$requestAdapter = new DeserializedRequestAdapter( [ PatchRequest::class => $patch ] );
		$this->assertSame( $patch, $requestAdapter->getPatch() );
	}

	public function testGivenNoPatch_getPatchThrows(): void {
		$this->expectException( LogicException::class );
		( new DeserializedRequestAdapter( [] ) )->getPatch();
	}

	public function testGetLabel(): void {
		$label = new Term( 'en', 'potato' );
		$requestAdapter = new DeserializedRequestAdapter( [ ItemLabelEditRequest::class => $label ] );
		$this->assertSame( $label, $requestAdapter->getLabel() );
	}

	public function testGivenNoLabel_getLabelThrows(): void {
		$this->expectException( LogicException::class );
		( new DeserializedRequestAdapter( [] ) )->getLabel();
	}

}
