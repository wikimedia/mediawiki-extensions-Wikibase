<?php

namespace Wikibase\Lib\Tests\Store\Sql\Terms;

use MediaWikiIntegrationTestCase;
use Wikibase\Lib\Store\Sql\Terms\CleanTermsIfUnusedJob;
use Wikibase\Lib\Store\Sql\Terms\TermStoreCleaner;
use Wikibase\Lib\WikibaseSettings;

/**
 * @group Wikibase
 * @license GPL-2.0-or-later
 * @covers \Wikibase\Lib\Store\Sql\Terms\CleanTermsIfUnusedJob
 */
class CleanTermsIfUnusedJobTest extends MediaWikiIntegrationTestCase {

	private $termInLangId;
	private $params;

	protected function setUp(): void {
		if ( !WikibaseSettings::isRepoEnabled() ) {
			$this->markTestSkipped( "Skipping because WikibaseClient doesn't have local term store tables." );
		}
		parent::setUp();
		$this->termInLangId = 'cat_terminlang';
		$this->params = [ CleanTermsIfUnusedJob::TERM_IN_LANG_IDS => [ $this->termInLangId ] ];
	}

	public function testMakeJobSpecification() {
		$job = CleanTermsIfUnusedJob::getJobSpecificationNoTitle( $this->params );
		$this->assertInstanceOf( CleanTermsIfUnusedJob::class, $job );
		$this->assertEquals( [ $this->termInLangId ], $job->getParams()[ CleanTermsIfUnusedJob::TERM_IN_LANG_IDS ] );
	}

	public function testRunCallsCleanerWith_cleanUnusedTermInLangIds() {
		$cleaner = $this->createMock( TermStoreCleaner::class );
		$job = new CleanTermsIfUnusedJob( $cleaner, $this->params );
		$cleaner->expects( $this->once() )
			->method( 'cleanTermInLangIds' )
			->with( [ $this->termInLangId ] );

		$job->run();
	}

}
