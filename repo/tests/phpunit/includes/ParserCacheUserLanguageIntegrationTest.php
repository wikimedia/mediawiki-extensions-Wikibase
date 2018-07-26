<?php

namespace Wikibase\Repo\Tests;

use MediaWikiTestCase;
use SpecialPageExecutor;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Repo\Specials\SpecialEntityPage;
use Wikibase\Repo\Tests\Specials\SpecialEntityPageTest;
use Wikibase\Repo\WikibaseRepo;

class ParserCacheUserLanguageIntegrationTest extends MediaWikiTestCase {

	public function testFoo() {
		$this->setMwGlobals( 'wgParserCacheType', CACHE_ACCEL );
		$this->setMwGlobals( 'wgMainCacheType', CACHE_ACCEL );

		$nonEngUser = $this->getTestUser()->getUser();
		$nonEngUser->setOption( 'language', 'de' );

		$item = new Item( null, new Fingerprint( new TermList( [ new Term( 'en', 'foo' ) ]  ) ) );
		$revision = WikibaseRepo::getDefaultInstance()->getEntityStore()->saveEntity( $item, __METHOD__, $nonEngUser, EDIT_NEW );

		//$id = $revision->getEntity()->getId();

		$generator = WikibaseRepo::getDefaultInstance()->getEntityParserOutputGeneratorFactory()->getEntityParserOutputGenerator( 'en' );
		$output = $generator->getParserOutput( $item, $generateHtml = true );

		$this->assertEquals( 'foo', $output->mText );
	}

}