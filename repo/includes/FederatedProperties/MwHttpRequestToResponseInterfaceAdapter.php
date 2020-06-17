<?php

declare( strict_types = 1 );
namespace Wikibase\Repo\FederatedProperties;

use LogicException;
use MWHttpRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use function GuzzleHttp\Psr7\stream_for;

/**
 * @license GPL-2.0-or-later
 */
class MwHttpRequestToResponseInterfaceAdapter implements ResponseInterface {

	/**
	 * @var MWHttpRequest
	 */
	private $mwHttpRequest;

	/**
	 * @param MWHttpRequest $mwHttpRequest the MWHttpRequest must contain response information, i.e. must have been `execute`d
	 */
	public function __construct( MWHttpRequest $mwHttpRequest ) {
		$this->validateHasResponse( $mwHttpRequest );
		$this->mwHttpRequest = $mwHttpRequest;
	}

	public function getProtocolVersion() {
		// This is not accessible via MWHttpRequest, but it is set in its protected `respVersion` property.
		// If this is ever needed, it can get exposed in MWHttpRequest.
		throw new LogicException( __METHOD__ . ' is not implemented' );
	}

	// @phan-suppress-next-line PhanTypeMissingReturn
	public function withProtocolVersion( $version ) {
		$this->throwExceptionForBuilderMethod( __METHOD__ );
	}

	public function getHeaders() {
		return $this->mwHttpRequest->getResponseHeaders();
	}

	public function hasHeader( $name ) {
		return isset( $this->mwHttpRequest->getResponseHeaders()[$name] );
	}

	public function getHeader( $name ) {
		return $this->hasHeader( $name ) ? $this->mwHttpRequest->getResponseHeaders()[$name] : [];
	}

	public function getHeaderLine( $name ) {
		return $this->hasHeader( $name )
			? implode( ',', $this->mwHttpRequest->getResponseHeaders()[$name] )
			: '';
	}

	// @phan-suppress-next-line PhanTypeMissingReturn
	public function withHeader( $name, $value ) {
		$this->throwExceptionForBuilderMethod( __METHOD__ );
	}

	// @phan-suppress-next-line PhanTypeMissingReturn
	public function withAddedHeader( $name, $value ) {
		$this->throwExceptionForBuilderMethod( __METHOD__ );
	}

	// @phan-suppress-next-line PhanTypeMissingReturn
	public function withoutHeader( $name ) {
		$this->throwExceptionForBuilderMethod( __METHOD__ );
	}

	public function getBody() {
		return stream_for( $this->mwHttpRequest->getContent() );
	}

	// @phan-suppress-next-line PhanTypeMissingReturn
	public function withBody( StreamInterface $body ) {
		$this->throwExceptionForBuilderMethod( __METHOD__ );
	}

	public function getStatusCode() {
		return $this->mwHttpRequest->getStatus();
	}

	// @phan-suppress-next-line PhanTypeMissingReturn
	public function withStatus( $code, $reasonPhrase = '' ) {
		$this->throwExceptionForBuilderMethod( __METHOD__ );
	}

	public function getReasonPhrase() {
		return ''; // not exposed through MWHttpRequest, unlikely to ever be useful
	}

	private function throwExceptionForBuilderMethod( string $method ) {
		throw new LogicException( "Builder method $method is not supported." );
	}

	private function validateHasResponse( MWHttpRequest $mwHttpRequest ) {
		// MWHttpRequest objects contain request information, but also contain response information after calling `execute`.
		// The best way of determining whether a MWHttpRequest contains response information is to check whether its headers list is empty.
		if ( empty( $mwHttpRequest->getResponseHeaders() ) ) {
			throw new LogicException( 'Trying to get response information from a request that was not yet executed' );
		}
	}
}
