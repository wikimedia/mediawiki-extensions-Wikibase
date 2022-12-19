<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Api;

use ApiQuery;
use ApiQueryBase;
use ExtensionRegistry;
use MediaWiki\Languages\LanguageNameUtils;
use Wikibase\Lib\WikibaseContentLanguages;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * @license GPL-2.0-or-later
 */
class MetaContentLanguages extends ApiQueryBase {

	/** @var WikibaseContentLanguages */
	private $wikibaseContentLanguages;

	/** @var bool */
	private $expectKnownLanguageNames;

	/** @var LanguageNameUtils */
	private $languageNameUtils;

	/**
	 * @param WikibaseContentLanguages $wikibaseContentLanguages
	 * @param bool $expectKnownLanguageNames whether we should expect MediaWiki
	 * to know a language name for any language code that occurs in the content languages
	 * (if true, warnings will be raised for any language without known language name)
	 * @param LanguageNameUtils $languageNameUtils source of language names and autonyms
	 * @param ApiQuery $queryModule
	 * @param string $moduleName
	 */
	public function __construct(
		WikibaseContentLanguages $wikibaseContentLanguages,
		bool $expectKnownLanguageNames,
		LanguageNameUtils $languageNameUtils,
		ApiQuery $queryModule,
		string $moduleName
	) {
		parent::__construct( $queryModule, $moduleName, 'wbcl' );
		$this->wikibaseContentLanguages = $wikibaseContentLanguages;
		$this->expectKnownLanguageNames = $expectKnownLanguageNames;
		$this->languageNameUtils = $languageNameUtils;
	}

	public static function factory(
		ApiQuery $apiQuery,
		string $moduleName,
		LanguageNameUtils $languageNameUtils,
		WikibaseContentLanguages $wikibaseContentLanguages
	): self {
		// if CLDR is available, we expect to have some language name
		// (falling back to English if necessary) for any content language
		$expectKnownLanguageNames = ExtensionRegistry::getInstance()->isLoaded( 'cldr' );

		return new self(
			$wikibaseContentLanguages,
			$expectKnownLanguageNames,
			$languageNameUtils,
			$apiQuery,
			$moduleName
		);
	}

	/**
	 * @inheritDoc
	 */
	public function execute(): void {
		$params = $this->extractRequestParams();
		$result = $this->getResult();

		$context = $params['context'];
		$contentLanguages = $this->wikibaseContentLanguages->getContentLanguages( $context );
		$languageCodes = $contentLanguages->getLanguages();

		$props = $params['prop'];
		$includeCode = in_array( 'code', $props );
		$includeAutonym = in_array( 'autonym', $props );
		$includeName = in_array( 'name', $props );

		foreach ( $languageCodes as $languageCode ) {
			$path = [
				$this->getQuery()->getModuleName(),
				$this->getModuleName(),
				$languageCode,
			];

			if ( $includeCode ) {
				$result->addValue( $path, 'code', $languageCode );
			}

			if ( $includeAutonym ) {
				$autonym = $this->languageNameUtils->getLanguageName( $languageCode );
				if ( $autonym === '' ) {
					$autonym = null;
				}
				$result->addValue( $path, 'autonym', $autonym );
			}

			if ( $includeName ) {
				$userLanguageCode = $this->getLanguage()->getCode();
				$name = $this->languageNameUtils->getLanguageName( $languageCode, $userLanguageCode );
				if ( $name === '' ) {
					if ( $this->expectKnownLanguageNames ) {
						wfLogWarning( 'No name known for language ' . $languageCode );
					}
					$name = null;
				}
				$result->addValue( $path, 'name', $name );
			}
		}
	}

	/**
	 * @see ApiQueryBase::getCacheMode
	 *
	 * @param array $params
	 * @return string
	 */
	public function getCacheMode( $params ): string {
		return 'public';
	}

	/**
	 * @inheritDoc
	 */
	protected function getAllowedParams(): array {
		return [
			'context' => [
				ParamValidator::PARAM_DEFAULT => 'term',
				ParamValidator::PARAM_TYPE => $this->wikibaseContentLanguages->getContexts(),
				self::PARAM_HELP_MSG_PER_VALUE => [],
			],
			'prop' => [
				ParamValidator::PARAM_DEFAULT => 'code',
				ParamValidator::PARAM_ISMULTI => true,
				ParamValidator::PARAM_TYPE => [
					'code',
					'autonym',
					'name',
				],
				self::PARAM_HELP_MSG_PER_VALUE => [],
			],
		];
	}

	/**
	 * @inheritDoc
	 */
	protected function getExamplesMessages(): array {
		$pathUrl = 'action=' . $this->getQuery()->getModuleName() .
			'&meta=' . $this->getModuleName();
		$pathMsg = $this->getModulePath();

		return [
			"$pathUrl" => "apihelp-$pathMsg-example-1",
			"$pathUrl&wbclcontext=monolingualtext&wbclprop=code|autonym" => "apihelp-$pathMsg-example-2",
		];
	}

}
