<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Crud\Application\UseCases\PatchItem;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\Item as ItemWriteModel;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lib\Store\HashSiteLinkStore;
use Wikibase\Repo\Domains\Crud\Application\Serialization\AliasesDeserializer;
use Wikibase\Repo\Domains\Crud\Application\Serialization\AliasesInLanguageDeserializer;
use Wikibase\Repo\Domains\Crud\Application\Serialization\AliasesSerializer;
use Wikibase\Repo\Domains\Crud\Application\Serialization\DescriptionsDeserializer;
use Wikibase\Repo\Domains\Crud\Application\Serialization\DescriptionsSerializer;
use Wikibase\Repo\Domains\Crud\Application\Serialization\ItemSerializer;
use Wikibase\Repo\Domains\Crud\Application\Serialization\LabelsDeserializer;
use Wikibase\Repo\Domains\Crud\Application\Serialization\LabelsSerializer;
use Wikibase\Repo\Domains\Crud\Application\Serialization\PropertyValuePairDeserializer;
use Wikibase\Repo\Domains\Crud\Application\Serialization\ReferenceDeserializer;
use Wikibase\Repo\Domains\Crud\Application\Serialization\SitelinkDeserializer;
use Wikibase\Repo\Domains\Crud\Application\Serialization\SitelinkSerializer;
use Wikibase\Repo\Domains\Crud\Application\Serialization\SitelinksSerializer;
use Wikibase\Repo\Domains\Crud\Application\Serialization\StatementDeserializer;
use Wikibase\Repo\Domains\Crud\Application\Serialization\StatementListSerializer;
use Wikibase\Repo\Domains\Crud\Application\UseCases\AssertItemExists;
use Wikibase\Repo\Domains\Crud\Application\UseCases\AssertUserIsAuthorized;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchItem\PatchedItemValidator;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchItem\PatchItem;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchItem\PatchItemRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchItem\PatchItemValidator;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchJson;
use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;
use Wikibase\Repo\Domains\Crud\Application\Validation\AliasesValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\DescriptionLanguageCodeValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\DescriptionsSyntaxValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\ItemDescriptionsContentsValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\ItemDescriptionValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\ItemLabelsContentsValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\ItemLabelValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\LabelLanguageCodeValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\LabelsSyntaxValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\SiteIdValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\SitelinksValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\StatementsValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\StatementValidator;
use Wikibase\Repo\Domains\Crud\Domain\Model\EditMetadata;
use Wikibase\Repo\Domains\Crud\Domain\Model\PatchItemEditSummary;
use Wikibase\Repo\Domains\Crud\Domain\Model\User;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Aliases;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Description;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Descriptions;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Item;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Label;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Labels;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Sitelinks;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\StatementList;
use Wikibase\Repo\Domains\Crud\Domain\Services\ItemRetriever;
use Wikibase\Repo\Domains\Crud\Domain\Services\ItemUpdater;
use Wikibase\Repo\Domains\Crud\Domain\Services\ItemWriteModelRetriever;
use Wikibase\Repo\Domains\Crud\Infrastructure\JsonDiffJsonPatcher;
use Wikibase\Repo\Domains\Crud\Infrastructure\SiteLinkLookupSitelinkValidator;
use Wikibase\Repo\Domains\Crud\Infrastructure\TermValidatorFactoryAliasesInLanguageValidator;
use Wikibase\Repo\Domains\Crud\Infrastructure\ValueValidatorLanguageCodeValidator;
use Wikibase\Repo\Tests\Domains\Crud\Application\UseCaseRequestValidation\TestValidatingRequestDeserializer;
use Wikibase\Repo\Tests\Domains\Crud\Infrastructure\DataAccess\DummyItemRevisionMetaDataRetriever;
use Wikibase\Repo\Tests\Domains\Crud\Infrastructure\DataAccess\DummyValidSiteIdsRetriever;
use Wikibase\Repo\Tests\Domains\Crud\Infrastructure\DataAccess\InMemoryItemRepository;
use Wikibase\Repo\Tests\Domains\Crud\Infrastructure\DataAccess\SameTitleSitelinkTargetResolver;
use Wikibase\Repo\Validators\MembershipValidator;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\Domains\Crud\Application\UseCases\PatchItem\PatchItem
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PatchItemTest extends TestCase {

	private const ALLOWED_BADGES = [ 'Q999' ];

	private InMemoryItemRepository $itemRepository;
	private AssertUserIsAuthorized $assertUserIsAuthorized;
	private PatchItemValidator $validator;
	private PatchJson $patchJson;
	private ItemRetriever $itemRetriever;
	private ItemUpdater $itemUpdater;
	private AssertItemExists $assertItemExists;
	private PatchedItemValidator $patchedItemValidator;
	private ItemWriteModelRetriever $itemWriteModelRetriever;

	protected function setUp(): void {
		parent::setUp();

		$this->itemRepository = new InMemoryItemRepository();

		$this->validator = new TestValidatingRequestDeserializer();
		$this->assertUserIsAuthorized = $this->createStub( AssertUserIsAuthorized::class );
		$this->patchJson = new PatchJson( new JsonDiffJsonPatcher() );
		$this->itemRetriever = $this->itemRepository;
		$this->itemUpdater = $this->itemRepository;
		$this->patchedItemValidator = new PatchedItemValidator(
			new LabelsSyntaxValidator( new LabelsDeserializer(), $this->createStub( LabelLanguageCodeValidator::class ) ),
			new ItemLabelsContentsValidator( $this->createStub( ItemLabelValidator::class ) ),
			new DescriptionsSyntaxValidator( new DescriptionsDeserializer(), $this->createStub( DescriptionLanguageCodeValidator::class ) ),
			new ItemDescriptionsContentsValidator( $this->createStub( ItemDescriptionValidator::class ) ),
			new AliasesValidator(
				new TermValidatorFactoryAliasesInLanguageValidator( WikibaseRepo::getTermValidatorFactory() ),
				new ValueValidatorLanguageCodeValidator( new MembershipValidator( [ 'ar', 'de', 'en', 'en-gb' ] ) ),
				new AliasesDeserializer( new AliasesInLanguageDeserializer() )
			),
			$this->newSitelinksValidator(),
			$this->newStatementsValidator(),
		);
		$this->itemWriteModelRetriever = $this->itemRepository;
		$this->assertItemExists = $this->createStub( AssertItemExists::class );
	}

	public function testHappyPath(): void {
		$itemId = new ItemId( 'Q123' );
		$editTags = TestValidatingRequestDeserializer::ALLOWED_TAGS;
		$isBot = false;
		$comment = 'statement replaced by ' . __METHOD__;
		$originalItem = new ItemWriteModel(
			$itemId,
			new Fingerprint( new TermList( [ new Term( 'en', 'potato' ), new Term( 'de', 'Kartoffel' ) ] ) )
		);

		$this->itemRepository->addItem( $originalItem );

		$response = $this->newUseCase()->execute(
			new PatchItemRequest(
				"$itemId",
				[
					[ 'op' => 'add', 'path' => '/descriptions/en', 'value' => 'staple food' ],
					[ 'op' => 'replace', 'path' => '/labels/en', 'value' => 'Solanum tuberosum' ],
					[ 'op' => 'remove', 'path' => '/labels/de' ],
				],
				$editTags,
				$isBot,
				$comment,
				null
			)
		);

		$this->assertSame( $this->itemRepository->getLatestRevisionId( $itemId ), $response->getRevisionId() );
		$this->assertSame(
			$this->itemRepository->getLatestRevisionTimestamp( $itemId ),
			$response->getLastModified()
		);
		$this->assertEquals(
			new Item(
				$itemId,
				new Labels( new Label( 'en', 'Solanum tuberosum' ) ),
				new Descriptions( new Description( 'en', 'staple food' ) ),
				new Aliases(),
				new Sitelinks(),
				new StatementList()
			),
			$response->getItem()
		);
		$this->assertEquals(
			new EditMetadata(
				$editTags,
				$isBot,
				PatchItemEditSummary::newSummary(
					$comment,
					$originalItem,
					$this->itemRepository->getItemWriteModel( $itemId )
				),
			),
			$this->itemRepository->getLatestRevisionEditMetadata( $itemId )
		);
	}

	public function testGivenInvalidRequest_throw(): void {
		try {
			$this->newUseCase()->execute( new PatchItemRequest( 'X321', [], [], false, null, null ) );
			$this->fail( 'expected exception was not thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::INVALID_PATH_PARAMETER, $e->getErrorCode() );
			$this->assertSame( "Invalid path parameter: 'item_id'", $e->getErrorMessage() );
			$this->assertSame( [ UseCaseError::CONTEXT_PARAMETER => 'item_id' ], $e->getErrorContext() );
		}
	}

	public function testGivenErrorWhilePatch_throws(): void {
		$itemId = new ItemId( 'Q123' );

		$this->itemRepository->addItem( new ItemWriteModel( $itemId, new Fingerprint(), null, null ) );

		$expectedException = $this->createStub( UseCaseError::class );

		$this->patchJson = $this->createStub( PatchJson::class );
		$this->patchJson->method( 'execute' )->willThrowException( $expectedException );

		try {
			$this->newUseCase()->execute( new PatchItemRequest( "$itemId", [], [], false, null, null ) );
			$this->fail( 'expected exception was not thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	public function testGivenItemInvalidAfterPatching_throws(): void {
		$itemId = new ItemId( 'Q123' );

		$this->itemRepository->addItem( new ItemWriteModel( $itemId, new Fingerprint(), null, null ) );

		$expectedException = $this->createStub( UseCaseError::class );

		$this->patchedItemValidator = $this->createStub( PatchedItemValidator::class );
		$this->patchedItemValidator->method( 'validateAndDeserialize' )->willThrowException( $expectedException );

		try {
			$this->newUseCase()->execute( new PatchItemRequest( "$itemId", [], [], false, null, null ) );
			$this->fail( 'expected exception was not thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	public function testGivenItemNotFoundOrRedirect_throws(): void {
		$expectedException = $this->createStub( UseCaseError::class );

		$this->assertItemExists = $this->createStub( AssertItemExists::class );
		$this->assertItemExists->method( 'execute' )->willThrowException( $expectedException );

		try {
			$this->newUseCase()->execute( new PatchItemRequest( 'Q999999', [], [], false, null, null ) );
			$this->fail( 'expected exception was not thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	public function testGivenUnauthorizedRequest_throws(): void {
		$user = 'bad-user';
		$itemId = new ItemId( 'Q123' );
		$request = new PatchItemRequest( (string)$itemId, [], [], false, null, $user );
		$expectedException = $this->createStub( UseCaseError::class );

		$this->assertUserIsAuthorized = $this->createMock( AssertUserIsAuthorized::class );
		$this->assertUserIsAuthorized->expects( $this->once() )
			->method( 'checkEditPermissions' )
			->with( $itemId, User::withUsername( $user ) )
			->willThrowException( $expectedException );

		try {
			$this->newUseCase()->execute( $request );
			$this->fail( 'expected exception was not thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	private function newUseCase(): PatchItem {
		return new PatchItem(
			$this->validator,
			$this->assertItemExists,
			$this->assertUserIsAuthorized,
			$this->itemRetriever,
			new ItemSerializer(
				new LabelsSerializer(),
				new DescriptionsSerializer(),
				new AliasesSerializer(),
				$this->createStub( StatementListSerializer::class ),
				new SitelinksSerializer( new SitelinkSerializer() )
			),
			$this->patchJson,
			$this->patchedItemValidator,
			$this->itemWriteModelRetriever,
			$this->itemUpdater,
		);
	}

	private function newStatementsValidator(): StatementsValidator {
		$propValPairDeserializer = $this->createStub( PropertyValuePairDeserializer::class );
		$propValPairDeserializer->method( 'deserialize' )->willReturnCallback(
			fn( array $p ) => new PropertySomeValueSnak( new ItemId( $p[ 'item' ][ 'id' ] ) )
		);

		return new StatementsValidator(
			new StatementValidator(
				new StatementDeserializer( $propValPairDeserializer, $this->createStub( ReferenceDeserializer::class ) )
			)
		);
	}

	private function newSitelinksValidator(): SitelinksValidator {
		return new SitelinksValidator(
			new SiteIdValidator( new DummyValidSiteIdsRetriever() ),
			new SiteLinkLookupSitelinkValidator(
				new SitelinkDeserializer(
					'/\?/',
					self::ALLOWED_BADGES,
					new SameTitleSitelinkTargetResolver(),
					new DummyItemRevisionMetaDataRetriever()
				),
				new HashSiteLinkStore()
			)
		);
	}

}
