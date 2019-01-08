<?php

namespace Wikibase\Repo\Api;

use ApiQuery;
use ApiQueryBase;
use Language;
use Wikibase\Lib\WikibaseContentLanguages;

/**
 * @license GPL-2.0-or-later
 */
class MetaContentLanguages extends ApiQueryBase {

	/**
	 * @var WikibaseContentLanguages
	 */
	private $wikibaseContentLanguages;

	/**
	 * @var bool
	 */
	private $expectKnownLanguageNames;

	/**
	 * @param WikibaseContentLanguages $wikibaseContentLanguages
	 * @param bool $expectKnownLanguageNames whether we should expect MediaWiki
	 * to know a language name for any language code that occurs in the content languages
	 * (if true, warnings will be raised for any language without known language name)
	 * @param ApiQuery $queryModule
	 * @param string $moduleName
	 */
	public function __construct(
		WikibaseContentLanguages $wikibaseContentLanguages,
		$expectKnownLanguageNames,
		ApiQuery $queryModule,
		$moduleName
	) {
		parent::__construct( $queryModule, $moduleName, 'wbcl' );
		$this->wikibaseContentLanguages = $wikibaseContentLanguages;
		$this->expectKnownLanguageNames = $expectKnownLanguageNames;
	}

	/**
	 * @see ApiBase::execute
	 */
	public function execute() {
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
				$autonym = Language::fetchLanguageName( $languageCode );
				if ( $autonym === '' ) {
					$autonym = null;
				}
				$result->addValue( $path, 'autonym', $autonym );
			}

			if ( $includeName ) {
				$userLanguageCode = $this->getLanguage()->getCode();
				$name = Language::fetchLanguageName( $languageCode, $userLanguageCode );
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
	public function getCacheMode( $params ) {
		return 'public';
	}

	/**
	 * @see ApiBase::getAllowedParams
	 */
	protected function getAllowedParams() {
		return [
			'context' => [
				self::PARAM_DFLT => 'term',
				self::PARAM_TYPE => $this->wikibaseContentLanguages->getContexts(),
				self::PARAM_HELP_MSG_PER_VALUE => [],
			],
			'prop' => [
				self::PARAM_DFLT => 'code',
				self::PARAM_ISMULTI => true,
				self::PARAM_TYPE => [
					'code',
					'autonym',
					'name',
				],
				self::PARAM_HELP_MSG_PER_VALUE => [],
			]
		];
	}

	/**
	 * @see ApiBase::getExamplesMessages()
	 */
	protected function getExamplesMessages() {
		$pathUrl = 'action=' . $this->getQuery()->getModuleName() .
			'&meta=' . $this->getModuleName();
		$pathMsg = $this->getModulePath();

		return [
			"$pathUrl" => "apihelp-$pathMsg-example-1",
			"$pathUrl&wbclcontext=monolingualtext&wbclprop=code|autonym" => "apihelp-$pathMsg-example-2",
		];
	}

}
