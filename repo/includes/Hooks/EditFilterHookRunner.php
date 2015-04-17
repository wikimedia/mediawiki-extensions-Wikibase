<?php

namespace Wikibase\Repo\Hooks;

use DerivativeContext;
use Hooks;
use IContextSource;
use InvalidArgumentException;
use RequestContext;
use Status;
use Title;
use User;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\WikibaseRepo;
use WikiPage;

/**
 * Class to run the EditFilterMergedContent hook
 *
 * @since 0.5
 * @author Addshore
 */
class EditFilterHookRunner {

	/**
	 * @var EntityTitleLookup
	 */
	private $titleLookup;

	/**
	 * @var RequestContext|DerivativeContext
	 */
	private $context;

	public function __construct( EntityTitleLookup $titleLookup, $context = null ) {
		$this->titleLookup = $titleLookup;

		if ( $context !== null && !$context instanceof RequestContext && !$context instanceof DerivativeContext ) {
			throw new InvalidArgumentException( '$context must be an instance of RequestContext'
				. ' or DerivativeContext' );
		}

		if ( $context === null ) {
			$context = RequestContext::getMain();
		}

		$this->context = $context;
	}

	/**
	 * Call EditFilterMergedContent hook, if registered.
	 *
	 * @param Entity|null $newEntity The modified entity we are trying to save
	 * @param User $user the user performing the edit
	 * @param string $summary The edit summary
	 *
	 * @return Status
	 */
	public function run( $newEntity, $user, $summary ) {
		$filterStatus = Status::newGood();

		if ( !Hooks::isRegistered( 'EditFilterMergedContent' ) ) {
			return $filterStatus;
		}

		$entityContentFactory = WikibaseRepo::getDefaultInstance()->getEntityContentFactory();
		$entityContent = $entityContentFactory->newFromEntity( $newEntity );

		$context = $this->getContextForEditFilter( $newEntity );

		if ( !wfRunHooks( 'EditFilterMergedContent',
			array( $context, $entityContent, &$filterStatus, $summary, $user, false ) ) ) {

			# Error messages etc. were handled inside the hook.
			$filterStatus->setResult( false, $filterStatus->getValue() );
		}

		return $filterStatus;
	}

	/**
	 * @param EntityDocument $entity
	 *
	 * @return IContextSource
	 */
	private function getContextForEditFilter( EntityDocument $entity ) {
		$title = $this->titleLookup->getTitleForId( $entity->getId() );

		if ( $title !== null ) {
			$context = clone $this->context;
		} else {
			$context = $this->context;
			$entityType = $entity->getType();

			// This constructs a "fake" title of the form Property:NewProperty,
			// where the title text is assumed to be name of the special page used
			// to create entities of the given type. This is used by the
			// LinkBeginHookHandler::doOnLinkBegin to replace the link to the
			// fake title with a link to the respective special page.
			// The effect is that e.g. the AbuseFilter log will show a link to
			// "Special:NewProperty" instead of "Property:NewProperty", while
			// the AbuseFilter itself will get a Title object with the correct
			// namespace IDs for Property entities.
			$namespace = $this->titleLookup->getNamespaceForType( $entityType );
			$title = Title::makeTitle( $namespace, 'New' . ucfirst( $entityType ) );
		}

		$context->setTitle( $title );
		$context->setWikiPage( new WikiPage( $title ) );

		return $context;
	}

}