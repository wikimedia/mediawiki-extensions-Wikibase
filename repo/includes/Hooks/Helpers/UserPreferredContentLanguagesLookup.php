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

	/**
	 * @param $uiLanguage - user interface language; will be returned as the first language in the list if valid
	 * @param User $user
	 *
	 * @return array language codes
	 */
	public function getLanguages( $uiLanguage, User $user ) {
		$validLanguages = array_filter(
			array_unique( array_merge(
				[ $uiLanguage ],
				$this->userLanguageLookup->getAllUserLanguages( $user )
			) ),
			[ $this->contentLanguages, 'hasLanguage' ]
		);
		return count( $validLanguages ) === 0 ?
			[ $this->wikiDefaultContentLanguage ] :
			array_values( $validLanguages );
	}

}
