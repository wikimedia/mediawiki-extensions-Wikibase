<?php

namespace Wikibase\Repo;

use MediaWiki\Language\Language;
use MediaWiki\Message\Message;
use MediaWiki\Output\OutputPage;
use Wikimedia\Assert\Assert;

/**
 * Handles adding user-specific or other js config to OutputPage
 *
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class OutputPageJsConfigBuilder {

	/**
	 * @var CopyrightMessageBuilder
	 */
	private $copyrightMessageBuilder;

	public function __construct() {
		$this->copyrightMessageBuilder = new CopyrightMessageBuilder();
	}

	/**
	 * @param OutputPage $out
	 * @param string $rightsUrl
	 * @param string $rightsText
	 * @param string[] $badgeItems
	 * @param int $stringLimit
	 * @param bool $taintedReferencesEnabled
	 * @param bool $mobileEditing
	 *
	 * @return array
	 */
	public function build(
		OutputPage $out,
		string $rightsUrl,
		string $rightsText,
		array $badgeItems,
		int $stringLimit,
		bool $taintedReferencesEnabled,
		bool $mobileEditing
	): array {
		$lang = $out->getLanguage();
		$title = $out->getTitle();

		Assert::parameter( $title !== null, '$out', 'Passed OutputPage needs to have a Title set' );

		$configVars = $this->getCopyrightConfig( $rightsUrl, $rightsText, $lang, $mobileEditing );

		$configVars['wbBadgeItems'] = $badgeItems;
		$configVars['wbMultiLingualStringLimit'] = $stringLimit;
		$configVars['wbTaintedReferencesEnabled'] = $taintedReferencesEnabled;

		return $configVars;
	}

	/**
	 * @param string $rightsUrl
	 * @param string $rightsText
	 * @param Language $language
	 * @param bool $mobileEditing
	 *
	 * @return array
	 */
	private function getCopyrightConfig(
		string $rightsUrl,
		string $rightsText,
		Language $language,
		bool $mobileEditing
	): array {
		$messageHtml = $this->copyrightMessageBuilder
			->build( $rightsUrl, $rightsText, $language, null, $mobileEditing );
		$versionMessage = new Message( 'wikibase-shortcopyrightwarning-version' );

		return [
			'wbCopyright' => [
				'version' => $versionMessage->parse(),
				'messageHtml' => $messageHtml->inLanguage( $language )->parse(),
			],
		];
	}
}
