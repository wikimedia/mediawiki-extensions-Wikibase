<?php

namespace Wikibase\Client\Hooks;

use ChangesList;
use Language;
use RecentChange;
use User;
use Wikibase\Client\RecentChanges\ChangeLineFormatter;
use Wikibase\Client\RecentChanges\ExternalChangeFactory;
use Wikibase\Client\RecentChanges\RecentChangeFactory;
use Wikibase\Client\WikibaseClient;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class OldChangesListHookHandler {

	/**
	 * @var ExternalChangeFactory
	 */
	private $changeFactory;

	/**
	 * @var ChangeLineFormatter
	 */
	private $changeLineFormatter;

	/**
	 * @param User $user
	 * @param Language $language
	 *
	 * @return OldChangesListHookHandler
	 */
	private static function newFromGlobalState( User $user, Language $language ) {
		$wikibaseClient = WikibaseClient::getDefaultInstance();

		$changeFactory = new ExternalChangeFactory(
			$wikibaseClient->getSettings()->getSetting( 'repoSiteId' ),
			$wikibaseClient->getContentLanguage()
		);

		$changeLineFormatter = new ChangeLineFormatter(
			$user,
			$language,
			$wikibaseClient->newRepoLinker()
		);

		return new self( $changeFactory, $changeLineFormatter );
	}

	/**
	 * @param ExternalChangeFactory $changeFactory
	 * @param ChangeLineFormatter $changeLineFormatter
	 */
	public function __construct(
		ExternalChangeFactory $changeFactory,
		ChangeLineFormatter $changeLineFormatter
	) {
		$this->changeFactory = $changeFactory;
		$this->changeLineFormatter = $changeLineFormatter;
	}

	/**
	 * Hook for formatting recent changes linkes
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/OldChangesListRecentChangesLine
	 *
	 * @param ChangesList $changesList
	 * @param string $s
	 * @param RecentChange $rc
	 * @param string[] &$classes
	 *
	 * @return bool
	 */
	public static function onOldChangesListRecentChangesLine(
		ChangesList &$changesList,
		&$s,
		RecentChange $rc,
		&$classes = array()
	) {
		$hookHandler = self::newFromGlobalState(
			$changesList->getUser(),
			$changesList->getLanguage()
		);

		if ( $rc->getAttribute( 'rc_source' ) === RecentChangeFactory::SRC_WIKIBASE ) {
			$hookHandler->formatLine( $changesList, $s, $rc, $classes );
		}

		return true;
	}

	/**
	 * @param ChangesList $changesList
	 * @param string $line
	 * @param RecentChange $rc
	 * @param string[] &$classes
	 */
	public function formatLine(
		ChangesList &$changesList,
		&$line,
		RecentChange $rc,
		&$classes = array()
	) {
		$flag = $changesList->recentChangesFlags(
			array( 'wikibase-edit' => true ),
			''
		);

		$line = $this->changeLineFormatter->format(
			$this->changeFactory->newFromRecentChange( $rc ),
			$rc->getTitle(),
			$rc->counter,
			$flag
		);

		$classes[] = 'wikibase-edit';

		// OutputPage will ignore multiple calls
		$changesList->getOutput()->addModuleStyles( 'wikibase.client.changeslist.css' );
	}

}
