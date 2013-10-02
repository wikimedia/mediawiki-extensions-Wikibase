<?php

namespace Wikibase;

use Site;
use InvalidArgumentException;
use Wikibase\DataModel\SimpleSiteLink;

/**
 * Class for sitelink change operation
 *
 * @since 0.4
 * @licence GNU GPL v2+
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class ChangeOpSiteLink extends ChangeOpBase {

	/**
	 * @since 0.4
	 *
	 * @var string
	 */
	protected $siteId;

	/**
	 * @since 0.4
	 *
	 * @var string|null
	 */
	protected $pageName;

	/**
	 * @since 0.4
	 *
	 * @param string $siteId
	 * @param string|null $pageName Null in case the link with the provided siteId should be removed
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $siteId, $pageName ) {
		if ( !is_string( $siteId ) ) {
			throw new InvalidArgumentException( '$siteId needs to be a string' );
		}

		if ( !is_string( $pageName ) && $pageName !==null ) {
			throw new InvalidArgumentException( '$linkPage needs to be a string|null' );
		}

		$this->siteId = $siteId;
		$this->pageName = $pageName;
	}

	/**
	 * @see ChangeOp::apply()
	 */
	public function apply( Entity $entity, Summary $summary = null ) {
		if ( !( $entity instanceof Item ) ) {
			throw new InvalidArgumentException( 'ChangeOpSiteLink can only be applied to Item instances' );
		}

		if ( $this->pageName === null ) {
			if ( $entity->hasLinkToSite( $this->siteId ) ) {
				$this->updateSummary( $summary, 'remove', $this->siteId, $entity->getSimpleSiteLink( $this->siteId )->getPageName() );
				$entity->removeSiteLink( $this->siteId );
			} else {
				//TODO: throw error, or ignore silently?
			}
		} else {
			$entity->hasLinkToSite( $this->siteId ) ? $action = 'set' : $action = 'add';
			$this->updateSummary( $summary, $action, $this->siteId, $this->pageName );
			$entity->addSimpleSiteLink( new SimpleSiteLink( $this->siteId, $this->pageName ) );
		}

		return true;
	}
}
