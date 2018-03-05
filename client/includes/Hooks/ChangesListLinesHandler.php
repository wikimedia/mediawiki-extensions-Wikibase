<?php

namespace Wikibase\Client\Hooks;

use ChangesList;
use EnhancedChangesList;
use OldChangesList;
use RecentChange;
use UnexpectedValueException;
use Wikibase\Client\RecentChanges\ChangeLineFormatter;
use Wikibase\Client\RecentChanges\ExternalChangeFactory;
use Wikibase\Client\RecentChanges\RecentChangeFactory;
use Wikibase\Client\WikibaseClient;

/**
 * Handlers for hooks dealing with Wikibase changes in client recent changes and watchlists
 *
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Matěj Suchánek
 */
class ChangesListLinesHandler {

	/**
	 * @var ExternalChangeFactory
	 */
	private $changeFactory;

	/**
	 * @var ChangeLineFormatter
	 */
	private $formatter;

	/**
	 * @var self
	 */
	private static $instance = null;

	public function __construct( ExternalChangeFactory $changeFactory, ChangeLineFormatter $formatter ) {
		$this->changeFactory = $changeFactory;
		$this->formatter = $formatter;
	}

	/**
	 * @param ChangesList $changesList
	 * @return self
	 */
	private static function newFromGlobalState( ChangesList $changesList ) {
		$wikibaseClient = WikibaseClient::getDefaultInstance();
		$changeFactory = new ExternalChangeFactory(
			$wikibaseClient->getSettings()->getSetting( 'repoSiteId' ),
			$wikibaseClient->getContentLanguage(),
			$wikibaseClient->getEntityIdParser()
		);
		$formatter = new ChangeLineFormatter(
			$changesList->getUser(),
			$changesList->getLanguage(),
			$wikibaseClient->newRepoLinker()
		);

		return new self( $changeFactory, $formatter );
	}

	/**
	 * @param ChangesList $changesList
	 * @return self
	 */
	private static function getInstance( ChangesList $changesList ) {
		if ( self::$instance === null ) {
			self::$instance = self::newFromGlobalState( $changesList );
		}

		return self::$instance;
	}

	/**
	 * Hook for formatting recent changes links
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/OldChangesListRecentChangesLine
	 * @see doOldChangesListRecentChangesLine
	 *
	 * @param OldChangesList &$changesList
	 * @param string &$s
	 * @param RecentChange $rc
	 * @param string[] &$classes
	 */
	public static function onOldChangesListRecentChangesLine(
		OldChangesList &$changesList,
		&$s,
		RecentChange $rc,
		&$classes = []
	) {
		$self = self::getInstance( $changesList );
		$self->doOldChangesListRecentChangesLine( $changesList, $s, $rc, $classes );
	}

	/**
	 * @param OldChangesList &$changesList
	 * @param string &$s
	 * @param RecentChange $rc
	 * @param string[] &$classes
	 */
	public function doOldChangesListRecentChangesLine(
		OldChangesList &$changesList,
		&$s,
		RecentChange $rc,
		&$classes
	) {
		if ( RecentChangeFactory::isWikibaseChange( $rc ) ) {
			try {
				$externalChange = $this->changeFactory->newFromRecentChange( $rc );
			} catch ( UnexpectedValueException $e ) {
				return;
			}

			// fixme: inject formatter and flags into a changes list formatter
			$flag = $changesList->recentChangesFlags( [ 'wikibase-edit' => true ], '' );
			$line = $this->formatter->format( $externalChange, $rc->getTitle(), $rc->counter, $flag );

			$s = $line;
		}
	}

	/**
	 * Static handler for EnhancedChangesListModifyBlockLineData
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/EnhancedChangesListModifyBlockLineData
	 * @see doEnhancedChangesListModifyBlockLineData
	 *
	 * @param EnhancedChangesList $changesList
	 * @param array &$data
	 * @param RecentChange $rc
	 */
	public static function onEnhancedChangesListModifyBlockLineData(
		EnhancedChangesList $changesList,
		array &$data,
		RecentChange $rc
	) {
		$self = self::getInstance( $changesList );
		$self->doEnhancedChangesListModifyBlockLineData( $changesList, $data, $rc );
	}

	/**
	 * @param EnhancedChangesList $changesList
	 * @param array &$data
	 * @param RecentChange $rc
	 */
	public function doEnhancedChangesListModifyBlockLineData(
		EnhancedChangesList $changesList,
		array &$data,
		RecentChange $rc
	) {
		$data['recentChangesFlags']['wikibase-edit'] = false;
		if ( RecentChangeFactory::isWikibaseChange( $rc ) ) {
			try {
				$externalChange = $this->changeFactory->newFromRecentChange( $rc );
			} catch ( UnexpectedValueException $e ) {
				return;
			}

			$this->formatter->formatDataForEnhancedBlockLine(
				$data,
				$externalChange,
				$rc->getTitle(),
				$rc->counter
			);
		}
	}

	/**
	 * Static handler for EnhancedChangesListModifyLineData
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/EnhancedChangesListModifyLineData
	 * @see doEnhancedChangesListModifyLineData
	 *
	 * @param EnhancedChangesList $changesList
	 * @param array &$data
	 * @param RecentChange[] $block
	 * @param RecentChange $rc
	 * @param string[] &$classes
	 */
	public static function onEnhancedChangesListModifyLineData(
		EnhancedChangesList $changesList,
		array &$data,
		array $block,
		RecentChange $rc,
		array &$classes
	) {
		$self = self::getInstance( $changesList );
		$self->doEnhancedChangesListModifyLineData( $changesList, $data, $block, $rc, $classes );
	}

	/**
	 * @param EnhancedChangesList $changesList
	 * @param array &$data
	 * @param RecentChange[] $block
	 * @param RecentChange $rc
	 * @param string[] &$classes
	 */
	public function doEnhancedChangesListModifyLineData(
		EnhancedChangesList $changesList,
		array &$data,
		array $block,
		RecentChange $rc,
		array &$classes
	) {
		$data['recentChangesFlags']['wikibase-edit'] = false;
		if ( RecentChangeFactory::isWikibaseChange( $rc ) ) {
			try {
				$externalChange = $this->changeFactory->newFromRecentChange( $rc );
			} catch ( UnexpectedValueException $e ) {
				return;
			}

			$this->formatter->formatDataForEnhancedLine(
				$data,
				$externalChange,
				$rc->getTitle(),
				$rc->counter
			);
		}
	}

}
