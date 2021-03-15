<?php

namespace Wikibase\Repo\Hooks;

use MediaWiki\Hook\OutputPageBeforeHTMLHook;
use OutputPage;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Repo\OutputPageJsConfigBuilder;

/**
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Marius Hoch
 */
class OutputPageJsConfigHookHandler implements OutputPageBeforeHTMLHook {

	/**
	 * @var EntityNamespaceLookup
	 */
	private $entityNamespaceLookup;

	/**
	 * @var OutputPageJsConfigBuilder
	 */
	private $outputPageConfigBuilder;

	/**
	 * @var string
	 */
	private $dataRightsUrl;

	/**
	 * @var string
	 */
	private $dataRightsText;

	/**
	 * @var string[]
	 */
	private $badgeItems;

	/**
	 * @var int
	 */
	private $stringLimit;

	/**
	 * @var bool
	 */
	private $taintedReferencesEnabled;

	/**
	 * @param EntityNamespaceLookup $entityNamespaceLookup
	 * @param string $dataRightsUrl
	 * @param string $dataRightsText
	 * @param string[] $badgeItems
	 * @param int $stringLimit
	 * @param bool $taintedReferencesEnabled
	 */
	public function __construct(
		EntityNamespaceLookup $entityNamespaceLookup,
		$dataRightsUrl,
		$dataRightsText,
		array $badgeItems,
		$stringLimit,
		$taintedReferencesEnabled
	) {
		$this->entityNamespaceLookup = $entityNamespaceLookup;
		$this->outputPageConfigBuilder = new OutputPageJsConfigBuilder();
		$this->dataRightsUrl = $dataRightsUrl;
		$this->dataRightsText = $dataRightsText;
		$this->badgeItems = $badgeItems;
		$this->stringLimit = $stringLimit;
		$this->taintedReferencesEnabled = $taintedReferencesEnabled;
	}

	public static function factory(
		EntityNamespaceLookup $entityNamespaceLookup,
		SettingsArray $repoSettings
	): self {
		return new self(
			$entityNamespaceLookup,
			$repoSettings->getSetting( 'dataRightsUrl' ),
			$repoSettings->getSetting( 'dataRightsText' ),
			$repoSettings->getSetting( 'badgeItems' ),
			$repoSettings->getSetting( 'string-limits' )['multilang']['length'],
			$repoSettings->getSetting( 'taintedReferencesEnabled' )
		);
	}

	/**
	 * @param OutputPage $out
	 * @param string &$text Text that will be displayed, in HTML
	 */
	public function onOutputPageBeforeHTML( $out, &$text ): void {
		$title = $out->getTitle();

		if ( !$title
			|| !$this->entityNamespaceLookup->isNamespaceWithEntities( $title->getNamespace() )
		) {
			return;
		}

		$outputConfigVars = $this->buildConfigVars( $out );
		$out->addJsConfigVars( $outputConfigVars );
	}

	/**
	 * @param OutputPage $out
	 *
	 * @return array
	 */
	private function buildConfigVars( OutputPage $out ) {
		return $this->outputPageConfigBuilder->build(
			$out,
			$this->dataRightsUrl,
			$this->dataRightsText,
			$this->badgeItems,
			$this->stringLimit,
			$this->taintedReferencesEnabled
		);
	}

}
