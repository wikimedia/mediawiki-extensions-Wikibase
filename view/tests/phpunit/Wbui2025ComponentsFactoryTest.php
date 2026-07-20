<?php

declare( strict_types = 1 );

namespace Wikibase\View\Tests;

use MediaWiki\Language\Language;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Serializers\StatementSerializer;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\View\EntityIdFormatterFactory;
use Wikibase\View\LocalizedTextProvider;
use Wikibase\View\Wbui2025ComponentsFactory;
use Wikimedia\Assert\InvariantException;
use WMDE\VueJsTemplating\App;

/**
 * @covers \Wikibase\View\Wbui2025ComponentsFactory
 *
 * @group Wikibase
 * @group WikibaseView
 *
 * @license GPL-2.0-or-later
 * @author Mahmoud Abdelsattar <mahmoud.abdelsattar@wikimedia.de>
 */
class Wbui2025ComponentsFactoryTest extends TestCase {

	private Wbui2025ComponentsFactory $factory;
	private LocalizedTextProvider $textProvider;
	private EntityIdParser $entityIdParser;
	private StatementSerializer $statementSerializer;
	private EntityIdFormatterFactory $entityIdFormatterFactory;
	private PropertyDataTypeLookup $propertyDataTypeLookup;
	private Language $language;
	private StatementList $allStatements;
	private array $propertyExistence = [];

	protected function setUp(): void {
		$this->factory = new Wbui2025ComponentsFactory();
		$this->textProvider = $this->createMock( LocalizedTextProvider::class );
		$this->entityIdParser = $this->createMock( EntityIdParser::class );
		$this->statementSerializer = $this->createMock( StatementSerializer::class );
		$this->entityIdFormatterFactory = $this->createMock( EntityIdFormatterFactory::class );
		$this->propertyDataTypeLookup = $this->createMock( PropertyDataTypeLookup::class );
		$this->language = $this->createMock( Language::class );
		$this->allStatements = $this->createMock( StatementList::class );
	}

	/**
	 * Runs a real registerTemplates() call and returns the setup callback captured for
	 * $componentName, so a test can invoke it directly with its own input.
	 */
	private function captureSetup( string $componentName ): callable {
		$app = $this->createMock( App::class );
		$captured = null;
		$app->method( 'registerComponentTemplate' )->willReturnCallback(
			function ( string $name, callable $templateCallable, ?callable $computedFunctions = null ) use (
				$componentName,
				&$captured
			): void {
				if ( $name === $componentName ) {
					$captured = $computedFunctions;
				}
			}
		);

		$this->factory->registerTemplates(
			$app,
			$this->allStatements,
			$this->propertyExistence,
			$this->textProvider,
			$this->entityIdParser,
			$this->statementSerializer,
			$this->entityIdFormatterFactory,
			$this->language,
			$this->propertyDataTypeLookup
		);

		$this->assertIsCallable( $captured, "No setup callback captured for '$componentName'" );
		return $captured;
	}

	public function testGetTemplateFiles_isNotEmpty(): void {
		$this->assertNotEmpty( $this->factory->getTemplateFiles() );
	}

	public function testGetTemplateFiles_valuesAreRepoRelativePaths(): void {
		foreach ( $this->factory->getTemplateFiles() as $relPath ) {
			$this->assertStringStartsWith( 'resources/wikibase.wbui2025/', $relPath );
		}
	}

	public function testGetTemplateCallable_returnsCallableForKnownComponent(): void {
		$callable = $this->factory->getTemplateCallable( 'wbui2025-statement-sections' );
		$this->assertIsCallable( $callable );
	}

	public function testGetTemplateCallable_callableReturnsVueSfcContent(): void {
		$callable = $this->factory->getTemplateCallable( 'wbui2025-statement-sections' );
		$content = $callable();
		$this->assertIsString( $content );
		$this->assertStringContainsString( '<template>', $content );
	}

	public function testGetTemplateCallable_throwsForUnknownComponent(): void {
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessageMatches( '/Unknown wbui2025 component/' );
		$this->factory->getTemplateCallable( 'wbui2025-does-not-exist' );
	}

	public function testRegisterComponentTemplate_registersTemplateWithApp(): void {
		$app = $this->createMock( App::class );

		$computedFunctions = static function ( array $data ): array {
			return $data;
		};

		$app->expects( $this->once() )
			->method( 'registerComponentTemplate' )
			->with(
				'wbui2025-statement-sections',
				$this->isType( 'callable' ),
				$computedFunctions
			);

		$this->factory->registerComponentTemplate(
			$app,
			'wbui2025-statement-sections',
			$computedFunctions
		);
	}

	/**
	 * registerComponentTemplates() is what VueStylesModule also calls to build its
	 * style-extraction App ... this is what makes COMPONENT_FILES the one and only list of
	 * wbui2025 components, instead of a second, separately maintained list.
	 */
	public function testRegisterComponentTemplates_registersEveryComponentFileWithNoSetup(): void {
		$app = $this->createMock( App::class );

		$registeredComponentNames = [];

		$app->method( 'registerComponentTemplate' )
			->willReturnCallback(
				function (
					string $componentName,
					callable $templateCallable,
					?callable $computedFunctions = null
				) use ( &$registeredComponentNames ): void {
					$registeredComponentNames[] = $componentName;
					$this->assertIsCallable( $templateCallable );
					$this->assertNull( $computedFunctions );
				}
			);

		$this->factory->registerComponentTemplates( $app );

		$expectedComponentNames = array_keys( $this->factory->getTemplateFiles() );
		sort( $expectedComponentNames );
		sort( $registeredComponentNames );
		$this->assertSame( $expectedComponentNames, $registeredComponentNames );
	}

	public function testRegisterTemplates_registersExpectedComponentTemplatesWithSetups(): void {
		$app = $this->createMock( App::class );
		$componentsWithSetup = [];

		$app->method( 'registerComponentTemplate' )
			->willReturnCallback(
				function (
					string $componentName,
					callable $templateCallable,
					?callable $computedFunctions = null
				) use ( &$componentsWithSetup ): void {
					$this->assertIsCallable( $templateCallable );
					if ( $computedFunctions !== null ) {
						$componentsWithSetup[] = $componentName;
					}
				}
			);

		$this->factory->registerTemplates(
			$app,
			$this->allStatements,
			[],
			$this->textProvider,
			$this->entityIdParser,
			$this->statementSerializer,
			$this->entityIdFormatterFactory,
			$this->language,
			$this->propertyDataTypeLookup
		);

		$expectedComponentNames = [
			'wbui2025-statement-sections',
			'wbui2025-main-snak',
			'wbui2025-statement-group-view',
			'wbui2025-statement-view',
			'wbui2025-property-name',
			'wbui2025-references',
			'wbui2025-qualifiers',
			'wbui2025-snak-value',
		];
		sort( $expectedComponentNames );
		sort( $componentsWithSetup );
		$this->assertSame( $expectedComponentNames, $componentsWithSetup );
	}

	/**
	 * Each test below calls one moved setup closure directly and checks its real output and,
	 * where cheap, the exact arguments it passes to its collaborators -- not just that a
	 * callback got registered (that's what the tests above already cover). Split per component
	 * so a failure in one doesn't hide the others, and each is independently readable.
	 */
	public function testStatementSectionsSetup_movesPropertyListAndDisablesJs(): void {
		$setup = $this->captureSetup( 'wbui2025-statement-sections' );
		$result = $setup( [ 'propertyList' => [ 'P1', 'P2' ] ] );

		$this->assertSame( [ 'P1', 'P2' ], $result['propertyIds'] );
		$this->assertFalse( $result['javaScriptLoaded'] );
	}

	public function testMainSnakSetup_looksUpRankMessageByKey(): void {
		$this->textProvider->expects( $this->once() )
			->method( 'get' )
			->with( 'wikibase-statementview-rank-preferred' )
			->willReturn( 'Preferred rank' );

		$setup = $this->captureSetup( 'wbui2025-main-snak' );
		$result = $setup( [ 'rank' => 'preferred' ] );

		$this->assertSame( 'Preferred rank', $result['rankTitleString'] );
		$this->assertFalse( $result['showIndicators'] );
	}

	public function testStatementGroupViewSetup_marksDeletedPropertyAndSerializesItsStatements(): void {
		$propertyId = new NumericPropertyId( 'P1' );
		$this->entityIdParser->method( 'parse' )->with( 'P1' )->willReturn( $propertyId );
		$this->propertyExistence = [ 'P1' => false ];

		$statementsForProperty = new StatementList( $this->createMock( Statement::class ), $this->createMock( Statement::class ) );
		$this->allStatements->expects( $this->once() )
			->method( 'getByPropertyId' )
			->with( $propertyId )
			->willReturn( $statementsForProperty );
		$this->statementSerializer->method( 'serialize' )->willReturn( [ 'serialized' => true ] );
		$setup = $this->captureSetup( 'wbui2025-statement-group-view' );
		$result = $setup( [ 'propertyId' => 'P1' ] );

		$this->assertTrue( $result['isDeletedProperty'] );
		$this->assertSame( [ [ 'serialized' => true ], [ 'serialized' => true ] ], $result['statements'] );
		$this->assertFalse( $result['showModalEditForm'] );
	}

	public function testStatementViewSetup_addsPreferredClassForPreferredRank(): void {
		$statement = $this->createMock( Statement::class );
		$this->allStatements->expects( $this->once() )
			->method( 'getFirstStatementWithGuid' )
			->with( 'Q1$guid' )
			->willReturn( $statement );
		$this->statementSerializer->method( 'serialize' )->with( $statement )->willReturn( [ 'rank' => 'preferred' ] );
		$setup = $this->captureSetup( 'wbui2025-statement-view' );
		$result = $setup( [ 'statementId' => 'Q1$guid' ] );

		$this->assertSame( [ 'rank' => 'preferred' ], $result['statement'] );
		$this->assertSame( [], $result['references'] );
		$this->assertSame( [], $result['qualifiers'] );
		$this->assertSame( [], $result['qualifiersOrder'] );
		$this->assertSame( [ 'wikibase-wbui2025-statement-view', 'wb-preferred' ], $result['activeClasses'] );
	}

	public function testStatementViewSetup_addsDeprecatedClassForDeprecatedRank(): void {
		$this->allStatements->method( 'getFirstStatementWithGuid' )->willReturn( $this->createMock( Statement::class ) );
		$this->statementSerializer->method( 'serialize' )->willReturn( [ 'rank' => 'deprecated' ] );
		$setup = $this->captureSetup( 'wbui2025-statement-view' );
		$result = $setup( [ 'statementId' => 'Q1$guid' ] );
		$this->assertSame( [ 'wikibase-wbui2025-statement-view', 'wb-deprecated' ], $result['activeClasses'] );
	}

	public function testStatementViewSetup_throwsWhenStatementNotFound(): void {
		$this->allStatements->method( 'getFirstStatementWithGuid' )->willReturn( null );
		$setup = $this->captureSetup( 'wbui2025-statement-view' );
		$this->expectException( InvariantException::class );
		$this->expectExceptionMessageMatches( '/Q1\$guid/' );
		$setup( [ 'statementId' => 'Q1$guid' ] );
	}

	public function testPropertyNameSetup_formatsLinkAndMarksDeletedProperty(): void {
		$propertyId = new NumericPropertyId( 'P1' );
		$this->entityIdParser->method( 'parse' )->with( 'P1' )->willReturn( $propertyId );
		$this->propertyExistence = [ 'P1' => true ];

		$entityIdFormatter = $this->createMock( EntityIdFormatter::class );
		$entityIdFormatter->method( 'formatEntityId' )->with( $propertyId )->willReturn( '<a>P1</a>' );
		$this->entityIdFormatterFactory->method( 'getEntityIdFormatter' )->with( $this->language )->willReturn( $entityIdFormatter );
		$setup = $this->captureSetup( 'wbui2025-property-name' );
		$result = $setup( [ 'propertyId' => 'P1' ] );

		$this->assertSame( '<a>P1</a>', $result['propertyLinkHtml'] );
		$this->assertFalse( $result['isDeletedProperty'] );
	}

	public function testReferencesSetup_countsAndFormatsReferenceMessage(): void {
		$this->textProvider->expects( $this->once() )
			->method( 'getEscaped' )
			->with( 'wikibase-statementview-references-counter', [ '2' ] )
			->willReturn( '2 references' );
		$setup = $this->captureSetup( 'wbui2025-references' );
		$result = $setup( [ 'references' => [ 'a', 'b' ] ] );

		$this->assertSame( 2, $result['referenceCount'] );
		$this->assertTrue( $result['hasReferences'] );
		$this->assertSame( '2 references', $result['referencesMessage'] );
		$this->assertFalse( $result['showReferences'] );
		$this->assertFalse( $result['showIndicators'] );
	}

	public function testReferencesSetup_hasReferencesIsFalseWhenEmpty(): void {
		$setup = $this->captureSetup( 'wbui2025-references' );
		$result = $setup( [ 'references' => [] ] );
		$this->assertSame( 0, $result['referenceCount'] );
		$this->assertFalse( $result['hasReferences'] );
	}

	public function testQualifiersSetup_hasQualifiersReflectsCount(): void {
		$setup = $this->captureSetup( 'wbui2025-qualifiers' );

		$this->assertTrue( $setup( [ 'qualifiers' => [ 'a' ] ] )['hasQualifiers'] );
		$this->assertFalse( $setup( [ 'qualifiers' => [] ] )['hasQualifiers'] );
	}

	public function testSnakValueSetup_setsClassForKnownDataType(): void {
		$propertyId = new NumericPropertyId( 'P1' );
		$this->entityIdParser->method( 'parse' )->with( 'P1' )->willReturn( $propertyId );
		$this->propertyDataTypeLookup->method( 'getDataTypeIdForProperty' )->with( $propertyId )->willReturn( 'commonsMedia' );
		$setup = $this->captureSetup( 'wbui2025-snak-value' );
		$result = $setup( [ 'snak' => [ 'property' => 'P1' ] ] );

		$this->assertTrue( $result['snakValueClass']['wikibase-wbui2025-media-value'] );
		$this->assertFalse( $result['snakValueClass']['wikibase-wbui2025-time-value'] );
	}
}
