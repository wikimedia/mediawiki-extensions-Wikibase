<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\AddPropertyAliasesInLanguage;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\Repo\RestApi\Application\UseCases\AddPropertyAliasesInLanguage\AddPropertyAliasesInLanguage;
use Wikibase\Repo\RestApi\Application\UseCases\AddPropertyAliasesInLanguage\AddPropertyAliasesInLanguageRequest;
use Wikibase\Repo\RestApi\Domain\Model\AliasesInLanguageEditSummary;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Model\EditSummary;
use Wikibase\Repo\RestApi\Domain\ReadModel\Aliases;
use Wikibase\Repo\RestApi\Domain\ReadModel\AliasesInLanguage;
use Wikibase\Repo\RestApi\Domain\ReadModel\Descriptions;
use Wikibase\Repo\RestApi\Domain\ReadModel\Labels;
use Wikibase\Repo\RestApi\Domain\ReadModel\Property as ReadModelProperty;
use Wikibase\Repo\RestApi\Domain\ReadModel\PropertyRevision;
use Wikibase\Repo\RestApi\Domain\ReadModel\StatementList;
use Wikibase\Repo\RestApi\Domain\Services\PropertyRetriever;
use Wikibase\Repo\RestApi\Domain\Services\PropertyUpdater;
use Wikibase\Repo\Tests\RestApi\Application\UseCaseRequestValidation\TestValidatingRequestDeserializer;
use Wikibase\Repo\Tests\RestApi\Domain\Model\EditMetadataHelper;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\AddPropertyAliasesInLanguage\AddPropertyAliasesInLanguage
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class AddPropertyAliasesInLanguageTest extends TestCase {

	use EditMetadataHelper;

	private PropertyRetriever $propertyRetriever;
	private PropertyUpdater $propertyUpdater;

	protected function setUp(): void {
		parent::setUp();

		$this->propertyRetriever = $this->createStub( PropertyRetriever::class );
		$this->propertyUpdater = $this->createStub( PropertyUpdater::class );
	}

	public function testCreateAliases(): void {
		$languageCode = 'en';
		$property = new Property( new NumericPropertyId( 'P123' ), null, 'string' );
		$aliasesToCreate = [ 'alias 1', 'alias 2' ];
		$postModificationRevisionId = 322;
		$modificationTimestamp = '20221111070707';
		$editTags = [ TestValidatingRequestDeserializer::ALLOWED_TAGS[0] ];
		$isBot = false;
		$comment = 'potato';

		$request = new AddPropertyAliasesInLanguageRequest(
			$property->getId()->getSerialization(),
			$languageCode,
			$aliasesToCreate,
			$editTags,
			$isBot,
			$comment,
			null
		);

		$this->propertyRetriever = $this->createStub( PropertyRetriever::class );
		$this->propertyRetriever->method( 'getProperty' )->willReturn( $property );

		$updatedProperty = new ReadModelProperty(
			new Labels(),
			new Descriptions(),
			new Aliases( new AliasesInLanguage( $languageCode, $aliasesToCreate ) ),
			new StatementList()
		);
		$this->propertyUpdater = $this->createMock( PropertyUpdater::class );
		$this->propertyUpdater->method( 'update' )
			->with(
				$this->callback(
					fn( Property $property ) => $property->getAliasGroups()
						->getByLanguage( $languageCode )
						->equals( new AliasGroup( $languageCode, $aliasesToCreate ) )
				),
				$this->expectEquivalentMetadata( $editTags, $isBot, $comment, EditSummary::ADD_ACTION )
			)
			->willReturn( new PropertyRevision( $updatedProperty, $modificationTimestamp, $postModificationRevisionId ) );

		$response = $this->newUseCase()->execute( $request );

		$this->assertEquals( new AliasesInLanguage( $languageCode, $aliasesToCreate ), $response->getAliases() );
		$this->assertFalse( $response->wasAddedToExistingAliasGroup() );
		$this->assertSame( $postModificationRevisionId, $response->getRevisionId() );
		$this->assertSame( $modificationTimestamp, $response->getLastModified() );
	}

	public function testAddToExistingAliases(): void {
		$languageCode = 'en';
		$existingAliases = [ 'alias 1', 'alias 2' ];
		$property = new Property(
			new NumericPropertyId( 'P123' ),
			new Fingerprint( null, null, new AliasGroupList( [ new AliasGroup( $languageCode, $existingAliases ) ] ) ),
			'string'
		);
		$aliasesToAdd = [ 'alias 3', 'alias 4' ];
		$request = new AddPropertyAliasesInLanguageRequest(
			"{$property->getId()}",
			$languageCode,
			$aliasesToAdd,
			[],
			false,
			null,
			null
		);

		$this->propertyRetriever = $this->createStub( PropertyRetriever::class );
		$this->propertyRetriever->method( 'getProperty' )->willReturn( $property );

		$updatedAliases = array_merge( $existingAliases, $aliasesToAdd );
		$updatedProperty = new ReadModelProperty(
			new Labels(),
			new Descriptions(),
			new Aliases( new AliasesInLanguage( $languageCode, $updatedAliases ) ),
			new StatementList()
		);
		$this->propertyUpdater = $this->createMock( PropertyUpdater::class );
		$this->propertyUpdater->method( 'update' )
			->with(
				$this->callback(
					fn( Property $property ) => $property->getAliasGroups()
						->getByLanguage( $languageCode )
						->equals( new AliasGroup( $languageCode, $updatedAliases ) ),
				),
				new EditMetadata(
					[],
					false,
					AliasesInLanguageEditSummary::newAddSummary( null, new AliasGroup( $languageCode, $aliasesToAdd ) )
				)
			)
			->willReturn( new PropertyRevision( $updatedProperty, '20221111070707', 322 ) );

		$response = $this->newUseCase()->execute( $request );

		$this->assertEquals( new AliasesInLanguage( $languageCode, $updatedAliases ), $response->getAliases() );
		$this->assertTrue( $response->wasAddedToExistingAliasGroup() );
	}

	private function newUseCase(): AddPropertyAliasesInLanguage {
		return new AddPropertyAliasesInLanguage(
			$this->propertyRetriever,
			$this->propertyUpdater
		);
	}

}
