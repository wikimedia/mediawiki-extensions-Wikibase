<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Hooks;

use EnhancedChangesList;
use Language;
use MediaWiki\Hook\EnhancedChangesListModifyBlockLineDataHook;
use MediaWiki\Hook\EnhancedChangesListModifyLineDataHook;
use MediaWiki\Hook\OldChangesListRecentChangesLineHook;
use MediaWiki\MediaWikiServices;
use MediaWiki\User\UserNameUtils;
use OldChangesList;
use RecentChange;
use UnexpectedValueException;
use Wikibase\Client\RecentChanges\ChangeLineFormatter;
use Wikibase\Client\RecentChanges\ExternalChangeFactory;
use Wikibase\Client\RecentChanges\RecentChangeFactory;
use Wikibase\Client\RepoLinker;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\Lib\SettingsArray;

/**
 * Handlers for hooks dealing with Wikibase changes in client recent changes and watchlists
 *
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Matěj Suchánek
 */
class ChangesListLinesHandler implements
	EnhancedChangesListModifyBlockLineDataHook,
	EnhancedChangesListModifyLineDataHook,
	OldChangesListRecentChangesLineHook
{

	/**
	 * @var ExternalChangeFactory
	 */
	private $changeFactory;

	/**
	 * @var ChangeLineFormatter
	 */
	private $formatter;

	public function __construct( ExternalChangeFactory $changeFactory, ChangeLineFormatter $formatter ) {
		$this->changeFactory = $changeFactory;
		$this->formatter = $formatter;
	}

	public static function factory(
		Language $contentLanguage,
		UserNameUtils $userNameUtils,
		EntityIdParser $entityIdParser,
		RepoLinker $repoLinker,
		SettingsArray $clientSettings
	): self {
		$changeFactory = new ExternalChangeFactory(
			$clientSettings->getSetting( 'repoSiteId' ),
			$contentLanguage,
			$entityIdParser
		);
		$formatter = new ChangeLineFormatter(
			$repoLinker,
			$userNameUtils,
			MediaWikiServices::getInstance()->getLinkRenderer(),
			MediaWikiServices::getInstance()->getCommentFormatter()
		);

		return new self( $changeFactory, $formatter );
	}

	/**
	 * @param OldChangesList &$changesList
	 * @param string &$s
	 * @param RecentChange $rc
	 * @param string[] &$classes
	 * @param string[] &$attribs
	 */
	public function onOldChangesListRecentChangesLine(
		$changesList,
		&$s,
		$rc,
		&$classes = [],
		&$attribs = []
	): void {
		if ( RecentChangeFactory::isWikibaseChange( $rc ) ) {
			try {
				$externalChange = $this->changeFactory->newFromRecentChange( $rc );
			} catch ( UnexpectedValueException $e ) {
				return;
			}

			// fixme: inject formatter and flags into a changes list formatter
			$flag = $changesList->recentChangesFlags(
				[
					'wikibase-edit' => true,
					'minor' => $rc->getAttribute( 'rc_minor' ),
					'bot' => $rc->getAttribute( 'rc_bot' ),
				],
				''
			);
			$lang = $changesList->getLanguage();
			$user = $changesList->getUser();
			$title = $rc->getTitle();
			$line = $this->formatter->format( $externalChange, $title, $rc->counter, $flag, $lang, $user );

			$s = $line;
		}
	}

	/**
	 * @param EnhancedChangesList $changesList
	 * @param array &$data
	 * @param RecentChange $rc
	 */
	public function onEnhancedChangesListModifyBlockLineData(
		$changesList,
		&$data,
		$rc
	): void {
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
				$rc->counter,
				$changesList->getLanguage(),
				$changesList->getUser()
			);
		}
	}

	/**
	 * @param EnhancedChangesList $changesList
	 * @param array &$data
	 * @param RecentChange[] $block
	 * @param RecentChange $rc
	 * @param string[] &$classes
	 * @param string[] &$attribs
	 */
	public function onEnhancedChangesListModifyLineData(
		$changesList,
		&$data,
		$block,
		$rc,
		&$classes = [],
		&$attribs = []
	): void {
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
				$rc->counter,
				$changesList->getLanguage(),
				$changesList->getUser()
			);
		}
	}

}
