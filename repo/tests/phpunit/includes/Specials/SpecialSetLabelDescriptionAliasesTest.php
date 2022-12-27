<?php

namespace Wikibase\Repo\Tests\Specials;

use FauxRequest;
use FauxResponse;
use MediaWiki\Languages\LanguageNameUtils;
use NullStatsdDataFactory;
use SpecialPageExecutor;
use Status;
use WebRequest;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Diff\EntityDiffer;
use Wikibase\DataModel\Services\Diff\EntityPatcher;
use Wikibase\DataModel\Services\Lookup\TermLookup;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\Lib\StaticContentLanguages;
use Wikibase\Repo\ChangeOp\FingerprintChangeOpFactory;
use Wikibase\Repo\CopyrightMessageBuilder;
use Wikibase\Repo\EditEntity\EditFilterHookRunner;
use Wikibase\Repo\EditEntity\MediawikiEditEntityFactory;
use Wikibase\Repo\Specials\SpecialPageCopyrightView;
use Wikibase\Repo\Specials\SpecialSetLabelDescriptionAliases;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikibase\Repo\Store\TermsCollisionDetectorFactory;
use Wikibase\Repo\Validators\TermValidatorFactory;
use Wikibase\Repo\Validators\UniquenessViolation;

/**
 * @covers \Wikibase\Repo\Specials\SpecialSetLabelDescriptionAliases
 * @covers \Wikibase\Repo\Specials\SpecialModifyEntity
 * @covers \Wikibase\Repo\Specials\SpecialWikibaseRepoPage
 * @covers \Wikibase\Repo\Specials\SpecialWikibasePage
 *
 * @group Wikibase
 * @group SpecialPage
 * @group WikibaseSpecialPage
 * @group Database
 *
 * @license GPL-2.0-or-later
 */
class SpecialSetLabelDescriptionAliasesTest extends SpecialWikibaseRepoPageTestBase {
	use HtmlAssertionHelpers;

	private const TAGS = [ 'mw-replace' ];

	private static $languageCodes = [ 'en', 'de', 'de-ch', 'ii', 'zh' ];

	protected function setUp(): void {
		parent::setUp();
		$this->setUserLang( 'qqx' );
	}

	/**
	 * @see SpecialPageTestBase::newSpecialPage()
	 *
	 * @return SpecialSetLabelDescriptionAliases
	 */
	protected function newSpecialPage() {
		$copyrightView = new SpecialPageCopyrightView( new CopyrightMessageBuilder(), '', '' );

		return new SpecialSetLabelDescriptionAliases(
			self::TAGS,
			$copyrightView,
			$this->getSummaryFormatter(),
			$this->getEntityTitleLookup(),
			new MediawikiEditEntityFactory(
				$this->getEntityTitleLookup(),
				$this->getEntityRevisionLookup(),
				$this->getEntityStore(),
				$this->getEntityPermissionChecker(),
				new EntityDiffer(),
				new EntityPatcher(),
				$this->getMockEditFitlerHookRunner(),
				new NullStatsdDataFactory(),
				$this->getServiceContainer()->getUserOptionsLookup(),
				PHP_INT_MAX,
				[ 'item', 'property' ]
			),
			$this->getFingerprintChangeOpsFactory(),
			new StaticContentLanguages( self::$languageCodes ),
			$this->getEntityPermissionChecker(),
			$this->getServiceContainer()->getLanguageNameUtils()
		);
	}

	/**
	 * @return EditFilterHookRunner
	 */
	private function getMockEditFitlerHookRunner() {
		$runner = $this->getMockBuilder( EditFilterHookRunner::class )
			->onlyMethods( [ 'run' ] )
			->disableOriginalConstructor()
			->getMock();
		$runner->method( 'run' )
			->willReturn( Status::newGood() );
		return $runner;
	}

	/**
	 * @return FingerprintChangeOpFactory
	 */
	private function getFingerprintChangeOpsFactory() {
		$maxLength = 32;

		return new FingerprintChangeOpFactory(
			new TermValidatorFactory(
				$maxLength,
				self::$languageCodes,
				$this->getIdParser(),
				$this->createMock( TermsCollisionDetectorFactory::class ),
				$this->createMock( TermLookup::class ),
				$this->createMock( LanguageNameUtils::class )
			)
		);
	}

	/**
	 * Mock duplicate detection: the term "DUPE" is considered a duplicate.
	 *
	 * @param string[] $terms
	 *
	 * @return UniquenessViolation[]
	 */
	public function detectDupes( array $terms ) {
		$errors = [];

		foreach ( $terms as $languageCode => $term ) {
			if ( $term === 'DUPE' ) {
				$q666 = new ItemId( 'Q666' );

				$errors[] = new UniquenessViolation(
					$q666,
					'found conflicting terms',
					'test-conflict',
					[
						$term,
						$languageCode,
						$q666,
					]
				);
			}
		}

		return $errors;
	}

	/**
	 * @param string[] $labels
	 * @param string[] $descriptions
	 * @param array[] $aliases
	 *
	 * @return Fingerprint
	 */
	private function makeFingerprint(
		array $labels = [],
		array $descriptions = [],
		array $aliases = []
	) {
		$fingerprint = new Fingerprint();

		foreach ( $labels as $lang => $text ) {
			$fingerprint->setLabel( $lang, $text );
		}

		foreach ( $descriptions as $lang => $text ) {
			$fingerprint->setDescription( $lang, $text );
		}

		foreach ( $aliases as $lang => $texts ) {
			$fingerprint->setAliasGroup( $lang, $texts );
		}

		return $fingerprint;
	}

	public function executeProvider() {
		$fooFingerprint = $this->makeFingerprint(
			[ 'de' => 'foo' ]
		);

		return [
			'add label' => [
				$fooFingerprint,
				new FauxRequest( [
					'language' => 'en',
					'label' => "FOO\xE2\x80\x82",
					'aliases' => "\xE2\x80\x82",
				], true ),
				$this->makeFingerprint(
					[ 'de' => 'foo', 'en' => 'FOO' ]
				),
			],

			'replace label' => [
				$fooFingerprint,
				new FauxRequest( [ 'language' => 'de', 'label' => 'FOO' ], true ),
				$this->makeFingerprint(
					[ 'de' => 'FOO' ]
				),
			],

			'add description, keep label' => [
				$fooFingerprint,
				new FauxRequest( [ 'language' => 'de', 'description' => 'Lorem Ipsum' ], true ),
				$this->makeFingerprint(
					[ 'de' => 'foo' ],
					[ 'de' => 'Lorem Ipsum' ]
				),
			],

			'set aliases' => [
				$fooFingerprint,
				new FauxRequest( [
					'language' => 'de',
					'aliases' => "foo\xE2\x80\x82|bar",
				], true ),
				$this->makeFingerprint(
					[ 'de' => 'foo' ],
					[],
					[ 'de' => [ 'foo', 'bar' ] ]
				),
			],
		];
	}

	/**
	 * @dataProvider executeProvider
	 */
	public function testExecuteWithExistingItemIdAsSubPage(
		Fingerprint $inputFingerprint,
		?WebRequest $request,
		Fingerprint $expectedFingerprint
	) {
		$inputEntity = new Item();
		$inputEntity->setFingerprint( $inputFingerprint );

		$this->mockRepository->putEntity( $inputEntity );
		$id = $inputEntity->getId();

		list( , $response ) = $this->executeSpecialPage( $id->getSerialization(), $request );

		$redirect = $response instanceof FauxResponse ? $response->getHeader( 'Location' ) : null;
		// TODO: Look for an error message in $output.
		$this->assertNotEmpty( $redirect, 'Expected redirect after successful edit' );

		/** @var Item $actualEntity */
		$actualEntity = $this->mockRepository->getEntity( $id );
		$actualFingerprint = $actualEntity->getFingerprint();
		$this->assetFingerprintEquals( $expectedFingerprint, $actualFingerprint );

		$tags = $this->mockRepository->getLatestLogEntryFor( $id )['tags'];
		$this->assertArrayEquals( self::TAGS, $tags );
	}

	public function testAllFormFieldsRendered_WhenPageRendered() {
		list( $output ) = $this->executeSpecialPage( '' );

		$this->assertHtmlContainsInputWithName( $output, 'id' );
		$this->assertHtmlContainsInputWithNameAndValue( $output, 'language', 'qqx' );
		$this->assertHtmlContainsSubmitControl( $output );
	}

	public function testFormForEditingDataInUserLanguageIsDisplayed_WhenPageRenderedWithItemIdAsFirstSubPagePart() {
		$item = new Item();
		$this->mockRepository->putEntity( $item );

		list( $output ) = $this->executeSpecialPage( $item->getId()->getSerialization() );

		$this->assertThatHamcrest( $output, is( htmlPiece( havingChild(
			tagMatchingOutline( "<input name='id' type='hidden' value='{$item->getId()->getSerialization()}'/>" )
		) ) ) );
		$this->assertHtmlContainsInputWithNameAndValue( $output, 'language', 'qqx' );
		$this->assertHtmlContainsInputWithName( $output, 'label' );
		$this->assertHtmlContainsInputWithName( $output, 'description' );
		$this->assertHtmlContainsInputWithName( $output, 'aliases' );
		$this->assertHtmlContainsSubmitControl( $output );
	}

	public function testRendersEditFormInLanguageProvidedAsSecondPartOfSubPage() {
		$item = new Item();
		$this->mockRepository->putEntity( $item );
		$language = 'de';

		$subPage = $item->getId()->getSerialization() . '/' . $language;
		list( $output ) = $this->executeSpecialPage( $subPage );

		$this->assertThatHamcrest( $output, is( htmlPiece( havingChild(
			tagMatchingOutline( "<input name='language' type='hidden' value='$language'/>" )
		) ) ) );
		$this->assertHtmlContainsInputWithName( $output, 'label' );
		$this->assertHtmlContainsInputWithName( $output, 'description' );
		$this->assertHtmlContainsInputWithName( $output, 'aliases' );
		$this->assertHtmlContainsSubmitControl( $output );
	}

	public function testRendersEditFormInLanguageProvidedAsQueryParameter() {
		$item = new Item();
		$language = 'de';
		$label = 'de label';
		$description = 'de description';
		$alias = 'de alias';
		$item->setFingerprint(
			$this->makeFingerprint(
				[ $language => $label ],
				[ $language => $description ],
				[ $language => [ $alias ] ]
			)
		);
		$this->mockRepository->putEntity( $item );

		list( $output ) = $this->executeSpecialPage( $item->getId()->getSerialization(), new FauxRequest( [ 'language' => $language ] ) );

		$this->assertThatHamcrest( $output, is( htmlPiece( havingChild(
			tagMatchingOutline( "<input name='language' type='hidden' value='$language'/>" )
		) ) ) );
		$this->assertHtmlContainsInputWithNameAndValue( $output, 'label', $label );
		$this->assertHtmlContainsInputWithNameAndValue( $output, 'description', $description );
		$this->assertHtmlContainsInputWithNameAndValue( $output, 'aliases', $alias );
		$this->assertHtmlContainsSubmitControl( $output );
	}

	public function testLanguageCodeEscaping() {
		$request = new FauxRequest( [ 'language' => '<sup>' ], true );
		list( $output, ) = $this->executeSpecialPage( null, $request );

		$this->assertStringContainsString( '<p class="error">', $output );
		$this->assertStringContainsString( '&lt;sup&gt;', $output );
		$this->assertStringNotContainsString( '<sup>', $output, 'never unescaped' );
		$this->assertStringNotContainsString( '&amp;lt;', $output, 'no double escaping' );
	}

	private function assetFingerprintEquals( Fingerprint $expected, Fingerprint $actual ) {
		// TODO: Compare serializations.
		$this->assertTrue( $expected->equals( $actual ), 'Fingerprint mismatches' );
	}

	public function testGivenUserHasInsufficientPermissions_errorIsShown() {
		$inputEntity = new Item( null, $this->makeFingerprint( [ 'en' => 'a label' ] ) );

		$this->mockRepository->putEntity( $inputEntity );
		$id = $inputEntity->getId();

		$specialPage = $this->newSpecialPageWithForbiddingPermissionChecker();

		$request = new FauxRequest( [ 'language' => 'en', 'label' => 'new label' ], true );

		list( $output, ) = ( new SpecialPageExecutor() )->executeSpecialPage( $specialPage, $id->getSerialization(), $request );

		$this->assertThatHamcrest( $output, is( htmlPiece( havingChild(
			both( tagMatchingOutline( "<p class='error'/>" ) )
				->andAlso( havingTextContents( '(permissionserrors)' ) )
		) ) ) );
	}

	private function newSpecialPageWithForbiddingPermissionChecker() {
		$copyrightView = new SpecialPageCopyrightView( new CopyrightMessageBuilder(), '', '' );

		$error = Status::newFatal( 'permission error' );

		$permissionChecker = $this->createMock( EntityPermissionChecker::class );
		$permissionChecker->method( $this->anything() )
			->willReturn( $error );

		return new SpecialSetLabelDescriptionAliases(
			[],
			$copyrightView,
			$this->getSummaryFormatter(),
			$this->getEntityTitleLookup(),
			new MediawikiEditEntityFactory(
				$this->getEntityTitleLookup(),
				$this->getEntityRevisionLookup(),
				$this->getEntityStore(),
				$this->getEntityPermissionChecker(),
				new EntityDiffer(),
				new EntityPatcher(),
				$this->getMockEditFitlerHookRunner(),
				new NullStatsdDataFactory(),
				$this->getServiceContainer()->getUserOptionsLookup(),
				PHP_INT_MAX,
				[ 'item', 'property' ]
			),
			$this->getFingerprintChangeOpsFactory(),
			new StaticContentLanguages( self::$languageCodes ),
			$permissionChecker,
			$this->getServiceContainer()->getLanguageNameUtils()
		);
	}

	public function testGivenItemHasPipeInAlias_errorIsShown() {
		$request = new FauxRequest( [ 'language' => 'en', 'aliases' => 'new alias' ], true );
		$item = new Item();
		$item->setAliases( 'en', [ 'Foo|Bar' ] );
		$this->mockRepository->putEntity( $item );
		$subPage = $item->getId()->getSerialization() . '/en';

		list( $output, $response ) = $this->executeSpecialPage( $subPage, $request );
		$this->assertThatHamcrest( $output, is( htmlPiece( havingChild(
			both( tagMatchingOutline( "<p class='error'/>" ) )
				->andAlso( havingTextContents(
					'(wikibase-wikibaserepopage-pipe-in-alias)'
					) )
		) ) ) );
	}

}
