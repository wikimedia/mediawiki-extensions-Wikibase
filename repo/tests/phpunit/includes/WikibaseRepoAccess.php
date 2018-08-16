<?php

namespace Wikibase\Repo\Tests;

use Wikibase\Repo\WikibaseRepo;

/**
 * @license GPL-2.0-or-later
 */
trait WikibaseRepoAccess {

	/**
	 * todo visibility
	 *
	 * @var WikibaseRepo
	 */
	protected $wikibaseRepo;

	/**
	 * @before
	 */
	public function setUpWikibaseRepo() {
		$this->wikibaseRepo = WikibaseRepo::getDefaultInstance();
	}

	/**
	 * @after
	 */
	public function tearDownWikibaseRepo() {
		$this->wikibaseRepo->getStore()->clearInMemoryCache();
	}

}
