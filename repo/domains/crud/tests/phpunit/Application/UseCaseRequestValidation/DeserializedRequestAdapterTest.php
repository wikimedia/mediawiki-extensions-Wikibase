<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Crud\Application\UseCaseRequestValidation;

use Generator;
use LogicException;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\DataModel\Term\Term;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\AliasLanguageCodeRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\DescriptionLanguageCodeRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\DeserializedRequestAdapter;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\EditMetadataRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\ItemAliasesInLanguageEditRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\ItemDescriptionEditRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\ItemFieldsRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\ItemIdRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\ItemLabelEditRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\LabelLanguageCodeRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\PatchRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\PropertyAliasesInLanguageEditRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\PropertyDescriptionEditRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\PropertyFieldsRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\PropertyIdFilterRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\PropertyIdRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\PropertyLabelEditRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\SiteIdRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\SitelinkEditRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\StatementIdRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\StatementSerializationRequest;
use Wikibase\Repo\Domains\Crud\Domain\Model\UserProvidedEditMetadata;

/**
 * @covers \Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\DeserializedRequestAdapter
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

	/**
	 * @dataProvider languageCodeRequestProvider
	 */
	public function testGetLanguageCode( string $requestInterface ): void {
		$languageCode = 'en';
		$requestAdapter = new DeserializedRequestAdapter( [ $requestInterface => $languageCode ] );
		$this->assertSame( $languageCode, $requestAdapter->getLanguageCode() );
	}

	public static function languageCodeRequestProvider(): Generator {
		yield [ LabelLanguageCodeRequest::class ];
		yield [ DescriptionLanguageCodeRequest::class ];
		yield [ AliasLanguageCodeRequest::class ];
	}

	public function testGivenNoLanguageCode_getLanguageCodeThrows(): void {
		$this->expectException( LogicException::class );
		( new DeserializedRequestAdapter( [] ) )->getLanguageCode();
	}

	public function testGetSiteId(): void {
		$siteId = 'enwiki';
		$requestAdapter = new DeserializedRequestAdapter( [ SiteIdRequest::class => $siteId ] );
		$this->assertSame( $siteId, $requestAdapter->getSiteId() );
	}

	public function testGivenNoSiteId_getSiteIdThrows(): void {
		$this->expectException( LogicException::class );
		( new DeserializedRequestAdapter( [] ) )->getSiteId();
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

	public function testGetItemLabel(): void {
		$label = new Term( 'en', 'potato' );
		$requestAdapter = new DeserializedRequestAdapter( [ ItemLabelEditRequest::class => $label ] );
		$this->assertSame( $label, $requestAdapter->getItemLabel() );
	}

	public function testGivenNoLabel_getItemLabelThrows(): void {
		$this->expectException( LogicException::class );
		( new DeserializedRequestAdapter( [] ) )->getItemLabel();
	}

	public function testGetPropertyLabel(): void {
		$label = new Term( 'en', 'potato' );
		$requestAdapter = new DeserializedRequestAdapter( [ PropertyLabelEditRequest::class => $label ] );
		$this->assertSame( $label, $requestAdapter->getPropertyLabel() );
	}

	public function testGivenNoLabel_getPropertyLabelThrows(): void {
		$this->expectException( LogicException::class );
		( new DeserializedRequestAdapter( [] ) )->getPropertyLabel();
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

	public function testGetItemAliases(): void {
		$aliases = [ 'first alias', 'second alias' ];
		$requestAdapter = new DeserializedRequestAdapter( [ ItemAliasesInLanguageEditRequest::class => $aliases ] );
		$this->assertSame( $aliases, $requestAdapter->getItemAliasesInLanguage() );
	}

	public function testGivenNoAliasesForItem_getItemAliasesThrows(): void {
		$this->expectException( LogicException::class );
		( new DeserializedRequestAdapter( [] ) )->getItemAliasesInLanguage();
	}

	public function testGetPropertyAliasesInLanguage(): void {
		$aliases = [ 'first alias', 'second alias' ];
		$requestAdapter = new DeserializedRequestAdapter( [ PropertyAliasesInLanguageEditRequest::class => $aliases ] );
		$this->assertSame( $aliases, $requestAdapter->getPropertyAliasesInLanguage() );
	}

	public function testGivenNoAliasesForProperty_getPropertyAliasesInLanguageThrows(): void {
		$this->expectException( LogicException::class );
		( new DeserializedRequestAdapter( [] ) )->getPropertyAliasesInLanguage();
	}

	public function testGetSitelink(): void {
		$sitelink = new SiteLink( 'enwiki', 'Potato', [ new ItemId( 'Q1234' ) ] );
		$requestAdapter = new DeserializedRequestAdapter( [ SitelinkEditRequest::class => $sitelink ] );
		$this->assertSame( $sitelink, $requestAdapter->getSitelink() );
	}

	public function testGivenNoSitelink_getSitelinkThrows(): void {
		$this->expectException( LogicException::class );
		( new DeserializedRequestAdapter( [] ) )->getSitelink();
	}

}
