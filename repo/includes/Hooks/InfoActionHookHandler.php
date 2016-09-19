<?php

namespace Wikibase\Repo\Hooks;

use Html;
use IContextSource;
use Linker;
use MediaWiki\Linker\LinkRenderer;
use SiteLookup;
use Title;
use Wikibase\DataModel\SiteLink;
use Wikibase\Store\Sql\SqlSubscriptionLookup;
use Wikibase\Store\EntityIdLookup;
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
	private $subscriptionLookup;

	/**
	 * @var SiteLookup
	 */
	private $siteLookup;

	/**
	 * @var EntityIdLookup
	 */
	private $entityIdLookup;

	/**
	 * @var LinkRenderer
	 */
	private $linkRenderer;

	/**
	 * @var IContextSource
	 */
	private $context;

	public function __construct(
		EntityNamespaceLookup $namespaceChecker,
		SqlSubscriptionLookup $subscriptionLookup,
		SiteLookup $siteLookup,
		EntityIdLookup $entityIdLookup,
		LinkRenderer $linkRenderer,
		IContextSource $context
	) {
		$this->namespaceChecker = $namespaceChecker;
		$this->subscriptionLookup = $subscriptionLookup;
		$this->siteLookup = $siteLookup;
		$this->entityIdLookup = $entityIdLookup;
		$this->linkRenderer = $linkRenderer;
		$this->context = $context;
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
			$pageInfo['header-properties'][] = $this->getPageInfoRow( $title );
		}

		return $pageInfo;
	}

	/**
	 * @param Title $title
	 *
	 * @return array
	 */
	public function getPageInfoRow( Title $title ) {
		$entity = $this->entityIdLookup->getEntityIdForTitle( $title );
		$subscriptions = $this->subscriptionLookup->getSubscribers( $entity );
		if ( !$subscriptions ) {
			return $this->getNoSubscriptionText();
		} else {
			return $this->formatSubscriptions( $subscriptions, $title );
		}
	}

	/**
	 * @param array $usage
	 * @param Title $title
	 *
	 * @return string HTML[]
	 */
	private function formatSubscriptions( array $subscriptions, Title $title ) {
		$output = '';

		foreach ( $subscriptions as $subscription ) {
			$link = $this->formatSubscription( $subscription, $title );
			$output .= Html::rawElement( 'li', [], $link );

		}
		$output = Html::rawElement( 'ul', [], $output );
		return [ $this->context->msg( 'wikibase-pageinfo-subscription' )->parse(), $output ];
	}

	/**
	 * @return string[] HTML
	 */
	private function getNoSubscriptionText() {
		return [
			$this->context->msg( 'wikibase-pageinfo-subscription' )->parse(),
			$this->context->msg( 'wikibase-pageinfo-subscription-none' )->parse()
		];
	}

	/**
	 * @param string $subscription
	 * @param Title $title
	 *
	 * @return string HTML
	 */
	private function formatSubscription( $subscription, Title $title ) {
		$site = $this->siteLookup->getSite( $subscription );
		if ( !$site ) {
			return $subscription;
		}
		if ( !$site->getInterwikiIds() ) {
			return $subscription;
		}

		$title = Title::makeTitle( NS_SPECIAL, 'Special:EntityUsage/' . $title->getText(), '', $site->getInterwikiIds()[0] );
		return $this->linkRenderer->makeLink( $title, $subscription );
	}

}
