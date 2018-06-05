<?php

namespace Wikibase\Repo\Hooks;

use DerivativeContext;
use Hooks;
use IContextSource;
use InvalidArgumentException;
use MutableContext;
use RuntimeException;
use Status;
use Title;
use User;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Repo\Store\EntityTitleStoreLookup;
use Wikibase\Repo\Content\EntityContentFactory;
use WikiPage;

/**
 * Class to run the EditFilterMergedContent hook
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 */
class EditFilterHookRunner {

	/**
	 * @var EntityNamespaceLookup
	 */
	private $namespaceLookup;

	/**
	 * @var EntityTitleStoreLookup
	 */
	private $titleLookup;

	/**
	 * @var EntityContentFactory
	 */
	private $entityContentFactory;

	/**
	 * @var MutableContext
	 */
	private $context;

	public function __construct(
		EntityNamespaceLookup $namespaceLookup,
		EntityTitleStoreLookup $titleLookup,
		EntityContentFactory $entityContentFactory,
		IContextSource $context
	) {
		if ( !( $context instanceof MutableContext ) ) {
			wfLogWarning( '$context is not an instanceof MutableContext.' );

			$context = new DerivativeContext( $context );
		}

		$this->namespaceLookup = $namespaceLookup;
		$this->titleLookup = $titleLookup;
		$this->entityContentFactory = $entityContentFactory;
		$this->context = $context;
	}

	/**
	 * Call EditFilterMergedContent hook, if registered.
	 *
	 * @param EntityDocument|EntityRedirect|null $new The entity or redirect we are trying to save
	 * @param User $user the user performing the edit
	 * @param string $summary The edit summary
	 *
	 * @throws RuntimeException
	 * @throws InvalidArgumentException
	 * @return Status
	 */
	public function run( $new, User $user, $summary ) {
		$filterStatus = Status::newGood();

		if ( !Hooks::isRegistered( 'EditFilterMergedContent' ) ) {
			return $filterStatus;
		}

		if ( $new instanceof EntityDocument ) {
			$entityContent = $this->entityContentFactory->newFromEntity( $new );
			$context = $this->getContextForEditFilter( $new->getId(), $new->getType() );

		} elseif ( $new instanceof EntityRedirect ) {
			$entityContent = $this->entityContentFactory->newFromRedirect( $new );
			if ( $entityContent === null ) {
				throw new RuntimeException(
					'Cannot get EntityContent from EntityRedirect of type ' .
					$new->getEntityId()->getEntityType()
				);
			}

			$context = $this->getContextForEditFilter(
				$new->getEntityId(),
				$new->getEntityId()->getEntityType()
			);
		} else {
			throw new InvalidArgumentException( '$new must be instance of EntityDocument or EntityRedirect' );
		}

		if ( !Hooks::run(
			'EditFilterMergedContent',
			[ $context, $entityContent, &$filterStatus, $summary, $user, false ]
		) ) {
			// Error messages etc. were handled inside the hook.
			$filterStatus->setResult( false, $filterStatus->getValue() );
		}

		return $filterStatus;
	}

	/**
	 * @param EntityId|null $entityId
	 * @param string $entityType
	 *
	 * @return MutableContext
	 */
	private function getContextForEditFilter( EntityId $entityId = null, $entityType ) {
		$context = clone $this->context;
		if ( $entityId !== null ) {
			$title = $this->titleLookup->getTitleForId( $entityId );
		} else {
			// This constructs a "fake" title of the form Property:NewProperty,
			// where the title text is assumed to be name of the special page used
			// to create entities of the given type. This is used by the
			// HtmlPageLinkRendererBeginHookHandler::doHtmlPageLinkRendererBegin to replace
			// the link to the fake title with a link to the respective special page.
			// The effect is that e.g. the AbuseFilter log will show a link to
			// "Special:NewProperty" instead of "Property:NewProperty", while
			// the AbuseFilter itself will get a Title object with the correct
			// namespace IDs for Property entities.
			$namespace = $this->namespaceLookup->getEntityNamespace( $entityType );
			$title = Title::makeTitle( $namespace, 'New' . ucfirst( $entityType ) );
		}

		$context->setTitle( $title );
		$context->setWikiPage( new WikiPage( $title ) );

		return $context;
	}

}
