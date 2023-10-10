<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCaseRequestValidation;

use LogicException;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\DataModel\Term\Term;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\DeserializedRequestAdapter;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\EditMetadataRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\ItemDescriptionEditRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\ItemFieldsRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\ItemIdRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\ItemLabelEditRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\LanguageCodeRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\PatchRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\PropertyDescriptionEditRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\PropertyFieldsRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\PropertyIdFilterRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\PropertyIdRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\StatementIdRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\StatementSerializationRequest;
use Wikibase\Repo\RestApi\Domain\Model\UserProvidedEditMetadata;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\DeserializedRequestAdapter
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

	public function testGetPropertyFields(): void {
		$fields = [ 'labels' ];
		$requestAdapter = new DeserializedRequestAdapter( [ PropertyFieldsRequest::class => $fields ] );
		$this->assertSame( $fields, $requestAdapter->getPropertyFields() );
	}

	public function testGivenNoPropertyFields_getPropertyFieldsThrows(): void {
		$this->expectException( LogicException::class );
		( new DeserializedRequestAdapter( [] ) )->getPropertyFields();
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

	public function testGetItemDescription(): void {
		$description = new Term( 'en', 'root vegetable' );
		$requestAdapter = new DeserializedRequestAdapter( [ ItemDescriptionEditRequest::class => $description ] );
		$this->assertSame( $description, $requestAdapter->getItemDescription() );
	}

	public function testGivenNoDescriptionForItem_getItemDescriptionThrows(): void {
		$this->expectException( LogicException::class );
		( new DeserializedRequestAdapter( [] ) )->getItemDescription();
	}

	public function testGetPropertyDescription(): void {
		$description = new Term( 'en', 'that class of which this subject is a particular example and member' );
		$requestAdapter = new DeserializedRequestAdapter( [ PropertyDescriptionEditRequest::class => $description ] );
		$this->assertSame( $description, $requestAdapter->getPropertyDescription() );
	}

	public function testGivenNoDescriptionForProperty_getPropertyDescriptionThrows(): void {
		$this->expectException( LogicException::class );
		( new DeserializedRequestAdapter( [] ) )->getPropertyDescription();
	}

}
