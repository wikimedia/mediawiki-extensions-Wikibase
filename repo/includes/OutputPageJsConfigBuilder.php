<?php

namespace Wikibase;

use Language;
use Message;
use OutputPage;
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
	 * @var EntityTitleLookup
	 */
	private $entityTitleLookup;

	public function __construct( EntityTitleLookup $entityTitleLookup ) {
		$this->entityTitleLookup = $entityTitleLookup;
	}

	/**
	 * @param OutputPage $out
	 * @param EntityId $entityId
	 * @param string $rightsUrl
	 * @param string $rightsText
	 */
	public function build( User $user, Language $lang, EntityId $entityId, $rightsUrl, $rightsText ) {
		$userConfigVars = $this->getUserConfigVars( $entityId, $user );

		$copyrightConfig = $this->getCopyrightConfig( $rightsUrl, $rightsText, $lang );

		$configVars = array_merge( $userConfigVars, $copyrightConfig );

		return $configVars;
	}

	/**
	 * @param EntityId $entityId
	 * @param User $user
	 *
	 * @return array
	 */
	public function getUserConfigVars( EntityId $entityId, User $user ) {
		$configVars = array();

		$title = $this->entityTitleLookup->getTitleForId( $entityId );

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
	public function getCopyrightConfig( $rightsUrl, $rightsText, Language $language ) {
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
		$copyrightMessageBuilder = new CopyrightMessageBuilder();
		$copyrightMessage = $copyrightMessageBuilder->build(
			$rightsUrl,
			$rightsText,
			$language
		);

		return $copyrightMessage;
	}

}
