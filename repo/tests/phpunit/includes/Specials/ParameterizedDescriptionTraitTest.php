<?php

declare( strict_types = 1 );
namespace Wikibase\Repo\Tests\Specials;

use MediaWiki\Languages\LanguageNameUtils;
use MediaWikiIntegrationTestCase;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\AnonymousEditWarningBuilder;
use Wikibase\Repo\ChangeOp\ChangeOpFactoryProvider;
use Wikibase\Repo\ChangeOp\FingerprintChangeOpFactory;
use Wikibase\Repo\EditEntity\MediaWikiEditEntityFactory;
use Wikibase\Repo\Specials\SpecialPageCopyrightView;
use Wikibase\Repo\Specials\SpecialSetAliases;
use Wikibase\Repo\Specials\SpecialSetDescription;
use Wikibase\Repo\Specials\SpecialSetLabel;
use Wikibase\Repo\Specials\SpecialSetLabelDescriptionAliases;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikibase\Repo\SummaryFormatter;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\Specials\ParameterizedDescriptionTrait
 *
 * @group Wikibase
 * @group SpecialPage
 *
 * @license GPL-2.0-or-later
 * @author Tobias Andersson
 */
class ParameterizedDescriptionTraitTest extends MediaWikiIntegrationTestCase {

	protected function setUp(): void {
		parent::setUp();
		$this->setUserLang( 'qqx' );
	}

	private function setFederatedPropertiesEnabled( bool $enabled ) {
		WikibaseRepo::getSettings()
			->setSetting( 'federatedPropertiesEnabled', $enabled );
	}

	/**
	 * @dataProvider specialPageProvider
	 */
	public function testParameterizedDescriptionOnSetDescriptionPage( $pageFactory, $expected ) {
		$page = $pageFactory( $this );
		$this->setFederatedPropertiesEnabled( false );
		$this->assertSame(
			$expected,
			$page->getDescription()->text()
		);
	}

	/**
	 * @dataProvider specialPageProvider_federatedPropertiesEnabled
	 */
	public function testParameterizedDescriptionOnSetDescriptionPage_federatedPropertiesEnabled( $pageFactory, $expected ) {
		$page = $pageFactory( $this );
		$this->setFederatedPropertiesEnabled( true );
		$this->assertSame(
			$expected,
			$page->getDescription()->text()
		);
	}

	/**
	 * @return array [ string, expectedOutput ]
	 */
	public static function specialPageProvider(): iterable {
		return [
			'SpecialSetLabel' => [
				fn ( self $self ) => $self->newSpecialSetLabelsPage(),
				'(special-setlabel-parameterized: (wikibase-entity-item)' .
				'(special-parameterized-description-separator)(wikibase-entity-property))',
			],
			'SpecialSetAliases' => [
				fn ( self $self ) => $self->newSpecialSetAliasesPage(),
				'(special-setaliases-parameterized: (wikibase-entity-item)' .
				'(special-parameterized-description-separator)(wikibase-entity-property))',
			],
			'SpecialSetDescription' => [
				fn ( self $self ) => $self->newSpecialSetDescriptionPage(),
				'(special-setdescription-parameterized: (wikibase-entity-item)' .
				'(special-parameterized-description-separator)(wikibase-entity-property))',
			],
			'SpecialSetLabelDescriptionAliases' => [
				fn ( self $self ) => $self->newSpecialSpecialSetLabelDescriptionAliases(),
				'(special-setlabeldescriptionaliases-parameterized: (wikibase-entity-item)' .
				'(special-parameterized-description-separator)(wikibase-entity-property))',
			],
		];
	}

	/**
	 * @return array [ string, expectedOutput ]
	 */
	public static function specialPageProvider_federatedPropertiesEnabled(): iterable {
		return [
			'SpecialSetLabel' => [
				fn ( self $self ) => $self->newSpecialSetLabelsPage(),
				'(special-setlabel-parameterized: (wikibase-entity-item))',
			],
			'SpecialSetAliases' => [
				fn ( self $self ) => $self->newSpecialSetAliasesPage(),
				'(special-setaliases-parameterized: (wikibase-entity-item))',
			],
			'SpecialSetDescription' => [
				fn ( self $self ) => $self->newSpecialSetDescriptionPage(),
				'(special-setdescription-parameterized: (wikibase-entity-item))',
			],
			'SpecialSetLabelDescriptionAliases' => [
				fn ( self $self ) => $self->newSpecialSpecialSetLabelDescriptionAliases(),
				'(special-setlabeldescriptionaliases-parameterized: (wikibase-entity-item))',
			],

		];
	}

	private function newSpecialSetAliasesPage() {
		return new SpecialSetAliases(
			[],
			$this->createMock( ChangeOpFactoryProvider::class ),
			$this->createMock( SpecialPageCopyrightView::class ),
			$this->createMock( SummaryFormatter::class ),
			$this->createMock( EntityTitleLookup::class ),
			$this->createMock( MediaWikiEditEntityFactory::class ),
			$this->createMock( AnonymousEditWarningBuilder::class ),
			$this->createMock( EntityPermissionChecker::class ),
			$this->createMock( ContentLanguages::class ),
			$this->createMock( LanguageNameUtils::class )
		);
	}

	private function newSpecialSetLabelsPage() {
		return new SpecialSetLabel(
			[],
			$this->createMock( ChangeOpFactoryProvider::class ),
			$this->createMock( SpecialPageCopyrightView::class ),
			$this->createMock( SummaryFormatter::class ),
			$this->createMock( EntityTitleLookup::class ),
			$this->createMock( MediaWikiEditEntityFactory::class ),
			$this->createMock( AnonymousEditWarningBuilder::class ),
			$this->createMock( EntityPermissionChecker::class ),
			$this->createMock( ContentLanguages::class ),
			$this->createMock( LanguageNameUtils::class )
		);
	}

	private function newSpecialSetDescriptionPage() {
		return new SpecialSetDescription(
			[],
			$this->createMock( ChangeOpFactoryProvider::class ),
			$this->createMock( SpecialPageCopyrightView::class ),
			$this->createMock( SummaryFormatter::class ),
			$this->createMock( EntityTitleLookup::class ),
			$this->createMock( MediaWikiEditEntityFactory::class ),
			$this->createMock( AnonymousEditWarningBuilder::class ),
			$this->createMock( EntityPermissionChecker::class ),
			$this->createMock( ContentLanguages::class ),
			$this->createMock( LanguageNameUtils::class )
		);
	}

	private function newSpecialSpecialSetLabelDescriptionAliases() {
		return new SpecialSetLabelDescriptionAliases(
			[],
			$this->createMock( SpecialPageCopyrightView::class ),
			$this->createMock( SummaryFormatter::class ),
			$this->createMock( EntityTitleLookup::class ),
			$this->createMock( MediaWikiEditEntityFactory::class ),
			$this->createMock( AnonymousEditWarningBuilder::class ),
			$this->createMock( FingerprintChangeOpFactory::class ),
			$this->createMock( ContentLanguages::class ),
			$this->createMock( EntityPermissionChecker::class ),
			$this->createMock( LanguageNameUtils::class ),
			'savechanges'
		);
	}

}
