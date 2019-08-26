<?php

namespace Wikibase\Repo\Hooks\Helpers;

use User;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Lib\UserLanguageLookup;

/**
 * @license GPL-2.0-or-later
 */
class UserPreferredContentLanguagesLookup {

	/**
	 * @var ContentLanguages
	 */
	private $contentLanguages;

	/**
	 * @var UserLanguageLookup
	 */
	private $userLanguageLookup;

	/**
	 * @var string
	 */
	private $wikiDefaultContentLanguage;

	public function __construct(
		ContentLanguages $contentLanguages,
		UserLanguageLookup $userLanguageLookup,
		$wikiDefaultContentLanguage
	) {
		$this->userLanguageLookup = $userLanguageLookup;
		$this->contentLanguages = $contentLanguages;
		$this->wikiDefaultContentLanguage = $wikiDefaultContentLanguage;
	}

	public function getLanguages( $language, User $user ) {
		$validLanguages = array_filter(
			array_unique( array_merge(
				[ $language ],
				$this->userLanguageLookup->getAllUserLanguages( $user )
			) ),
			function ( $language ) {
				return $this->contentLanguages->hasLanguage( $language );
			}
		);
		return count( $validLanguages ) === 0 ? [ $this->wikiDefaultContentLanguage ] : $validLanguages;
	}

}
