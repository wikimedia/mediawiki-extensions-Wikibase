<?php

namespace Wikibase\Repo\Hooks;

use Html;
use IContextSource;
use Linker;
use MediaWikiServices;
use Title;
use Wikibase\DataModel\SiteLink;
use Wikibase\Store\Sql\SqlSubscriptionLookup;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\NamespaceChecker;

/**
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 */
class InfoActionHookHandler {

	/**
	 * @var EntityNamespaceLookup
	 */
	private $namespaceChecker;

	/**
	 * @var SqlSubscriptionLookup
	 */
	private $subLookup;

	public function __construct( EntityNamespaceLookup $namespaceChecker, SqlSubscriptionLookup $subLookup ) {
		$this->namespaceChecker = $namespaceChecker;
		$this->subLookup = $subLookup;
	}

	/**
	 * @param IContextSource $context
	 * @param array $pageInfo
	 *
	 * @return array
	 */
	public function handle( IContextSource $context, array $pageInfo ) {
		// Check if wikibase namespace is enabled
		$title = $context->getTitle();

		if ( $this->namespaceChecker->isEntityNamespace( $title->getNamespace() ) && $title->exists() ) {
			$pageInfo['header-properties'][] = $this->getPageInfoRow( $context, $title );
		}

		return $pageInfo;
	}

	/**
	 * @param IContextSource $context
	 * @param Title $title
	 *
	 * @return array
	 */
	public function getPageInfoRow( IContextSource $context, Title $title ) {
		$entities = [ $title->getText() ];
		$subscriptions = $this->subLookup->queryIdBasedSubscriptions( $entities );
		if ( !$subscriptions ) {
			return $this->getUnsubscribedEntities( $context );
		} else {
			return $this->formatSubscriptions( $context, $subscriptions, $title );
		}
	}

	/**
	 * @param IContextSource $context
	 * @param array $usage
	 * @param Title $title
	 *
	 * @return string[]
	 */
	private function formatSubscriptions( IContextSource $context, array $subscriptions, Title $title ) {
		$output = '';
		$siteLookup = \MediaWiki\MediaWikiServices::getInstance()->getSiteLookup();

		foreach ( $subscriptions as $subscription ) {
			$link = $this->formatSubscription( $subscription, $context, $title, $siteLookup );
			$output .= Html::rawElement( 'li', [], $link );

		}
		$output = Html::rawElement( 'ul', [], $output );
		return array( $context->msg( 'wikibase-pageinfo-subscription' ), $output );
	}

	/**
	 * @param IContextSource $context
	 *
	 * @return string[]
	 */
	private function getUnsubscribedEntities( IContextSource $context ) {
		return array(
			$context->msg( 'wikibase-pageinfo-subscription' ),
			$context->msg( 'wikibase-pageinfo-subscription-none' )
		);
	}

	/**
	 * @param string $subscription
	 * @param IContextSource $context
	 * @param Title $title
	 *
	 * @return string[]
	 */
	private function formatSubscription( $subscription, IContextSource $context, Title $title, $siteLookup ) {
		$site = $siteLookup->getSite( $subscription );
		if ( !$site ) {
			return $subscription;
		}
		if ( !$site->getInterwikiIds() ) {
			return $subscription;
		}

		$title = Title::makeTitle( '', 'Special:EntityUsage/' . $title->getText(), '', $site->getInterwikiIds()[0] );
		return Linker::link( $title, $subscription );
	}

}
