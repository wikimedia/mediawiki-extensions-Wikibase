<?php

namespace Wikibase\Lib\Tests;

use MediaWikiIntegrationTestCase;
use OutOfRangeException;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Lib\StaticContentLanguages;
use Wikibase\Lib\WikibaseContentLanguages;

/**
 * @covers \Wikibase\Lib\WikibaseContentLanguages
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class WikibaseContentLanguagesTest extends MediaWikiIntegrationTestCase {

	public function testGetContentLanguages() {
		$test1Languages = new StaticContentLanguages( [ 'test1' ] );
		$test2Languages = new StaticContentLanguages( [ 'test2' ] );
		$wcl = new WikibaseContentLanguages( [
			'test1' => $test1Languages,
			'test2' => $test2Languages,
		] );

		$this->assertSame( $test1Languages, $wcl->getContentLanguages( 'test1' ) );
		$this->assertSame( $test2Languages, $wcl->getContentLanguages( 'test2' ) );
	}

	public function testGetContentLanguages_unknownContext() {
		$test1Languages = new StaticContentLanguages( [ 'test1' ] );
		$wcl = new WikibaseContentLanguages( [ 'test1' => $test1Languages ] );

		$this->expectException( OutOfRangeException::class );
		$wcl->getContentLanguages( 'test2' );
	}

	public function testGetDefaultInstance_defaultContexts() {
		$wcl = WikibaseContentLanguages::getDefaultInstance();

		$termLanguages = $wcl->getContentLanguages( WikibaseContentLanguages::CONTEXT_TERM );
		$monolingualTextLanguages = $wcl->getContentLanguages( WikibaseContentLanguages::CONTEXT_MONOLINGUAL_TEXT );

		$this->assertInstanceOf( ContentLanguages::class, $termLanguages );
		$this->assertInstanceOf( ContentLanguages::class, $monolingualTextLanguages );
	}

	public function testGetDefaultInstance_withHook() {
		$testLanguages = new StaticContentLanguages( [ 'test' ] );
		$this->setTemporaryHook(
			'WikibaseContentLanguages',
			function ( array &$contentLanguages ) use ( $testLanguages ) {
				$contentLanguages['test'] = $testLanguages;
			}
		);

		$wcl = WikibaseContentLanguages::getDefaultInstance();
		$this->assertSame( $testLanguages, $wcl->getContentLanguages( 'test' ) );
	}

}
