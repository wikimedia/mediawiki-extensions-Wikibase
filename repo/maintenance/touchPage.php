<?php

namespace Wikibase;
use Maintenance;
use Revision;
use Title;
use User;
use WikiPage;

$IP = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../../..';

// Require base maintenance class
require_once( "$IP/maintenance/Maintenance.php" );

class TouchPage extends Maintenance {

	public function __construct() {
		parent::__construct();
		$this->addOption( 'invalidate', 'Invalidate the parser cache' );
		$this->addOption( 'nulledit', 'Re-parse the page by a null edit' );
		//$this->addOption( 'parselater', 'Queue RefreshLinksJob2 jobs for the page' );
		$this->addOption( 'dataupdates', 'Apply data updates' );
		$this->addArg( 'pageids', 'Page Ids to update, separated by commas' );
	}

	public function execute() {
		$ids = explode( ',', $this->getArg() );
		$titles = Title::newFromIDs( $ids );
		foreach ( $titles as $title ) {
			if ( $this->hasOption( 'invalidate' ) ) {
				$this->doInvalidate( $title );
			}
			if ( $this->hasOption( 'nulledit' ) ) {
				$this->doNullEdit( $title );
			}
			if ( $this->hasOption( 'parselater' ) ) {
				$this->doParseLater( $title );
			}
			if ( $this->hasOption( 'dataupdates' ) ) {
				$this->doDataUpdates( $title );
			}
		}
	}

	private function doInvalidate( Title $title ) {
		$title->invalidateCache();
		$this->output( "Invalidated cache of {$title->getPrefixedText()}\n" );
	}

	private function doNullEdit( Title $title ) {
		$rev = Revision::newFromTitle( $title );
		$content = $rev->getContent();
		$page = WikiPage::factory( $title );
		$page->doEditContent(
			$content,
			'Null-edit',
			EDIT_SUPPRESS_RC | EDIT_UPDATE,
			$rev->getId(),
			User::newFromName( 'MediaWiki default' )
		);
		$this->output( "Null-edited of {$title->getPrefixedText()}\n" );
	}

	private function doParseLater( Title $title ) {
		// not sure what to do here.
		//$this->output( "Queued jobs for {$title->getPrefixedText()}\n" );
	}

	private function doDataUpdates( Title $title ) {
		$rev = Revision::newFromTitle( $title );
		$content = $rev->getContent();
		foreach ( $content->getSecondaryDataUpdates( $title ) as $upd ) {
			$upd->doUpdate();
		}
		$this->output( "Ran data updates for {$title->getPrefixedText()}\n" );
	}
}

$maintClass = 'Wikibase\TouchPage';
require_once( RUN_MAINTENANCE_IF_MAIN );
