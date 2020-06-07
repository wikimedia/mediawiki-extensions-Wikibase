<?php

namespace Wikibase\Repo\LinkedData;

use ApiMain;
use DerivativeContext;
use DerivativeRequest;
use RequestContext;
use Wikimedia\Purtle\RdfWriterFactory;

/**
 * Service for getting information about supported data formats.
 *
 * @license GPL-2.0-or-later
 */
class EntityDataFormatProvider {

	/**
	 * List of allowed formats. If non-null, only formats listed here are allowed.
	 *
	 * @var string[]|null
	 */
	private $allowedFormats = null;

	/**
	 * @var null|array Associative array from MIME type to format name
	 * @note: initialized by initFormats()
	 */
	private $mimeTypes = null;

	/**
	 * @var null|array Associative array from file extension to format name
	 * @note: initialized by initFormats()
	 */
	private $fileExtensions = null;

	/**
	 * @var RdfWriterFactory
	 */
	private $rdfWriterFactory;

	public function __construct() {
		$this->rdfWriterFactory = new RdfWriterFactory();
	}

	/**
	 * @param string[]|null $allowedFormats
	 */
	public function setAllowedFormats( array $allowedFormats = null ) {
		$this->allowedFormats = $allowedFormats;

		// force re-init of format maps
		$this->fileExtensions = null;
		$this->mimeTypes = null;
	}

	/**
	 * @return string[]|null
	 */
	public function getAllowedFormats() {
		return $this->allowedFormats;
	}

	/**
	 * Returns the list of supported MIME types that can be used to specify the
	 * output format.
	 *
	 * @return string[]
	 */
	public function getSupportedMimeTypes() {
		$this->initFormats();

		return array_keys( $this->mimeTypes );
	}

	/**
	 * Returns the list of supported file extensions that can be used
	 * to specify a format.
	 *
	 * @return string[]
	 */
	public function getSupportedExtensions() {
		$this->initFormats();

		return array_keys( $this->fileExtensions );
	}

	/**
	 * Returns the list of supported formats using their canonical names.
	 *
	 * @return string[]
	 */
	public function getSupportedFormats() {
		$this->initFormats();

		return array_unique( array_merge(
			array_values( $this->mimeTypes ),
			array_values( $this->fileExtensions )
		) );
	}

	/**
	 * Returns a canonical format name. Used to normalize the format identifier.
	 *
	 * @param string $format the format as a file extension or MIME type.
	 *
	 * @return string|null the canonical format name, or null of the format is not supported
	 */
	public function getFormatName( $format ) {
		$this->initFormats();

		$format = trim( strtolower( $format ) );

		if ( array_key_exists( $format, $this->mimeTypes ) ) {
			return $this->mimeTypes[$format];
		}

		if ( array_key_exists( $format, $this->fileExtensions ) ) {
			return $this->fileExtensions[$format];
		}

		if ( in_array( $format, $this->mimeTypes ) ) {
			return $format;
		}

		if ( in_array( $format, $this->fileExtensions ) ) {
			return $format;
		}

		return null;
	}

	/**
	 * Returns a file extension suitable for $format, or null if no such extension is known.
	 *
	 * @param string $format A canonical format name, as returned by getFormatName() or getSupportedFormats().
	 *
	 * @return string|null
	 */
	public function getExtension( $format ) {
		$this->initFormats();

		$ext = array_search( $format, $this->fileExtensions );
		return $ext === false ? null : $ext;
	}

	/**
	 * Returns a MIME type suitable for $format, or null if no such extension is known.
	 *
	 * @param string $format A canonical format name, as returned by getFormatName() or getSupportedFormats().
	 *
	 * @return string|null
	 */
	public function getMimeType( $format ) {
		$this->initFormats();

		$type = array_search( $format, $this->mimeTypes );

		return $type === false ? null : $type;
	}

	/**
	 * Initializes the internal mapping of MIME types and file extensions to format names.
	 */
	private function initFormats() {
		if ( $this->mimeTypes !== null && $this->fileExtensions !== null ) {
			return;
		}

		$this->mimeTypes = [];
		$this->fileExtensions = [];

		$api = $this->newApiMain( "dummy" );
		$formatNames = $api->getModuleManager()->getNames( 'format' );

		foreach ( $formatNames as $name ) {
			if ( $this->allowedFormats !== null && !in_array( $name, $this->allowedFormats ) ) {
				continue;
			}

			$mimes = self::getApiMimeTypes( $name );
			$ext = self::getApiFormatName( $name );

			foreach ( $mimes as $mime ) {
				if ( !isset( $this->mimeTypes[$mime] ) ) {
					$this->mimeTypes[$mime] = $name;
				}
			}

			$this->fileExtensions[ $ext ] = $name;
		}

		$formats = $this->rdfWriterFactory->getSupportedFormats();

		foreach ( $formats as $name ) {
			// check allowed formats, and don't override API formats
			if ( ( $this->allowedFormats !== null
					&& !in_array( $name, $this->allowedFormats ) )
				|| in_array( $name, $this->mimeTypes )
				|| in_array( $name, $this->fileExtensions )
			) {
				continue;
			}

			// use all mime types. to improve content negotiation
			foreach ( $this->rdfWriterFactory->getMimeTypes( $name ) as $mime ) {
				if ( !isset( $this->mimeTypes[$mime] ) ) {
					$this->mimeTypes[$mime] = $name;
				}
			}

			// only one file extension, to keep purging simple
			$ext = $this->rdfWriterFactory->getFileExtension( $name );
			if ( !isset( $this->fileExtensions[$ext] ) ) {
				$this->fileExtensions[$ext] = $name;
			}
		}
	}

	/**
	 * Does $format specify a name for RDF format?
	 * @param string $format
	 * @return bool
	 */
	public function isRdfFormat( $format ) {
		$this->initFormats();
		$formats = $this->rdfWriterFactory->getSupportedFormats();
		if ( in_array( $format, $formats ) ) {
			// Try direct name
			return true;
		}
		if ( isset( $this->fileExtensions[$format] ) &&
			in_array( $this->fileExtensions[$format], $formats ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Normalizes the format specifier; Converts mime types to API format names.
	 *
	 * @param string $format The format as supplied in the request
	 *
	 * @return string|null The normalized format name, or null if the format is unknown
	 */
	private static function getApiFormatName( $format ) {
		$format = trim( strtolower( $format ) );

		if ( $format === 'application/vnd.php.serialized' ) {
			$format = 'php';
		} elseif ( $format === 'text/text' || $format === 'text/plain' ) {
			$format = 'txt';
		} else {
			// hack: just trip the major part of the mime type
			$format = preg_replace( '@^(text|application)?/@', '', $format );
		}

		return $format;
	}

	/**
	 * Converts API format names to MIME types.
	 *
	 * @param string $format The API format name
	 *
	 * @return string[]|null The MIME types for the given format
	 */
	private static function getApiMimeTypes( $format ) {
		$format = trim( strtolower( $format ) );
		$type = null;

		switch ( $format ) {
			case 'php':
				return [ 'application/vnd.php.serialized' ];

			case 'txt':
				return [ "text/text", "text/plain" ];

			case 'javascript':
				return [ "text/javascript" ];

			default:
				return [ "application/$format" ];
		}
	}

	/**
	 * Returns an ApiMain module that acts as a context for the formatting and serialization.
	 *
	 * @param string $format The desired output format, as a format name that ApiBase understands.
	 *
	 * @return ApiMain
	 */
	private function newApiMain( $format ) {
		// Fake request params to ApiMain, with forced format parameters.
		// We can override additional parameters here, as needed.
		$params = [
			'format' => $format,
		];

		$context = new DerivativeContext( RequestContext::getMain() ); //XXX: ugly

		$req = new DerivativeRequest( $context->getRequest(), $params );
		$context->setRequest( $req );

		$api = new ApiMain( $context );
		return $api;
	}

}
