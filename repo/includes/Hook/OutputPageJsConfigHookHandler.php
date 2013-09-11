<?php

namespace Wikibase\Hook;

use OutputPage;
use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\EntityTitleLookup;
use Wikibase\NamespaceUtils;
use Wikibase\OutputPageJsConfigBuilder;
use Wikibase\Settings;
use UnexpectedValueException;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class OutputPageJsConfigHookHandler {

	public function __construct( EntityIdParser $idParser, EntityTitleLookup $entityTitleLookup,
		Settings $settings
	) {
		$this->idParser = $idParser;
		$this->entityTitleLookup = $entityTitleLookup;
		$this->settings = $settings;
	}

	/**
	 * @throws UnexpectedValueException
	 * @return boolean
	 */
	public function handle( OutputPage $out ) {
		$title = $out->getTitle();

		if ( !$this->isTitleInEntityNamespace( $title ) ) {
			return true;
		}

		$parserOutputConfigVars = $this->getParserOutputConfigVars( $out );

		$entityId = $this->idParser->parse( $parserOutputConfigVars['wbEntityId'] );
		$configVars = $this->buildConfigVars( $out, $entityId );

		$this->registerConfigVars( $out, $configVars );

		return true;
	}

	/**
	 * @param OutputPage $out
	 * @param EntityId $entityId
	 *
	 * @return array
	 */
	private function buildConfigVars( OutputPage $out, EntityId $entityId ) {
		$configBuilder = new OutputPageJsConfigBuilder( $this->entityTitleLookup );

		$rightsUrl = $this->settings->get( 'dataRightsUrl' );
		$rightsText = $this->settings->get( 'dataRightsText' );
		$user = $out->getUser();
		$lang = $out->getLanguage();

		$configVars = $configBuilder->build( $user, $lang, $entityId, $rightsUrl, $rightsText );

		return $configVars;
	}

	/**
	 * @param OutputPage $out
	 * @param array $configVars
	 */
	private function registerConfigVars( OutputPage $out, array $configVars ) {
		foreach( $configVars as $key => $configVar ) {
			$out->addJsConfigVars( $key, $configVar );
		}
	}

	/**
	 * @param OutputPage $out
	 *
	 * @throws UnexpectedValueException
	 * @return array
	 */
	private function getParserOutputConfigVars( OutputPage $out ) {
		$title = $out->getTitle();
		$configVars = $out->getJsConfigVars();

		if ( !$this->hasEntityIdParamInConfig( $configVars ) ) {
			throw new UnexpectedValueException( '$configVars were not found in parser cache' );
		}

		return $configVars;
	}

	/**
	 * @param mixed
	 *
	 * @return boolean
	 */
	private function hasEntityIdParamInConfig( $configVars ) {
		return is_array( $configVars ) && array_key_exists( 'wbEntityId', $configVars );
	}

	/**
	 * @param Title $title
	 *
	 * @return boolean
	 */
	private function isTitleInEntityNamespace( Title $title ) {
		$entityNamespaces = array_flip( NamespaceUtils::getEntityNamespaces() );
		$namespace = $title->getNamespace();

		return in_array( $namespace, $entityNamespaces );
	}

}
