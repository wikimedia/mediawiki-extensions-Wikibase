<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Hooks;

use MediaWiki\Hook\MakeGlobalVariablesScriptHook;
use OutputPage;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\UserLanguageLookup;
use Wikibase\Repo\BabelUserLanguageLookup;
use Wikibase\Repo\Content\EntityContentFactory;
use Wikibase\Repo\Hooks\Helpers\OutputPageEntityViewChecker;
use Wikibase\Repo\OutputPageJsConfigBuilder;

/**
 * @license GPL-2.0-or-later
 */
class MakeGlobalVariablesScriptHookHandler implements MakeGlobalVariablesScriptHook {

	/** @var OutputPageEntityViewChecker */
	private $entityViewChecker;

	/** @var OutputPageJsConfigBuilder */
	private $outputPageJsConfigBuilder;

	/** @var ContentLanguages */
	private $termsLanguages;

	/** @var UserLanguageLookup */
	private $userLanguageLookup;

	/** @var string */
	private $dataRightsUrl;

	/** @var string */
	private $dataRightsText;

	/** @var string[] */
	private $badgeItems;

	/** @var int */
	private $stringLimit;

	/** @var bool */
	private $taintedReferencesEnabled;

	public function __construct(
		OutputPageEntityViewChecker $entityViewChecker,
		OutputPageJsConfigBuilder $outputPageJsConfigBuilder,
		ContentLanguages $termsLanguages,
		UserLanguageLookup $userLanguageLookup,
		string $dataRightsUrl,
		string $dataRightsText,
		array $badgeItems,
		int $stringLimit,
		bool $taintedReferencesEnabled
	) {
		$this->entityViewChecker = $entityViewChecker;
		$this->outputPageJsConfigBuilder = $outputPageJsConfigBuilder;
		$this->termsLanguages = $termsLanguages;
		$this->userLanguageLookup = $userLanguageLookup;
		$this->dataRightsUrl = $dataRightsUrl;
		$this->dataRightsText = $dataRightsText;
		$this->badgeItems = $badgeItems;
		$this->stringLimit = $stringLimit;
		$this->taintedReferencesEnabled = $taintedReferencesEnabled;
	}

	public static function factory(
		EntityContentFactory $entityContentFactory,
		SettingsArray $repoSettings,
		ContentLanguages $termsLanguages
	): self {
		return new self(
			new OutputPageEntityViewChecker( $entityContentFactory ),
			new OutputPageJsConfigBuilder(),
			$termsLanguages,
			new BabelUserLanguageLookup(),
			$repoSettings->getSetting( 'dataRightsUrl' ),
			$repoSettings->getSetting( 'dataRightsText' ),
			$repoSettings->getSetting( 'badgeItems' ),
			$repoSettings->getSetting( 'string-limits' )['multilang']['length'],
			$repoSettings->getSetting( 'taintedReferencesEnabled' )
		);
	}

	/**
	 * @param array $vars
	 * @param OutputPage $out
	 */
	public function onMakeGlobalVariablesScript( &$vars, $out ): void {
		if ( !$this->entityViewChecker->hasEntityView( $out ) ) {
			return;
		}

		// All user-specified languages, that are valid term languages
		// Reindex the keys so that JavaScript still works if an unknown
		// language code in the babel box causes an index to miss
		$vars['wbUserSpecifiedLanguages'] = array_values( array_intersect(
			$this->userLanguageLookup->getUserSpecifiedLanguages( $out->getUser() ),
			$this->termsLanguages->getLanguages()
		) );

		$vars += $this->outputPageJsConfigBuilder->build(
			$out,
			$this->dataRightsUrl,
			$this->dataRightsText,
			$this->badgeItems,
			$this->stringLimit,
			$this->taintedReferencesEnabled
		);
	}

}
