<?php

namespace Wikibase\Repo\Api;

use ApiQuery;
use ApiQueryBase;
use ApiResult;
use Wikibase\Lib\SettingsArray;

/**
 * @license GPL-2.0-or-later
 */
class MetaDataBridgeConfig extends ApiQueryBase {

	/** @var SettingsArray */
	private $repoSettings;

	/** @var callable */
	private $resolveTitleStringToUrl;

	/**
	 * @param SettingsArray $repoSettings
	 * @param ApiQuery $queryModule
	 * @param string $moduleName
	 * @param callable $resolveTitleStringToUrl a callable that turns a pagename into a Url
	 */
	public function __construct(
		SettingsArray $repoSettings,
		ApiQuery $queryModule,
		string $moduleName,
		callable $resolveTitleStringToUrl
	) {
		parent::__construct( $queryModule, $moduleName, 'wbdbc' );
		$this->repoSettings = $repoSettings;

		/**
		 * Todo: Replace this callback when the {@see \Title} class is in better shape
		 */
		$this->resolveTitleStringToUrl = $resolveTitleStringToUrl;
	}

	public function isInternal() {
		return true;
	}

	public function execute() {
		$result = $this->getResult();
		$path = [
			$this->getQuery()->getModuleName(),
			$this->getModuleName(),
		];

		$this->addStringMaxLengthToResult( $result, $path );
		$this->addLicenseInfoToResult( $result, $path );
	}

	private function addLicenseInfoToResult( ApiResult $result, $path ) {
		$result->addValue( $path, 'dataRightsUrl', $this->repoSettings->getSetting( 'dataRightsUrl' ) );
		$result->addValue( $path, 'dataRightsText', $this->repoSettings->getSetting( 'dataRightsText' ) );
		$termsOfUseTitlePage = $this->msg( 'copyrightpage' )->inContentLanguage()->text();
		$result->addValue( $path, 'termsOfUseUrl', ( $this->resolveTitleStringToUrl )( $termsOfUseTitlePage ) );
	}

	private function addStringMaxLengthToResult( ApiResult $result, $path ) {
		$dataTypeLimitsPath = array_merge( $path, [ 'dataTypeLimits' ] );

		// adapted from WikibaseRepo.datatypes.php > VT:string > validator-factory-callback
		$stringLimits = $this->repoSettings->getSetting( 'string-limits' );
		$stringConstraints = $stringLimits['VT:string'];
		$stringLimitsPath = array_merge( $dataTypeLimitsPath, [ 'string' ] );
		$stringMaxLength = $stringConstraints['length'];
		$result->addValue( $stringLimitsPath, 'maxLength', $stringMaxLength );
	}

	public function getCacheMode( $params ) {
		return 'public';
	}

}
