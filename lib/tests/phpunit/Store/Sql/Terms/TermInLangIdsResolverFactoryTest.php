<?php

declare( strict_types = 1 );

namespace Wikibase\Lib\Store\Sql\Terms;

use MediaWiki\MediaWikiServices;
use MediaWikiIntegrationTestCase;
use Psr\Log\NullLogger;
use Wikibase\DataAccess\DatabaseEntitySource;
use Wikibase\Lib\Tests\Rdbms\LocalRepoDbTestHelper;
use Wikibase\Lib\WikibaseSettings;

/**
 * @covers \Wikibase\Lib\Store\Sql\Terms\TermInLangIdsResolverFactory
 *
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0-or-later
 */
class TermInLangIdsResolverFactoryTest extends MediaWikiIntegrationTestCase {

	use LocalRepoDbTestHelper;

	private const MOCK_LANG_LABELS = [
		'de' => 'Hallo Welt',
		'en' => 'Hello World',
		'he' => 'שלום עולם',
	];

	private const MOCK_TYPE_LABEL = 1;
	/**
	 * @var array
	 */
	private $termIds;

	protected function setUp(): void {
		parent::setUp();

		$this->setUpDB();
	}

	private function setUpDB(): void {
		if ( !WikibaseSettings::isRepoEnabled() ) {
			$this->markTestSkipped( "Skipping because WikibaseClient doesn't have local term store tables." );
		}

		$this->tablesUsed[] = 'wbt_type';
		$this->tablesUsed[] = 'wbt_text';
		$this->tablesUsed[] = 'wbt_text_in_lang';
		$this->tablesUsed[] = 'wbt_term_in_lang';

		$this->db->insert( 'wbt_type', [
			'wby_id' => self::MOCK_TYPE_LABEL,
			'wby_name' => 'label',
		] );

		$this->termIds = array_map(
			function ( string $lang, string $label ): int {
				$this->db->insert( 'wbt_text', [
					'wbx_text' => $label,
				] );

				$this->db->insert( 'wbt_text_in_lang', [
					'wbxl_language' => $lang,
					'wbxl_text_id' => $this->db->insertId(),
				] );

				$this->db->insert( 'wbt_term_in_lang', [
					'wbtl_type_id' => self::MOCK_TYPE_LABEL,
					'wbtl_text_in_lang_id' => $this->db->insertId(),
				] );

				return $this->db->insertId();
			},
			array_keys( self::MOCK_LANG_LABELS ),
			self::MOCK_LANG_LABELS
		);
	}

	public function testReturnsWorkingResolver(): void {
		$expectedTerms = array_map( function ( string $label ): array {
			return [ $label ];
		}, self::MOCK_LANG_LABELS );

		$factory = new TermInLangIdsResolverFactory(
			$this->getRepoDomainDbFactory(),
			new NullLogger(),
			MediaWikiServices::getInstance()->getMainWANObjectCache()
		);

		$entitySource = $this->createStub( DatabaseEntitySource::class );
		$entitySource->method( 'getDatabaseName' )
			->willReturn( false ); // false means local db

		$resolver = $factory->getResolverForEntitySource( $entitySource );

		$this->assertSame( [
			'label' => $expectedTerms,
		], $resolver->resolveTermInLangIds( $this->termIds ) );
	}
}
