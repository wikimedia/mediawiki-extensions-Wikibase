<?php

namespace Wikibase\Repo\Api;

use ApiBase;
use ApiMain;
use ApiResult;
use Wikibase\Lib\SettingsArray;

/**
 * API module to query available badge items.
 *
 * @license GPL-2.0-or-later
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class AvailableBadges extends ApiBase {

	/** @var SettingsArray */
	private $repoSettings;

	public function __construct(
		ApiMain $mainModule,
		string $moduleName,
		SettingsArray $repoSettings
	) {
		parent::__construct( $mainModule, $moduleName );
		$this->repoSettings = $repoSettings;
	}

	/**
	 * @inheritDoc
	 */
	public function execute() {
		$this->getMain()->setCacheMode( 'public' );
		$this->getMain()->setCacheMaxAge( 3600 );

		$badgeItems = $this->repoSettings->getSetting( 'badgeItems' );
		$idStrings = array_keys( $badgeItems );
		ApiResult::setIndexedTagName( $idStrings, 'badge' );
		$this->getResult()->addValue(
			null,
			'badges',
			$idStrings
		);
	}

	/**
	 * @inheritDoc
	 */
	protected function getExamplesMessages() {
		return [
			'action=wbavailablebadges' =>
				'apihelp-wbavailablebadges-example-1',
		];
	}

}
