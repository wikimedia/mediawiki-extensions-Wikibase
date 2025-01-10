<?php

declare( strict_types = 1 );

namespace Wikibase\Lib\Store\Sql\Terms;

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

		$this->getDb()->newInsertQueryBuilder()
			->insertInto( 'wbt_type' )
			->row( [
				'wby_id' => self::MOCK_TYPE_LABEL,
				'wby_name' => 'label',
			] )
			->caller( __METHOD__ )
			->execute();

		$fname = __METHOD__;
		$this->termIds = array_map(
			function ( string $lang, string $label ) use ( $fname ): int {
				$this->getDb()->newInsertQueryBuilder()
					->insertInto( 'wbt_text' )
					->row( [
						'wbx_text' => $label,
					] )
					->caller( $fname )
					->execute();

				$this->getDb()->newInsertQueryBuilder()
					->insertInto( 'wbt_text_in_lang' )
					->row( [
						'wbxl_language' => $lang,
						'wbxl_text_id' => $this->getDb()->insertId(),
					] )
					->caller( $fname )
					->execute();

				$this->getDb()->newInsertQueryBuilder()
					->insertInto( 'wbt_term_in_lang' )
					->row( [
						'wbtl_type_id' => self::MOCK_TYPE_LABEL,
						'wbtl_text_in_lang_id' => $this->getDb()->insertId(),
					] )
					->caller( $fname )
					->execute();

				return $this->getDb()->insertId();
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
			$this->getTermsDomainDbFactory(),
			new NullLogger()
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
