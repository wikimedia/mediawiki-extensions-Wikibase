<?php

declare( strict_types=1 );

namespace Wikibase\Client\Maintenance;

use Maintenance;
use Onoi\MessageReporter\CallbackMessageReporter;
use Wikibase\Client\Store\Sql\UnexpectedUnconnectedPagePrimer;
use Wikibase\Client\WikibaseClient;
use Wikibase\Lib\WikibaseSettings;

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false
	? getenv( 'MW_INSTALL_PATH' )
	: __DIR__ . '/../../../..';

require_once $basePath . '/maintenance/Maintenance.php';

/**
 * Maintenance script for populating or updating the 'unexpectedUnconnectedPage' page property.
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch <mail@mariushoch.de>
 */
class PopulateUnexpectedUnconnectedPagePageProp extends Maintenance {

	public function __construct() {
		parent::__construct();

		$this->addDescription( 'Populate the "unexpectedUnconnectedPage" page property.' );

		$this->addOption( 'batch-size', "Number of rows to update per batch (1000 by default)", false, true );
		$this->addOption(
			'first-page-id',
			'First page id to process, use 1 to start with the first page. ' .
			'Use --last-page-id + 1 to continue a previous run.',
			false,
			true
		);
		$this->addOption(
			'last-page-id',
			'Page id of the last page to process.',
			false,
			true
		);
	}

	public function execute(): bool {
		if ( !WikibaseSettings::isClientEnabled() ) {
			$this->output( "You need to have WikibaseClient enabled in order to use this maintenance script!\n\n" );
			exit;
		}

		$reporter = new CallbackMessageReporter( [ $this, 'report' ] );

		$batchSize = (int)$this->getOption( 'batch-size', 1000 );
		$primer = new UnexpectedUnconnectedPagePrimer(
			WikibaseClient::getClientDomainDbFactory()->newLocalDb(),
			WikibaseClient::getNamespaceChecker(),
			$batchSize
		);
		$primer->setProgressReporter( $reporter );

		$firstPageId = $this->getOption( 'first-page-id' );
		if ( $firstPageId ) {
			$primer->setMinPageId( intval( $firstPageId ) );
		}
		$lastPageId = $this->getOption( 'last-page-id' );
		if ( $lastPageId ) {
			$primer->setMaxPageId( intval( $lastPageId ) );
		}

		$primer->setPageProps();

		return true;
	}

	/**
	 * Outputs a message via the output() method.
	 */
	public function report( string $msg ): void {
		$this->output( "$msg\n" );
	}

}

$maintClass = PopulateUnexpectedUnconnectedPagePageProp::class;
require_once RUN_MAINTENANCE_IF_MAIN;
