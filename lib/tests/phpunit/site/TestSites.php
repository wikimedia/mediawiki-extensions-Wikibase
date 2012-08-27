<?php

class TestSites {

	public static function getSites() {
		$sites = array();

		$site = Sites::newSite( 'foobar' );
		$site->setId( 1 );
		$sites[] = $site;

		$site = Sites::newSite( 'enwiktionary' );
		$site->setId( 2 );
		$site->setGroup( 'wiktionary' );
		$site->setType( Site::TYPE_MEDIAWIKI );
		$site->setLanguageCode( 'en' );
		$site->addNavigationId( 'enwiktionary' );
		$sites[] = $site;

		$site = Sites::newSite( 'dewiktionary' );
		$site->setId( 3 );
		$site->setGroup( 'wiktionary' );
		$site->setType( Site::TYPE_MEDIAWIKI );
		$site->setLanguageCode( 'de' );
		$site->addInterwikiId( 'dewiktionary' );
		$site->addInterwikiId( 'wiktionaryde' );
		$sites[] = $site;

		$site = Sites::newSite( 'spam' );
		$site->setId( 4 );
		$site->setGroup( 'spam' );
		$site->setType( Site::TYPE_UNKNOWN );
		$site->setLanguageCode( 'en' );
		$site->addNavigationId( 'spam' );
		$site->addNavigationId( 'spamz' );
		$site->addInterwikiId( 'spamzz' );
		$sites[] = $site;

		$id = 5;

		foreach ( array( 'en', 'de', 'nl', 'sv', 'sr', 'no', 'nn' ) as $langCode ) {
			$site = Sites::newSite( $langCode . 'wiki' );
			$site->setId( $id++ );
			$site->setGroup( 'wikipedia' );
			$site->setType( Site::TYPE_MEDIAWIKI );
			$site->setLanguageCode( $langCode );
			$site->addInterwikiId( $langCode );
			$site->addNavigationId( $langCode );
			$sites[] = $site;
		}

		return $sites;
	}

}