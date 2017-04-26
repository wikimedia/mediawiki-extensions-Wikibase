<?php

namespace Wikibase\Repo\Tests\Specials;

use FauxRequest;
use FauxResponse;
use Status;
use ValueValidators\Result;
use WebRequest;
use Wikibase\ChangeOp\FingerprintChangeOpFactory;
use Wikibase\CopyrightMessageBuilder;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Diff\EntityDiffer;
use Wikibase\DataModel\Services\Diff\EntityPatcher;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\EditEntityFactory;
use Wikibase\LabelDescriptionDuplicateDetector;
use Wikibase\Lib\StaticContentLanguages;
use Wikibase\Repo\Hooks\EditFilterHookRunner;
use Wikibase\Repo\Specials\SpecialPageCopyrightView;
use Wikibase\Repo\Specials\SpecialSetLabelDescriptionAliases;
use Wikibase\Repo\Validators\TermValidatorFactory;
use Wikibase\Repo\Validators\UniquenessViolation;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers Wikibase\Repo\Specials\SpecialSetLabelDescriptionAliases
 * @covers Wikibase\Repo\Specials\SpecialModifyEntity
 * @covers Wikibase\Repo\Specials\SpecialWikibaseRepoPage
 * @covers Wikibase\Repo\Specials\SpecialWikibasePage
 *
 * @group Wikibase
 * @group SpecialPage
 * @group WikibaseSpecialPage
 * @group Database
 *
 * @license GPL-2.0+
 */
class SpecialSetLabelDescriptionAliasesTest extends SpecialWikibaseRepoPageTestBase {

	use HtmlAssertionHelpers;

	private static $languageCodes = array( 'en', 'de', 'de-ch', 'ii', 'zh' );

	const USER_LANGUAGE = 'en';

	protected function setUp() {
		parent::setUp();

		$this->setUserLang( self::USER_LANGUAGE );
	}

	/**
	 * @see SpecialPageTestBase::newSpecialPage()
	 *
	 * @return SpecialSetLabelDescriptionAliases
	 */
	protected function newSpecialPage() {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();

		$copyrightView = new SpecialPageCopyrightView( new CopyrightMessageBuilder(), '', '' );

		return new SpecialSetLabelDescriptionAliases(
			$copyrightView,
			$this->getSummaryFormatter(),
			$this->getEntityRevisionLookup(),
			$this->getEntityTitleLookup(),
			new EditEntityFactory(
				$this->getEntityTitleLookup(),
				$this->getEntityRevisionLookup(),
				$this->getEntityStore(),
				$this->getEntityPermissionChecker(),
				new EntityDiffer(),
				new EntityPatcher(),
				$this->getMockEditFitlerHookRunner()
			),
			$this->getFingerprintChangeOpsFactory(),
			new StaticContentLanguages( self::$languageCodes )
		);
	}

	/**
	 * @return EditFilterHookRunner
	 */
	private function getMockEditFitlerHookRunner() {
		$runner = $this->getMockBuilder( EditFilterHookRunner::class )
			->setMethods( array( 'run' ) )
			->disableOriginalConstructor()
			->getMock();
		$runner->expects( $this->any() )
			->method( 'run' )
			->will( $this->returnValue( Status::newGood() ) );
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
				$this->getLabelDescriptionDuplicateDetector()
			)
		);
	}

	/**
	 * @return LabelDescriptionDuplicateDetector
	 */
	private function getLabelDescriptionDuplicateDetector() {
		$detector = $this->getMockBuilder( LabelDescriptionDuplicateDetector::class )
			->disableOriginalConstructor()
			->getMock();

		$detector->expects( $this->any() )
			->method( 'detectLabelDescriptionConflicts' )
			->will( $this->returnCallback( function(
				$entityType,
				array $labels,
				array $descriptions,
				EntityId $ignoreEntityId = null
			) {
				$errors = array();

				$errors = array_merge( $errors, $this->detectDupes( $labels ) );
				$errors = array_merge( $errors, $this->detectDupes( $descriptions ) );

				$result = empty( $errors ) ? Result::newSuccess() : Result::newError( $errors );
				return $result;
			} ) );

		return $detector;
	}

	/**
	 * Mock duplicate detection: the term "DUPE" is considered a duplicate.
	 *
	 * @param string[] $terms
	 *
	 * @return UniquenessViolation[]
	 */
	public function detectDupes( array $terms ) {
		$errors = array();

		foreach ( $terms as $languageCode => $term ) {
			if ( $term === 'DUPE' ) {
				$q666 = new ItemId( 'Q666' );

				$errors[] = new UniquenessViolation(
					$q666,
					'found conflicting terms',
					'test-conflict',
					array(
						$term,
						$languageCode,
						$q666,
					)
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
		array $labels = array(),
		array $descriptions = array(),
		array $aliases = array()
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
			array( 'de' => 'foo' )
		);

		return array(
			'add label' => array(
				$fooFingerprint,
				new FauxRequest( array(
					'language' => 'en',
					'label' => "FOO\xE2\x80\x82",
					'aliases' => "\xE2\x80\x82",
				), true ),
				$this->makeFingerprint(
					array( 'de' => 'foo', 'en' => 'FOO' )
				),
			),

			'replace label' => array(
				$fooFingerprint,
				new FauxRequest( array( 'language' => 'de', 'label' => 'FOO' ), true ),
				$this->makeFingerprint(
					array( 'de' => 'FOO' )
				),
			),

			'add description, keep label' => array(
				$fooFingerprint,
				new FauxRequest( array( 'language' => 'de', 'description' => 'Lorem Ipsum' ), true ),
				$this->makeFingerprint(
					array( 'de' => 'foo' ),
					array( 'de' => 'Lorem Ipsum' )
				),
			),

			'set aliases' => array(
				$fooFingerprint,
				new FauxRequest( array(
					'language' => 'de',
					'aliases' => "foo\xE2\x80\x82|bar",
				), true ),
				$this->makeFingerprint(
					array( 'de' => 'foo' ),
					array(),
					array( 'de' => array( 'foo', 'bar' ) )
				),
			),
		);
	}

	/**
	 * @dataProvider executeProvider
	 */
	public function testExecuteWithExistingItemIdAsSubPage(
		Fingerprint $inputFingerprint,
		WebRequest $request = null,
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
	}

	public function testAllFormFieldsRendered_WhenPageRendered() {

		list( $output ) = $this->executeSpecialPage( '' );

		$this->assertHtmlContainsInputWithName( $output, 'id' );
		$this->assertHtmlContainsInputWithNameAndValue( $output, 'language', self::USER_LANGUAGE );
		$this->assertHtmlContainsSubmitControl( $output );
	}

	public function testFormForEditingDataInUserLanguageIsDisplayed_WhenPageRenderedWithItemIdAsFirstSubPagePart() {
		$item = new Item();
		$this->mockRepository->putEntity( $item );

		list( $output ) = $this->executeSpecialPage( $item->getId()->getSerialization() );

		assertThat( $output, is( htmlPiece( havingChild(
			tagMatchingOutline( "<input name='id' type='hidden' value='{$item->getId()->getSerialization()}'/>" )
		) ) ) );
		$this->assertHtmlContainsInputWithNameAndValue( $output, 'language', self::USER_LANGUAGE );
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

		assertThat( $output, is( htmlPiece( havingChild(
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

		assertThat( $output, is( htmlPiece( havingChild(
			tagMatchingOutline( "<input name='language' type='hidden' value='$language'/>" )
		) ) ) );
		$this->assertHtmlContainsInputWithNameAndValue( $output, 'label', $label );
		$this->assertHtmlContainsInputWithNameAndValue( $output, 'description', $description );
		$this->assertHtmlContainsInputWithNameAndValue( $output, 'aliases', $alias );
		$this->assertHtmlContainsSubmitControl( $output );
	}

	public function testLanguageCodeEscaping() {
		$request = new FauxRequest( array( 'language' => '<sup>' ), true );
		list( $output, ) = $this->executeSpecialPage( null, $request );

		$this->assertContains( '<p class="error">', $output );
		$this->assertContains( '&lt;sup&gt;', $output );
		$this->assertNotContains( '<sup>', $output, 'never unescaped' );
		$this->assertNotContains( '&amp;lt;', $output, 'no double escaping' );
	}

	private function assetFingerprintEquals( Fingerprint $expected, Fingerprint $actual ) {
		// TODO: Compare serializations.
		$this->assertTrue( $expected->equals( $actual ), 'Fingerprint mismatches' );
	}

}
