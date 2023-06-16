<?php

declare( strict_types = 1 );
namespace Wikibase\Repo\FederatedProperties;

use ErrorPageError;
use Html;
use RawMessage;
use Wikibase\DataModel\Term\LabelsProvider;
use Wikibase\View\Template\TemplateFactory;

/**
 * An error page used for showing API errors for a specific entity using Federated Properties.
 *
 * @ingroup Wikibase
 *
 * @license GPL-2.0-or-later
 */
class FederatedPropertiesError extends ErrorPageError {

	/**
	 * @param LabelsProvider $entity
	 * @param string $msg Message key (string) for error text
	 * @param array $params Array with parameters to wfMessage()
	 *
	 * @suppress SecurityCheck-DoubleEscaped
	 */
	public function __construct( $languageCode, $entity, $msg, $params = [] ) {

		$templateFactory = TemplateFactory::getDefaultInstance();
		$errorBody = new RawMessage( Html::errorBox( wfMessage( $msg, $params )->parse() ) );

		// @phan-suppress-next-line PhanUndeclaredMethod Phan is confused by intersection types
		$entityId = $entity->getId()->getSerialization();
		$hasLabel = $entity->getLabels()->hasTermForLanguage( $languageCode );
		$labelText = '';

		if ( $hasLabel ) {
			$labelText = $entity->getLabels()->getByLanguage( $languageCode )->getText();
		}

		$idInParenthesesHtml = htmlspecialchars( wfMessage( 'parentheses', [ $entityId ] )->parse() );

		$html = $templateFactory->render( 'wikibase-title',
			!$hasLabel ? 'wb-empty' : '',
			!$hasLabel ? wfMessage( 'wikibase-label-empty' )->parse() : htmlspecialchars( $labelText, ENT_QUOTES ),
			$idInParenthesesHtml
		);

		parent::__construct( new RawMessage( $html ), $errorBody, [] );
	}

	public function report( $action = self::SEND_OUTPUT ) {
		if ( self::isCommandLine() || defined( 'MW_API' ) ) {
			parent::report();
		} else {
			global $wgOut;
			$wgOut->addModuleStyles( [ 'wikibase.alltargets', 'wikibase.desktop' ] );
			parent::report( $action );
		}
	}
}
