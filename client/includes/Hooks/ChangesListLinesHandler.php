<?php

namespace Wikibase\Client\Hooks;

use ChangesList;
use EnhancedChangesList;
use OldChangesList;
use RecentChange;
use ResultWrapper;
use UnexpectedValueException;
use Wikibase\Client\RecentChanges\ChangeLineFormatter;
use Wikibase\Client\RecentChanges\ExternalChangeFactory;
use Wikibase\Client\RecentChanges\RecentChangeFactory;
use Wikibase\Client\WikibaseClient;

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
	 * @param ChangesList
	 * @return self
	 */
	private static function newFromGlobalState( ChangesList $changesList ) {
		$wikibaseClient = WikibaseClient::getDefaultInstance();
		$changeFactory = new ExternalChangeFactory(
			$wikibaseClient->getSettings()->getSetting( 'repoSiteId' ),
			$wikibaseClient->getContentLanguage()
		);
		$formatter = new ChangeLineFormatter(
			$changesList->getUser(),
			$changesList->getLanguage(),
			$wikibaseClient->newRepoLinker()
		);
		return new self( $changeFactory, $formatter );
	}

	/**
	 * @return self
	 */
	private static function getInstance( $changesList ) {
		if ( self::$instance === null ) {
			self::$instance = self::newFromGlobalState( $changesList );
		}

		return self::$instance;
	}

	/**
	 * @param RecentChange $rc
	 * @return bool
	 */
	private static function isWikibaseChange( RecentChange $rc ) {
		return $rc->getAttribute( 'rc_source' ) === RecentChangeFactory::SRC_WIKIBASE;
	}

	/**
	 * @param RecentChange[] $block
	 * @return bool
	 */
	private static function areAllChangesWikibase( array $block ) {
		return !in_array( false, array_map( 'self::isWikibaseChange', $block ) );
	}

	/**
	 * Hook for formatting recent changes links
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/OldChangesListRecentChangesLine
	 *
	 * @param OldChangesList &$changesList
	 * @param string &$s
	 * @param RecentChange $rc
	 * @param string[] &$classes
	 * @return bool
	 */
	public static function onOldChangesListRecentChangesLine(
		OldChangesList &$changesList,
		&$s,
		RecentChange $rc,
		&$classes = []
	) {
		$self = self::getInstance( $changesList );
		return $self->doOldChangesListRecentChangesLine( $changesList, $s, $rc, $classes );
	}

	/**
	 * @param OldChangesList &$changesList
	 * @param string &$s
	 * @param RecentChange $rc
	 * @param string[] &$classes
	 * @return bool
	 */
	public function doOldChangesListRecentChangesLine(
		OldChangesList &$changesList,
		&$s,
		RecentChange $rc,
		&$classes
	) {
		if ( self::isWikibaseChange( $rc ) ) {
			try {
				$externalChange = $this->changeFactory->newFromRecentChange( $rc );
			} catch ( UnexpectedValueException $e ) {
				return false;
			}

			// fixme: inject formatter and flags into a changes list formatter
			$flag = $changesList->recentChangesFlags( [ 'wikibase-edit' => true ], '' );
			$line = $this->formatter->format( $externalChange, $rc->getTitle(), $rc->counter, $flag );

			$s = $line;
		}
	}

	/**
	 * @param EnhancedChangesList $changesList
	 * @param array &$data
	 * @param RecentChange $rc
	 * @return bool
	 */
	public static function onEnhancedChangesListModifyBlockLineData(
		EnhancedChangesList $changesList,
		array &$data,
		RecentChange $rc
	) {
		$self = self::getInstance( $changesList );
		return $self->doEnhancedChangesListModifyBlockLineData( $changesList, $data, $rc );
	}

	/**
	 * @param EnhancedChangesList $changesList
	 * @param array &$data
	 * @param RecentChange $rc
	 * @return bool
	 */
	public function doEnhancedChangesListModifyBlockLineData(
		EnhancedChangesList $changesList,
		array &$data,
		RecentChange $rc
	) {
		$data['recentChangesFlags']['wikibase-edit'] = false;
		if ( self::isWikibaseChange( $rc ) ) {
			try {
				$externalChange = $this->changeFactory->newFromRecentChange( $rc );
			} catch ( UnexpectedValueException $e ) {
				return false;
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
	 * @param EnhancedChangesList $changesList
	 * @param array &$data
	 * @param RecentChange[] $block
	 * @param RecentChange $rc
	 * @param string[] &$classes
	 * @return bool
	 */
	public static function onEnhancedChangesListModifyLineData(
		EnhancedChangesList $changesList,
		array &$data,
		array $block,
		RecentChange $rc,
		array &$classes
	) {
		$self = self::getInstance( $changesList );
		return $self->doEnhancedChangesListModifyLineData( $changesList, $data, $block, $rc, $classes );
	}

	/**
	 * @param EnhancedChangesList $changesList
	 * @param array &$data
	 * @param RecentChange[] $block
	 * @param RecentChange $rc
	 * @param string[] &$classes
	 * @return bool
	 */
	public function doEnhancedChangesListModifyLineData(
		EnhancedChangesList $changesList,
		array &$data,
		array $block,
		RecentChange $rc,
		array &$classes
	) {
		$data['recentChangesFlags']['wikibase-edit'] = false;
		if ( self::isWikibaseChange( $rc ) ) {
			try {
				$externalChange = $this->changeFactory->newFromRecentChange( $rc );
			} catch ( UnexpectedValueException $e ) {
				return false;
			}
			$this->formatter->formatDataForEnhancedLine(
				$data,
				$externalChange,
				$rc->getTitle(),
				$rc->counter
			);
		}
	}

	/**
	 * @param EnhancedChangesList $changesList
	 * @param HTML[] &$links
	 * @param RecentChange[] $block
	 */
	public static function onEnhancedChangesList_getLogText(
		EnhancedChangesList $changesList,
		array &$links,
		array $block
	) {
		if ( self::areAllChangesWikibase( $block ) ) {
			// @todo
			$links = [];
		}
	}

	/**
	 * @param ChangesList $changesList
	 * @param ResultWrapper|array $rows
	 */
	public static function onChangesListInitRows( ChangesList $changesList, $rows ) {
		$changesList->getOutput()->addModuleStyles( 'wikibase.client.changeslist.css' );
	}

}