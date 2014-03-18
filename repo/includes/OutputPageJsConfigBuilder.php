<?php

namespace Wikibase;

use Language;
use Message;
use OutputPage;
use Title;
use User;

/**
 * Handles adding user-specific or other js config to OutputPage
 *
 * @since 0.5
 * @licence GNU GPL v2+
 *
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class OutputPageJsConfigBuilder {

	/**
	 * @var CopyrightMessageBuilder
	 */
	protected $copyrightMessageBuilder;

	public function __construct() {
		$this->copyrightMessageBuilder = new CopyrightMessageBuilder();
	}

	/**
	 * @param OutputPage $out
	 * @param string $rightsUrl
	 * @param string $rightsText
	 * @param boolean $isExperimental
	 *
	 * @return array
	 */
	public function build( OutputPage $out, $rightsUrl, $rightsText, $isExperimental ) {
		$user = $out->getUser();
		$lang = $out->getLanguage();
		$title = $out->getTitle();

		$userConfigVars = $this->getUserConfigVars( $title, $user );

		$copyrightConfig = $this->getCopyrightConfig( $rightsUrl, $rightsText, $lang );

		$configVars = array_merge( $userConfigVars, $copyrightConfig );

		$configVars['wbExperimentalFeatures'] = $isExperimental;

		return $configVars;
	}

	/**
	 * @param Title $title
	 * @param User $user
	 *
	 * @return array
	 */
	private function getUserConfigVars( Title $title, User $user ) {
		$configVars = array();

		// TODO: replace wbUserIsBlocked this with more useful info (which groups would be
		// required to edit? compare wgRestrictionEdit and wgRestrictionCreate)
		$configVars['wbUserIsBlocked'] = $user->isBlockedFrom( $title ); //NOTE: deprecated

		// tell JS whether the user can edit
		// TODO: make this a per-entity info
		$configVars['wbUserCanEdit'] = $title->userCan( 'edit', $user, false );

		return $configVars;
	}

	/**
	 * @param string $rightsUrl
	 * @param string $rightsText
	 * @param Language $language
	 *
	 * @return array
	 */
	private function getCopyrightConfig( $rightsUrl, $rightsText, Language $language ) {
		$copyrightMessage = $this->getCopyrightMessage( $rightsUrl, $rightsText, $language );

		return $this->getCopyrightVar( $copyrightMessage, $language );
	}

	/**
	 * @param CopyrightMessage $copyrightMessage
	 * @param string $langCode
	 *
	 * @param Message $copyrightMessage
	 */
	private function getCopyrightVar( $copyrightMessage, $language ) {
		// non-translated message
		$versionMessage = new Message( 'wikibase-shortcopyrightwarning-version' );

		return array(
			'wbCopyright' => array(
				'version' => $versionMessage->parse(),
				'messageHtml' => $copyrightMessage->inLanguage( $language )->parse()
			)
		);
	}

	/**
	 * @param string $rightsUrl
	 * @param string $rightsText
	 * @param Language $language
	 *
	 * @return Message
	 */
	private function getCopyrightMessage( $rightsUrl, $rightsText, Language $language ) {
		$copyrightMessage = $this->copyrightMessageBuilder->build(
			$rightsUrl,
			$rightsText,
			$language
		);

		return $copyrightMessage;
	}

}
