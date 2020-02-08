<?php

namespace Wikibase\Lib\Tests\Store\Sql\Terms;

use MediaWikiIntegrationTestCase;
use Wikibase\Lib\Store\Sql\Terms\CleanTermsIfUnusedJob;
use Wikibase\Lib\Store\Sql\Terms\TermStoreCleaner;

/**
 * @group Wikibase
 * @license GPL-2.0-or-later
 */
class CleanTermsIfUnusedJobTest extends MediaWikiIntegrationTestCase {

	private $termInLangId;
	private $params;

	public function setUp(): void {
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
			->with( $this->equalTo( [ $this->termInLangId ] ) );

		$job->run();
	}

}
