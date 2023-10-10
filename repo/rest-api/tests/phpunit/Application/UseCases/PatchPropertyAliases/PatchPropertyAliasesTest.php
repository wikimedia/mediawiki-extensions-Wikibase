<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\PatchPropertyAliases;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property as DataModelProperty;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\Repo\RestApi\Application\Serialization\AliasesDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\AliasesSerializer;
use Wikibase\Repo\RestApi\Application\UseCases\PatchJson;
use Wikibase\Repo\RestApi\Application\UseCases\PatchPropertyAliases\PatchPropertyAliases;
use Wikibase\Repo\RestApi\Application\UseCases\PatchPropertyAliases\PatchPropertyAliasesRequest;
use Wikibase\Repo\RestApi\Domain\ReadModel\Aliases;
use Wikibase\Repo\RestApi\Domain\ReadModel\AliasesInLanguage;
use Wikibase\Repo\RestApi\Domain\ReadModel\Descriptions;
use Wikibase\Repo\RestApi\Domain\ReadModel\Labels;
use Wikibase\Repo\RestApi\Domain\ReadModel\Property;
use Wikibase\Repo\RestApi\Domain\ReadModel\PropertyRevision;
use Wikibase\Repo\RestApi\Domain\ReadModel\StatementList;
use Wikibase\Repo\RestApi\Domain\Services\PropertyAliasesRetriever;
use Wikibase\Repo\RestApi\Domain\Services\PropertyRetriever;
use Wikibase\Repo\RestApi\Domain\Services\PropertyUpdater;
use Wikibase\Repo\RestApi\Infrastructure\JsonDiffJsonPatcher;
use Wikibase\Repo\RestApi\Infrastructure\ValidatingRequestDeserializer;
use Wikibase\Repo\Tests\RestApi\Application\UseCaseRequestValidation\TestValidatingRequestDeserializer;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\PatchPropertyAliases\PatchPropertyAliases
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PatchPropertyAliasesTest extends TestCase {

	private ValidatingRequestDeserializer $validator;
	private PropertyAliasesRetriever $aliasesRetriever;
	private AliasesSerializer $aliasesSerializer;
	private PatchJson $patchJson;
	private PropertyRetriever $propertyRetriever;
	private AliasesDeserializer $aliasesDeserializer;
	private PropertyUpdater $propertyUpdater;

	protected function setUp(): void {
		parent::setUp();

		$this->validator = new TestValidatingRequestDeserializer();
		$this->aliasesRetriever = $this->createStub( PropertyAliasesRetriever::class );
		$this->aliasesSerializer = new AliasesSerializer();
		$this->patchJson = new PatchJson( new JsonDiffJsonPatcher() );
		$this->propertyRetriever = $this->createStub( PropertyRetriever::class );
		$this->aliasesDeserializer = new AliasesDeserializer();
		$this->propertyUpdater = $this->createStub( PropertyUpdater::class );
	}

	public function testHappyPath(): void {
		$propertyId = new NumericPropertyId( 'P123' );
		$originalAliases = new AliasGroupList( [
			new AliasGroup( 'en', [ 'spud', 'tater' ] ),
			new AliasGroup( 'de', [ 'Erdapfel' ] ),
		] );
		$request = new PatchPropertyAliasesRequest(
			"$propertyId",
			[
				[ 'op' => 'remove', 'path' => '/de' ],
				[ 'op' => 'add', 'path' => '/en/-', 'value' => 'Solanum tuberosum' ],
			]
		);
		$patchedAliasesSerialization = [ 'en' => [ 'spud', 'tater', 'Solanum tuberosum' ] ];
		$expectedAliases = new Aliases( new AliasesInLanguage( 'en', $patchedAliasesSerialization['en'] ) );
		$revisionId = 657;
		$lastModified = '20221212040506';

		$this->aliasesRetriever = $this->createStub( PropertyAliasesRetriever::class );
		$this->aliasesRetriever->method( 'getAliases' )->willReturn( Aliases::fromAliasGroupList( $originalAliases ) );

		$this->propertyRetriever = $this->createStub( PropertyRetriever::class );
		$this->propertyRetriever->method( 'getProperty' )->willReturn(
			new DataModelProperty( $propertyId, new Fingerprint( null, null, $originalAliases ), 'string' )
		);

		$this->propertyUpdater = $this->createMock( PropertyUpdater::class );
		$this->propertyUpdater->expects( $this->once() )
			->method( 'update' )
			->with(
				$this->callback( fn( DataModelProperty $p ) => $p->getAliasGroups()->toTextArray() === $patchedAliasesSerialization )
			)
			->willReturn( new PropertyRevision(
				new Property(
					new Labels(),
					new Descriptions(),
					$expectedAliases,
					new StatementList()
				),
				$lastModified,
				$revisionId
			) );

		$response = $this->newUseCase()->execute( $request );

		$this->assertSame( $expectedAliases, $response->getAliases() );
		$this->assertSame( $revisionId, $response->getRevisionId() );
		$this->assertSame( $lastModified, $response->getLastModified() );
	}

	private function newUseCase(): PatchPropertyAliases {
		return new PatchPropertyAliases(
			$this->validator,
			$this->aliasesRetriever,
			$this->aliasesSerializer,
			$this->patchJson,
			$this->propertyRetriever,
			$this->aliasesDeserializer,
			$this->propertyUpdater
		);
	}

}
