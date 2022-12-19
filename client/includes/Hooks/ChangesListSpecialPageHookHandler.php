<?php

namespace Wikibase\Client\Hooks;

use ChangesListBooleanFilter;
use ChangesListSpecialPage;
use ExtensionRegistry;
use MediaWiki\MediaWikiServices;
use MediaWiki\SpecialPage\Hook\ChangesListSpecialPageQueryHook;
use MediaWiki\User\UserOptionsLookup;
use User;
use Wikibase\Client\RecentChanges\RecentChangeFactory;
use Wikibase\Client\WikibaseClient;
use Wikibase\Lib\Rdbms\ClientDomainDbFactory;
use Wikibase\Lib\SettingsArray;
use Wikimedia\Rdbms\IDatabase;

/**
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class ChangesListSpecialPageHookHandler implements ChangesListSpecialPageQueryHook {

	/**
	 * @var IDatabase
	 */
	private $dbr;

	/**
	 * @var bool
	 */
	private $showExternalChanges;

	/**
	 * @var UserOptionsLookup
	 */
	private $userOptionsLookup;

	/**
	 * @param IDatabase $dbr
	 * @param bool $showExternalChanges
	 * @param UserOptionsLookup $userOptionsLookup
	 */
	public function __construct(
		IDatabase $dbr,
		$showExternalChanges,
		UserOptionsLookup $userOptionsLookup
	) {
		$this->dbr = $dbr;
		$this->showExternalChanges = $showExternalChanges;
		$this->userOptionsLookup = $userOptionsLookup;
	}

	public static function factory(
		UserOptionsLookup $userOptionsLookup,
		ClientDomainDbFactory $dbFactory,
		SettingsArray $clientSettings
	): self {
		return new self(
			$dbFactory->newLocalDb()->connections()->getReadConnection(),
			$clientSettings->getSetting( 'showExternalRecentChanges' ),
			$userOptionsLookup
		);
	}

	/**
	 * This is used to force-hide Wikibase changes if hasWikibaseChangesEnabled returns
	 * false.  The user will not even see the option in that case.
	 *
	 * @param string $name Name of the special page, e.g. 'Watchlist'
	 * @param array &$tables Array of tables to be queried
	 * @param array &$fields Array of columns to select
	 * @param array &$conds Array of WHERE conditionals for query
	 * @param array &$query_options Array of options for the database request
	 * @param array &$join_conds Join conditions for the tables
	 * @param \FormOptions $opts FormOptions for this request
	 */
	public function onChangesListSpecialPageQuery( $name, &$tables, &$fields,
			&$conds, &$query_options, &$join_conds, $opts ) {
		if ( !$this->hasWikibaseChangesEnabled() ) {
			// Force-hide if hasWikibaseChangesEnabled is false
			// The user-facing hideWikibase is handled by
			// ChangesListSpecialPageStructuredFilters and connected code.
			$this->addWikibaseConditions( $this->dbr, $conds );
		}
	}

	/**
	 * @param ChangesListSpecialPage $special
	 */
	public static function onChangesListSpecialPageStructuredFilters( $special ) {
		$services = MediaWikiServices::getInstance();
		$handler = self::factory(
			MediaWikiServices::getInstance()->getUserOptionsLookup(),
			WikibaseClient::getClientDomainDbFactory( $services ),
			WikibaseClient::getSettings( $services )
		);
		// The *user-facing* filter is only registered if external changes
		// are enabled.
		//
		// If the user-facing filter is not registered, it's always *hidden*.
		// (See ChangesListSpecialPageQuery).
		if ( $handler->hasWikibaseChangesEnabled() ) {
			$handler->addFilter( $special );
		}
	}

	protected function addFilter( ChangesListSpecialPage $specialPage ) {
		$filterName = $this->getFilterName();
		$changeTypeGroup = $specialPage->getFilterGroup( 'changeType' );

		$specialPage->getOutput()->addModules( 'wikibase.client.jqueryMsg' );
		$specialPage->getOutput()->addModuleStyles( 'wikibase.client.miscStyles' );

		$wikidataFilter = new ChangesListBooleanFilter( [
			'name' => $filterName,
			'group' => $changeTypeGroup,
			'priority' => -4,
			'label' => 'wikibase-rcfilters-hide-wikibase-label',
			'description' => 'wikibase-rcfilters-hide-wikibase-description',
			'showHide' => 'wikibase-rc-hide-wikidata',
			// If the preference is enabled, then don't hide Wikidata edits
			'default' => !$this->hasShowWikibaseEditsPrefEnabled( $specialPage->getUser(), $specialPage->getName() ),
			'queryCallable' => function ( $specialClassName, $ctx, $dbr, &$tables, &$fields,
				&$conds, &$query_options, &$join_conds ) {
				$this->addWikibaseConditions( $dbr, $conds );
			},
			'cssClassSuffix' => 'src-mw-wikibase',
			'isRowApplicableCallable' => static function ( $ctx, $rc ) {
				return RecentChangeFactory::isWikibaseChange( $rc );
			},
		] );

		$significanceGroup = $specialPage->getFilterGroup( 'significance' );
		$hideMajorFilter = $significanceGroup->getFilter( 'hidemajor' );
		$hideMajorFilter->conflictsWith(
			$wikidataFilter,
			'wikibase-rcfilters-hide-wikibase-conflicts-major-global',
			'wikibase-rcfilters-major-conflicts-hide-wikibase',
			'wikibase-rcfilters-hide-wikibase-conflicts-major'
		);

		$extensionRegistry = ExtensionRegistry::getInstance();
		if ( $extensionRegistry->isLoaded( 'ORES' ) ) {
			$damagingGroup = $specialPage->getFilterGroup( 'damaging' );
			if ( $damagingGroup ) {
				$wikidataFilter->conflictsWith(
					$damagingGroup,
					'wikibase-rcfilters-hide-wikibase-conflicts-ores-global',
					'wikibase-rcfilters-hide-wikibase-conflicts-ores',
					'wikibase-rcfilters-damaging-conflicts-hide-wikibase'
				);
			}

			$goodfaithGroup = $specialPage->getFilterGroup( 'goodfaith' );
			if ( $goodfaithGroup ) {
				$wikidataFilter->conflictsWith(
					$goodfaithGroup,
					'wikibase-rcfilters-hide-wikibase-conflicts-ores-global',
					'wikibase-rcfilters-hide-wikibase-conflicts-ores',
					'wikibase-rcfilters-goodfaith-conflicts-hide-wikibase'
				);
			}
		}
	}

	/**
	 * @param IDatabase $dbr
	 * @param array &$conds
	 */
	public function addWikibaseConditions( IDatabase $dbr, array &$conds ) {
		$conds[] = 'rc_source != ' . $dbr->addQuotes( RecentChangeFactory::SRC_WIKIBASE );
	}

	/**
	 * @return bool
	 */
	protected function hasWikibaseChangesEnabled() {
		return $this->showExternalChanges;
	}

	private function hasShowWikibaseEditsPrefEnabled( User $user, string $pageName ): bool {
		return (bool)$this->userOptionsLookup->getOption( $user, $this->getOptionName( $pageName ) );
	}

	/**
	 * @return string
	 */
	private function getFilterName() {
		return 'hideWikibase';
	}

	private function getOptionName( string $pageName ): string {
		if ( $pageName === 'Watchlist' ) {
			return 'wlshowwikibase';
		}

		return 'rcshowwikidata';
	}

}
