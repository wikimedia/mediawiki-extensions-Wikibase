<?php

namespace Wikibase\Repo\LinkedData;

use ApiMain;
use DerivativeContext;
use DerivativeRequest;
use RequestContext;
use Wikimedia\Purtle\RdfWriterFactory;

/**
 * Generates a list of formats supported from various sources.
 * Please note that this is not doing any (even in-process) caching and thus
 * should only be used carefully or with a cache (CachingEntityDataFormatAccessor)
 * on top.
 *
 * @see EntityDataFormatAccessor
 *
 * @since 0.5
 *
 * @license GNU GPL v2+
 * @author Marius Hoch < hoo@online.de >
 */
class EntityDataFormatProvider implements EntityDataFormatAccessor {

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

	/**
	 * @param RdfWriterFactory $rdfWriterFactory
	 *
	 * @since 0.5
	 */
	public function __construct( RdfWriterFactory $rdfWriterFactory ) {
		$this->rdfWriterFactory = $rdfWriterFactory;
	}

	/**
	 * @param array|null $whitelist List of allowed formats or null
	 *
	 * @return array Associative array from MIME type to format name
	 */
	public function getMimeTypes( array $whitelist = null ) {
		$this->initMimeTypes( $whitelist );

		return $this->mimeTypes;
	}

	/**
	 * @param array|null $whitelist List of allowed formats or null
	 *
	 * @return array Associative array from file extension to format name
	 */
	public function getFileExtensions( array $whitelist = null ) {
		$this->initFileExtensions( $whitelist );

		return $this->fileExtensions;
	}

	/**
	 * Initializes the internal mapping of MIME types
	 *
	 * @param array|null $whitelist List of allowed formats or null
	 */
	private function initMimeTypes( array $whitelist = null ) {
		$this->mimeTypes = array();

		$this->initApiMimeTypes( $whitelist );
		$this->initRdfWriterFactoryMimeTypes( $whitelist );
	}

	/**
	 * Initializes the internal mapping of file extensions to format names.
	 *
	 * @param array|null $whitelist List of allowed formats or null
	 */
	private function initFileExtensions( array $whitelist = null ) {
		$this->fileExtensions = array();

		$this->initApiFileExtensions( $whitelist );
		$this->initRdfWriterFactoryFileExtensions( $whitelist );
	}

	private function getApiFormatNames() {
		static $formatNames = null;

		if ( $formatNames === null ) {
			$api = $this->newApiMain( 'dummy' );
			$formatNames = $api->getModuleManager()->getNames( 'format' );
		}

		return $formatNames;
	}

	private function initApiMimeTypes( array $whitelist = null ) {
		foreach ( $this->getApiFormatNames() as $name ) {
			if ( $whitelist !== null && !in_array( $name, $whitelist ) ) {
				continue;
			}

			$mimes = $this->getApiMimeTypes( $name );

			foreach ( $mimes as $mime ) {
				if ( !isset( $this->mimeTypes[$mime] ) ) {
					$this->mimeTypes[$mime] = $name;
				}
			}
		}
	}

	private function initApiFileExtensions( array $whitelist = null ) {
		foreach ( $this->getApiFormatNames() as $name ) {
			if ( $whitelist !== null && !in_array( $name, $whitelist ) ) {
				continue;
			}

			$ext = $this->getApiFormatName( $name );
			$this->fileExtensions[$ext] = $name;
		}
	}

	private function initRdfWriterFactoryMimeTypes( array $whitelist = null ) {
		$formats = $this->rdfWriterFactory->getSupportedFormats();

		foreach ( $formats as $name ) {
			// Take the whitelist into account and don't override API formats
			if ( ( $whitelist !== null && !in_array( $name, $whitelist ) )
				|| in_array( $name, $this->mimeTypes )
			) {
				continue;
			}

			// use all mime types. to improve content negotiation
			foreach ( $this->rdfWriterFactory->getMimeTypes( $name ) as $mime ) {
				if ( !isset( $this->mimeTypes[$mime]) ) {
					$this->mimeTypes[$mime] = $name;
				}
			}
		}
	}

	private function initRdfWriterFactoryFileExtensions( array $whitelist = null ) {
		$formats = $this->rdfWriterFactory->getSupportedFormats();

		foreach ( $formats as $name ) {
			// Take the whitelist into account and don't override API formats
			if ( ( $whitelist !== null && !in_array( $name, $whitelist ) )
				|| in_array( $name, $this->fileExtensions ) ) {
				continue;
			}

			// only one file extension, to keep purging simple
			$ext = $this->rdfWriterFactory->getFileExtension( $name );
			if ( !isset( $this->fileExtensions[$ext] ) ) {
				$this->fileExtensions[$ext] = $name;
			}
		}
	}

	/**
	 * Normalizes the format specifier; Converts mime types to API format names.
	 *
	 * @param String $format the format as supplied in the request
	 *
	 * @return String|null the normalized format name, or null if the format is unknown
	 */
	private function getApiFormatName( $format ) {
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
	 * @param String $format the API format name
	 *
	 * @return String[]|null the MIME types for the given format
	 */
	private function getApiMimeTypes( $format ) {
		$format = trim( strtolower( $format ) );

		switch ( $format ) {
			case 'php':
				return array( 'application/vnd.php.serialized' );

			case 'txt':
				return array( "text/text", "text/plain" );

			case 'javascript':
				return array( "text/javascript" );

			default:
				return array( "application/$format" );
		}
	}

	/**
	 * Returns an ApiMain module that acts as a context for the formatting and serialization.
	 *
	 * @param String $format The desired output format, as a format name that ApiBase understands.
	 *
	 * @return ApiMain
	 */
	private function newApiMain( $format ) {
		// Fake request params to ApiMain, with forced format parameters.
		// We can override additional parameters here, as needed.
		$params = array(
			'format' => $format,
		);

		$context = new DerivativeContext( RequestContext::getMain() ); //XXX: ugly

		$req = new DerivativeRequest( $context->getRequest(), $params );
		$context->setRequest( $req );

		return new ApiMain( $context );
	}

}
