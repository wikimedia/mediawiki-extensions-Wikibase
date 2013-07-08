<?php

namespace Wikibase\Api;

use ApiBase, User;

use Wikibase\DataModel\SimpleSiteLink;
use Wikibase\Entity;
use Wikibase\EntityContent;
use Wikibase\ItemContent;
use Wikibase\SiteLink;
use Wikibase\Autocomment;
use Wikibase\Utils;

/**
 * API module to associate a page on a site with a Wikibase entity or remove an already made such association.
 * Requires API write mode to be enabled.
 *
 * @since 0.1
 *
 * @file
 * @ingroup WikibaseRepo
 * @ingroup API
 *
 * @licence GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
class SetSiteLink extends ModifyEntity {

	/**
	 * @see  \Wikibase\Api\ModifyEntity::getRequiredPermissions()
	 */
	protected function getRequiredPermissions( Entity $entity, array $params ) {
		$permissions = parent::getRequiredPermissions( $entity, $params );

		$permissions[] = 'sitelink-' . ( strlen( $params['linktitle'] ) ? 'update' : 'remove' );
		return $permissions;
	}

	/**
	 * @see  \Wikibase\Api\ModifyEntity::getEntityContent
	 */
	protected function getEntityContent( array $params ) {
		$entityContent = parent::getEntityContent( $params );

		// If we found anything then check if it is of the correct base class
		if ( !is_null( $entityContent ) && !( $entityContent instanceof ItemContent ) ) {
			$this->dieUsage( $this->msg( 'wikibase-api-wrong-class' )->text(), 'wrong-class' );
		}

		return $entityContent;
	}
	/**
	 * Make sure the required parameters are provided and that they are valid.
	 *
	 * @since 0.1
	 *
	 * @param array $params
	 */
	protected function validateParameters( array $params ) {
		parent::validateParameters( $params );

		// Note that linksite should always exist as a prerequisite for this
		// call to succeede. The param linktitle will not always exist because
		// that signals a sitelink to remove.
	}

	/**
	 * @see ApiModifyEntity::modifyEntity()
	 */
	protected function modifyEntity( EntityContent &$entityContent, array $params ) {
		wfProfileIn( __METHOD__ );
		$summary = $this->createSummary( $params );
		$summary->setLanguage( $params['linksite'] ); //XXX: not really a language!

		if ( !( $entityContent instanceof ItemContent ) ) {
			wfProfileOut( __METHOD__ );
			$this->dieUsage( "The given entity is not an item", "not-an-item" );
		}

		/* @var Item $item */
		$item = $entityContent->getItem();

		if ( isset( $params['linksite'] ) && ( $params['linktitle'] === '' ) ) {
			$linksite = Utils::trimToNFC( $params['linksite'] );

			try {
				$link = $item->getSimpleSiteLink( $linksite );
				$item->removeSiteLink( $params['linksite'] );
				$this->addSiteLinksToResult( array( $link ), 'entity', 'sitelinks', 'sitelink', array( 'removed' ) );

				$summary->setAction( 'remove' );
			} catch ( \OutOfBoundsException $exception ) {
				// never mind then
			}

			wfProfileOut( __METHOD__ );
			return $summary; // would be nice to signal "nothing to do" somehow
		}
		else {
			$sites = $this->getSiteLinkTargetSites();
			$site = $sites->getSite( $params['linksite'] );

			if ( $site === false ) {
				wfProfileOut( __METHOD__ );
				$this->dieUsage( $this->msg( 'wikibase-api-not-recognized-siteid' )->text(), 'not-recognized-siteid' );
			}

			$page = $site->normalizePageName( Utils::trimWhitespace( $params['linktitle'] ) );

			if ( $page === false ) {
				wfProfileOut( __METHOD__ );
				$this->dieUsage( $this->msg( 'wikibase-api-no-external-page' )->text(), 'no-external-page' );
			}

			$link = new SimpleSiteLink( $site->getGlobalId(), $page );

			$item->addSimpleSiteLink( $link );

			$this->addSiteLinksToResult( array( $link ), 'entity', 'sitelinks', 'sitelink', array( 'url' ) );

			$summary->setAction( 'set' );
			$summary->addAutoSummaryArgs( $page );

			wfProfileOut( __METHOD__ );
			return $summary;
		}
	}

	/**
	 * Returns a list of all possible errors returned by the module
	 * @return array in the format of array( key, param1, param2, ... ) or array( 'code' => ..., 'info' => ... )
	 */
	public function getPossibleErrors() {
		return array_merge( parent::getPossibleErrors(), array(
			array( 'code' => 'wrong-class', 'info' => $this->msg( 'wikibase-api-wrong-class' )->text() ),
			array( 'code' => 'not-an-item', 'info' => $this->msg( 'wikibase-api-not-an-item' )->text() ),
			array( 'code' => 'not-recognized-siteid', 'info' => $this->msg( 'wikibase-api-not-recognized-siteid' )->text() ),
			array( 'code' => 'no-external-page', 'info' => $this->msg( 'wikibase-api-no-external-page' )->text() ),
		) );
	}

	/**
	 * Returns an array of allowed parameters (parameter name) => (default
	 * value) or (parameter name) => (array with PARAM_* constants as keys)
	 * Don't call this function directly: use getFinalParams() to allow
	 * hooks to modify parameters as needed.
	 * @return array|bool
	 */
	public function getAllowedParams() {
		return array_merge(
			parent::getAllowedParams(),
			parent::getAllowedParamsForId(),
			parent::getAllowedParamsForSiteLink(),
			parent::getAllowedParamsForEntity(),
			array(
				'linksite' => array(
					ApiBase::PARAM_TYPE => $this->getSiteLinkTargetSites()->getGlobalIdentifiers(),
					ApiBase::PARAM_REQUIRED => true,
				),
				'linktitle' => array(
					ApiBase::PARAM_TYPE => 'string',
				),
			)
		);
	}

	/**
	 * Get final parameter descriptions, after hooks have had a chance to tweak it as
	 * needed.
	 *
	 * @return array|bool False on no parameter descriptions
	 */
	public function getParamDescription() {
		return array_merge(
			parent::getParamDescription(),
			parent::getParamDescriptionForId(),
			parent::getParamDescriptionForSiteLink(),
			parent::getParamDescriptionForEntity(),
			array(
				'linksite' => 'The identifier of the site on which the article to link resides',
				'linktitle' => 'The title of the article to link',
			)
		);
	}

	/**
	 * @see \ApiBase::getDescription()
	 */
	public function getDescription() {
		return array(
			'API module to associate an article on a wiki with a Wikibase item or remove an already made such association.'
		);
	}

	/**
	 * Returns usage examples for this module. Return false if no examples are available.
	 * @return bool|string|array
	 */
	protected function getExamples() {
		return array(
			'api.php?action=wbsetsitelink&id=Q42&linksite=enwiki&linktitle=Hydrogen'
			=> 'Add a sitelink "Hydrogen" for English page with id "Q42", if the site link does not exist',
			'api.php?action=wbsetsitelink&id=Q42&linksite=enwiki&linktitle=Hydrogen&summary=World%20domination%20will%20be%20mine%20soon!'
			=> 'Add a sitelink "Hydrogen" for English page with id "Q42", if the site link does not exist with an edit summary of "World domination will be mine soon!"',
			'api.php?action=wbsetsitelink&site=enwiki&title=Hydrogen&tosite=dewiki&totitle=Wasserstoff'
			=> 'Add a sitelink "Wasserstoff" for the German page on item with the link from the English page to "Hydrogen", if the site link does not exist',
		);
	}

}
