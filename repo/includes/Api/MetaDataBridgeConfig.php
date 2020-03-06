<?php

namespace Wikibase\Repo\Api;

use ApiQuery;
use ApiQueryBase;
use Wikibase\Lib\SettingsArray;
use ApiResult;

/**
 * @license GPL-2.0-or-later
 */
class MetaDataBridgeConfig extends ApiQueryBase {

	/** @var SettingsArray */
	private $repoSettings;

	public function __construct(
		SettingsArray $repoSettings,
		ApiQuery $queryModule,
		$moduleName
	) {
		parent::__construct( $queryModule, $moduleName, 'wbdbc' );
		$this->repoSettings = $repoSettings;
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
