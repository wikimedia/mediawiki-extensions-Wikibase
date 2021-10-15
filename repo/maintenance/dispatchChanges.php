<?php

namespace Wikibase\Repo\Maintenance;

use ExtensionRegistry;
use Maintenance;
use MWException;
use OutOfBoundsException;
use Wikibase\Repo\WikibaseRepo;

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../../..';

require_once $basePath . '/maintenance/Maintenance.php';

/**
 * @license GPL-2.0-or-later
 */
class DispatchChanges extends Maintenance {

	public function __construct() {
		parent::__construct();

		$this->addDescription(
			'Deprecated maintenance script that no longer does anything. Do not use.'
		);

		$this->addOption( 'verbose', 'Unused.' );
		$this->addOption( 'idle-delay', 'Unused.', false, true );
		$this->addOption( 'dispatch-interval', 'Unused.', false, true );
		$this->addOption( 'randomness', 'Unused.', false, true );
		$this->addOption( 'max-passes', 'Unused.', false, true );
		$this->addOption( 'max-time', 'Unused.', false, true );
		$this->addOption( 'max-chunks', 'Unused.', false, true );
		$this->addOption( 'batch-size', 'Unused.', false, true );
		$this->addOption( 'client', 'Unused', false, true, false, true );
	}

	public function execute() {
		$this->error(
			"This maintenance script no longer does anything. Please stop running it.\n" .
			'Sleeping to prevent busy restart loops...'
		);

		if ( !ExtensionRegistry::getInstance()->isLoaded( 'WikibaseRepository' ) ) {
			// Since people might waste time debugging odd errors when they forget to enable the extension. BTDT.
			throw new MWException( 'WikibaseRepository has not been loaded.' );
		}

		$defaultMaxTime = $this->getSettingOrDefault( 'dispatchMaxTime', 60 * 60 );
		if ( $defaultMaxTime == 0 ) {
			return;
		}

		$maxTime = (int)$this->getOption( 'max-time', $defaultMaxTime );
		$maxPasses = (int)$this->getOption( 'max-passes', $maxTime < PHP_INT_MAX ? PHP_INT_MAX : 1 );
		$delay = (int)$this->getOption( 'idle-delay', $this->getSettingOrDefault( 'dispatchIdleDelay', 10 ) );

		$startTime = microtime( true );
		$t = 0;

		for ( $c = 0; $c < $maxPasses; ) {
			if ( $t > $maxTime ) {
				break;
			}
			$c++;

			if ( $c < $maxPasses ) {
				sleep( $delay );
			}

			$t = microtime( true ) - $startTime;
		}

		$this->fatalError( '', 3 );
	}

	private function getSettingOrDefault( string $setting, $default ) {
		try {
			return WikibaseRepo::getSettings()->getSetting( $setting );
		} catch ( OutOfBoundsException $e ) {
			return $default;
		}
	}

}

$maintClass = DispatchChanges::class;
require_once RUN_MAINTENANCE_IF_MAIN;
