<?php

declare( strict_types=1 );

namespace Wikibase;

use MediaWiki\Maintenance\Maintenance;
use MediaWiki\MediaWikiServices;
use Wikibase\Lib\WikibaseSettings;

// @codeCoverageIgnoreStart
$basePath = getenv( 'MW_INSTALL_PATH' ) !== false
	? getenv( 'MW_INSTALL_PATH' )
	: __DIR__ . '/../../../..';

require_once $basePath . '/maintenance/Maintenance.php';
// @codeCoverageIgnoreEnd

/**
 * Utility for dumping entity usage directly from ParserOutput, bypassing wbc_entity_usage.
 *
 * @license GPL-2.0-or-later
 * @author Adam Wight
 */
class DumpActualEntityUsage extends Maintenance {

	public function __construct() {
		parent::__construct();

		$this->addDescription( 'Dump actual entity usage before bundling limits are applied.' );

		$this->addOption(
			'page',
			'The page ID to parse and dump usages for. Can pass multiple pages as a comma-separated string.',
			true,
			true
		);
	}

	/**
	 * @see Maintenance::execute
	 */
	public function execute(): bool {
		if ( !WikibaseSettings::isClientEnabled() ) {
			$this->fatalError(
				'You need to have WikibaseClient enabled in order to use this maintenance script!'
			);
		}

		$pageIds = explode( ',', $this->getOption( 'page' ) );

		$result = array_map( [ $this, 'getPageData' ], $pageIds );

		$this->output( json_encode( $result, JSON_PRETTY_PRINT ) );

		return true;
	}

	private function getPageData( string $pageId ): array {
		$page = MediaWikiServices::getInstance()->getWikiPageFactory()->newFromID( (int)$pageId );
		$parserOutput = $page->getParserOutput();
		$euData = $parserOutput->getExtensionData( 'wikibase-entity-usage' );
		ksort( $euData );

		$templates = $page->getTitle()->getTemplateLinksFrom();

		$result = [
			'pageid' => (int)$pageId,
			'page_title' => $page->getTitle()->getPrefixedText(),
			'templates' => array_map(
				static fn ( $title ) => $title->getPrefixedText(),
				$templates
			),
			'entity_usage' => array_keys( $euData ),
		];

		return $result;
	}

}

// @codeCoverageIgnoreStart
$maintClass = DumpActualEntityUsage::class;
require_once RUN_MAINTENANCE_IF_MAIN;
// @codeCoverageIgnoreEnd
