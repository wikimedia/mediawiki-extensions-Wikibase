<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Search\Fields;

use PHPUnit\Framework\TestCase;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\StaticContentLanguages;
use Wikibase\Repo\Search\Fields\FieldDefinitions;
use Wikibase\Repo\Search\Fields\FieldDefinitionsFactory;
use Wikibase\Repo\Search\Fields\NoFieldDefinitions;

/**
 * @covers \Wikibase\Repo\Search\Fields\FieldDefinitionsFactory
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class FieldDefinitionsFactoryTest extends TestCase {

	public function testWithCallback() {
		$termsLanguages = new StaticContentLanguages( [ 'qqx' ] );
		$repoSettings = new SettingsArray( [] );
		$fieldDefinitions = $this->createMock( FieldDefinitions::class );
		$entityTypeDefinitions = new EntityTypeDefinitions( [
			'test' => [
				EntityTypeDefinitions::SEARCH_FIELD_DEFINITIONS => function (
					$languageCodes,
					$settings
				) use ( $repoSettings, $fieldDefinitions ) {
					$this->assertSame( [ 'qqx' ], $languageCodes );
					$this->assertSame( $repoSettings, $settings );
					return $fieldDefinitions;
				},
			],
		] );

		$fieldDefinitionsFactory = new FieldDefinitionsFactory(
			$entityTypeDefinitions,
			$termsLanguages,
			$repoSettings
		);

		$this->assertSame( $fieldDefinitions,
			$fieldDefinitionsFactory->getFieldDefinitionsByType( 'test' ) );
	}

	/** @dataProvider provideEntityTypeDefinitionsWithoutSearchFieldDefinitions */
	public function testWithoutCallback( array $testDefinitions ) {
		$entityTypeDefinitions = new EntityTypeDefinitions( [
			'test' => $testDefinitions,
		] );
		$termsLanguages = new StaticContentLanguages( [] );
		$repoSettings = new SettingsArray( [] );

		$fieldDefinitionsFactory = new FieldDefinitionsFactory(
			$entityTypeDefinitions,
			$termsLanguages,
			$repoSettings
		);

		$this->assertInstanceOf( NoFieldDefinitions::class,
			$fieldDefinitionsFactory->getFieldDefinitionsByType( 'test' ) );
	}

	public function provideEntityTypeDefinitionsWithoutSearchFieldDefinitions(): iterable {
		yield 'search field definitions set to null' => [ [
			EntityTypeDefinitions::SEARCH_FIELD_DEFINITIONS => null,
		] ];
		yield 'search field definitions not set at all' => [ [
		] ];
	}

}
