<?php

namespace Wikibase;

use Maintenance;
use MediaWiki\MediaWikiServices;
use Onoi\MessageReporter\CallbackMessageReporter;
use Onoi\MessageReporter\MessageReporter;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\Store\ItemTermsRebuilder;
use Wikibase\Repo\WikibaseRepo;

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../../..';

require_once $basePath . '/maintenance/Maintenance.php';

/**
 * @license GPL-2.0-or-later
 */
class RebuildItemTerms extends Maintenance {

	/**
	 * @var WikibaseRepo
	 */
	private $wikibaseRepo;

	public function __construct() {
		parent::__construct();

		$this->addDescription( 'Rebuilds item terms from primary persistence' );

		$this->addOption(
			'from-id',
			"Lowest (numeric) item id that should be updated (Default: 1)",
			false,
			true
		);

		$this->addOption(
			'to-id',
			"Highest (numeric) item id that should be updated (Default: no limit, manual script termination required)",
			false,
			true
		);

		$this->addOption(
			'batch-size',
			"Number of rows to update per batch (Default: 250)",
			false,
			true
		);

		$this->addOption(
			'sleep',
			"Sleep time (in seconds) between every batch (Default: 10)",
			false,
			true
		);
	}

	public function execute() {
		if ( !defined( 'WB_VERSION' ) ) {
			$this->error(
				"You need to have Wikibase enabled in order to use this "
				. "maintenance script!\n\n",
				1
			);
		}

		$this->wikibaseRepo = WikibaseRepo::getDefaultInstance();

		$rebuilder = new ItemTermsRebuilder(
			$this->wikibaseRepo->getItemTermStore(),
			$this->newItemIdIterator(),
			$this->getReporter(),
			$this->getErrorReporter(),
			MediaWikiServices::getInstance()->getDBLoadBalancerFactory(),
			$this->wikibaseRepo->getItemLookup( Store::LOOKUP_CACHING_RETRIEVE_ONLY ),
			(int)$this->getOption( 'batch-size', 250 ),
			(int)$this->getOption( 'sleep', 10 )
		);

		$rebuilder->rebuild();

		$this->output( "Done.\n" );
	}

	private function newItemIdIterator(): \Iterator {
		$fromId = (int)$this->getOption( 'from-id', 1 );
		$toId = (int)$this->getOption( 'to-id', 0 );

		for ( $id = $fromId; $id <= $toId; $id++ ) {
			yield ItemId::newFromNumber( $id );
		}
	}

	private function getReporter(): MessageReporter {
		return new CallbackMessageReporter(
			function ( $message ) {
				$this->output( "$message\n" );
			}
		);
	}

	private function getErrorReporter(): MessageReporter {
		return new CallbackMessageReporter(
			function ( $message ) {
				$this->error( "[ERROR] $message" );
			}
		);
	}

}

$maintClass = RebuildItemTerms::class;
require_once RUN_MAINTENANCE_IF_MAIN;
