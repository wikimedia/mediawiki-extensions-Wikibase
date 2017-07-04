<?php

namespace Wikibase;

use Maintenance;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\WikibaseRepo;

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../../..';

require_once $basePath . '/maintenance/Maintenance.php';

/**
 * Maintenance script for creating blacklisted items.
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class CreateBlacklistedItems extends Maintenance {

	public function __construct() {
		$this->addDescription( 'Created blacklisted items' );

		parent::__construct();
	}

	public function execute() {
		global $wgUser;

		$user = $wgUser;
		$store = WikibaseRepo::getDefaultInstance()->getEntityStore();

		if ( !WikibaseSettings::isRepoEnabled() ) {
			$this->output( "You need to have Wikibase enabled in order to use this maintenance script!\n\n" );
			exit;
		}

		$report = function( $message ) {
			echo $message . "\n";
		};

		$items = [
			//'Q0' => 'Off-by-one error',
			'Q1' => 'Universe',
			'Q2' => 'Earth',
			'Q3' => 'Life',
			'Q4' => 'Death',
			'Q5' => 'Human',
			'Q8' => 'Happiness',
			'Q13' => 'Triskaidekaphobia',
			'Q23' => 'George Washington',
			'Q24' => 'Jack Bauer',
			'Q42' => 'Douglas Adams',
			'Q80' => 'Tim Berners-Lee',
			'Q666' => 'Number of the Beast',
			'Q1337' => 'Leet',
			'Q1868' => 'Paul Otlet',
			'Q1971' => 'Imagine (song)',
			'Q2001' => 'Stanley Kubrick',
			'Q2012' => 'Maya calendar',
			'Q2013' => 'Wikidata',
		];

		$report( 'Starting import...' );

		foreach ( $items as $id => $name ) {
			$report( "   Importing $name as item $id..." );

			$item = new Item( new ItemId( $id ) );
			$item->setLabel( 'en', $name );
			$item->getSiteLinkList()->addNewSiteLink( 'enwiki', $name );

			$store->saveEntity( $item, 'Import', $user, EDIT_NEW );
		}

		$report( 'Import completed.' );
	}

}

$maintClass = CreateBlacklistedItems::class;
require_once RUN_MAINTENANCE_IF_MAIN;
