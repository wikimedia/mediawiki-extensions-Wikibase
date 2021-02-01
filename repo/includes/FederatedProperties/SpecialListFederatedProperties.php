<?php

declare( strict_types = 1 );
namespace Wikibase\Repo\FederatedProperties;

use OOUI\HtmlSnippet;
use OOUI\MessageWidget;
use Wikibase\Repo\Specials\SpecialWikibasePage;

/**
 * Special page to list properties by data type
 *
 * @license GPL-2.0-or-later
 * @author sihe
 */
class SpecialListFederatedProperties extends SpecialWikibasePage {

	/**
	 * @var string
	 */
	private $scriptUrl;

	public function __construct( string $scriptUrl ) {
		parent::__construct( 'ListProperties' );
		$this->scriptUrl = $scriptUrl;
	}

	/**
	 * @see SpecialPage::execute
	 *
	 * @param string|null $subPage
	 */
	public function execute( $subPage ) {
		parent::execute( $subPage );
		$this->getOutput()->enableOOUI();
		$this->getOutput()->addHTML( $this->htmlNoticeWidget( $subPage ) );
	}

	private function htmlNoticeWidget( ?string $dataTypeId ): string {
		return ( new MessageWidget( [ 'label' => new HtmlSnippet( $this->htmlNoticeLabel( $dataTypeId ) ) ] ) )->toString();
	}

	private function htmlNoticeLabel( ?string $dataTypeId ): string {
		return wfMessage( 'wikibase-federated-properties-special-list-of-properties-notice' )
			->rawParams( $this->htmlLinkToRemotePage( $dataTypeId ) )
			->escaped();
	}

	private function htmlLinkToRemotePage( ?string $dataTypeId ): string {
		$specialPage = 'Special:ListProperties';
		if ( $dataTypeId ) {
			$specialPage .= '/' . wfEscapeWikiText( $dataTypeId );
		}

		return wfMessage(
			'wikibase-federated-properties-special-list-of-properties-source-ref',
			$this->scriptUrl . 'index.php?title=' . $specialPage,   // the href target URL
			parse_url( $this->scriptUrl, PHP_URL_HOST )           // the source domain name as displayed in the message
		)->parse();
	}

}
