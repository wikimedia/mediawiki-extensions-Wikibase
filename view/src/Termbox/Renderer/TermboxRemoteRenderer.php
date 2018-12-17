<?php

namespace Wikibase\View\Termbox\Renderer;

use Exception;
use MediaWiki\Http\HttpRequestFactory;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\View\SpecialPageLinker;

/**
 * @license GPL-2.0-or-later
 */
class TermboxRemoteRenderer implements TermboxRenderer {

	private $requestFactory;
	private $specialPageLinker;
	private $ssrServerUrl;

	/* public */ const HTTP_STATUS_OK = 200;
	/* public */ const EDIT_PAGE = 'SetLabelDescriptionAliases';

	public function __construct(
		HttpRequestFactory $requestFactory,
		$ssrServerUrl,
		SpecialPageLinker $specialPageLinker
	) {
		$this->requestFactory = $requestFactory;
		$this->ssrServerUrl = $ssrServerUrl;
		$this->specialPageLinker = $specialPageLinker;
	}

	/**
	 * @inheritDoc
	 */
	public function getContent( EntityId $entityId, $language ) {
		try {
			$request = $this->requestFactory->create(
				$this->formatUrl( $entityId, $language ),
				[ /* TODO attach required data */ ]
			);
			$request->execute();
		} catch ( Exception $e ) {
			throw new TermboxRenderingException( 'Encountered request problem', null, $e );
		}

		$status = $request->getStatus();
		if ( $status !== self::HTTP_STATUS_OK ) {
			throw new TermboxRenderingException( 'Encountered bad response: ' . $status );
		}

		return $request->getContent();
	}

	private function formatUrl( EntityId $entityId, $language ) {
		return $this->ssrServerUrl . '?' .
			http_build_query( $this->getRequestParams( $entityId, $language ) );
	}

	private function getRequestParams( EntityId $entityId, $language ) {
		return [
			'entity' => $entityId->getSerialization(),
			'language' => $language,
			'editLink' => $this->specialPageLinker->getLink(
				self::EDIT_PAGE,
				[ $entityId->getSerialization() ]
			),
		];
	}

}
