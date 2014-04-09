<?php

use Wikibase\EntityId;

interface EntityLabelLookup {

	/**
	 * @param EntityId $entityid
	 * @param string $languageCode
	 *
	 * @throws EntityNotFoundException
	 * @return string
	 */
	public function getLabelForId( EntityId $entityid, $languageCode );

	/**
	 * @param EntityId $entityid
	 * @param string $languageCode
	 *
	 * @return string
	 */
	public function getDescriptionForId( EntityId $entityid, $languageCode );

	/**
	 * @param EntityId $entityid
	 * @param LanguageFallbackChain $languageFallbackChain
	 * @return array
	 */
	public function getLabelValueForId( EntityId $entityid, LanguageFallbackChain $languageFallbackChain );

	EntityInfoLabelLookup
	public function __construct( array $entityInfo );

	EntityRetrievingLabelLookup
	public function __construct( EntityLookup $entityLookup );

	TermIndexLabelLookup
	public function __construct( TermIndex $termIndex );

}
