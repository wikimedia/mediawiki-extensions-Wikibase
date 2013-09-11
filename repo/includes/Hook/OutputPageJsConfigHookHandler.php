<?php

namespace Wikibase\Hook;

use OutputPage;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\EntityTitleLookup;
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
		if ( defined( 'MW_PHPUNIT_TEST' ) ) {
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
		$configVars = $out->getJsConfigVars();

		if ( !is_array( $configVars ) || !array_key_exists( 'wbEntityId', $configVars ) ) {
			throw new UnexpectedValueException( '$configVars were not found in parser cache' );
		}

		return $configVars;
	}

}
