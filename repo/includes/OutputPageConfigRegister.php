<?php

namespace Wikibase;

use OutputPage;
use User;
use Wikibase\DataModel\Entity\EntityIdParser;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 *
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class OutputPageConfigRegister {

	/**
	 * @var EntityIdParser
	 */
	private $idParser;

	/**
	 * @var EntityTitleLookup
	 */
	private $entityTitleLookup;

	public function __construct( EntityIdParser $idParser, EntityTitleLookup $entityTitleLookup ) {
		$this->idParser = $idParser;
		$this->entityTitleLookup = $entityTitleLookup;
	}

	/**
	 * @param OutputPage $out
	 * @param array $configVars
	 */
	public function registerJsConfigVars( OutputPage $out,  array $configVars ) {
		$entityId = $this->idParser->parse( $configVars['wbEntityId'] );

		$configVars = array_merge(
			$configVars,
			$this->getUserConfigVars( $entityId, $out->getUser() )
		);

		foreach( $configVars as $key => $configVar ) {
			$out->addJsConfigVars( $key, $configVar );
		}
	}

	/**
	 * @param EntityId $entityId
	 * @param User $user
	 *
	 * @return array
	 */
	public function getUserConfigVars( EntityId $entityId, User $user ) {
		$configVars = array();

		$title = $this->entityTitleLookup->getTitleForId( $entityId );

		// TODO: replace wbUserIsBlocked this with more useful info (which groups would be
		// required to edit? compare wgRestrictionEdit and wgRestrictionCreate)
		$configVars['wbUserIsBlocked'] = $user->isBlockedFrom( $title ); //NOTE: deprecated

		// tell JS whether the user can edit
		// TODO: make this a per-entity info
		$configVars['wbUserCanEdit'] = $title->userCan( 'edit', $user, false );

		return $configVars;
	}

}
