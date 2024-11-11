'use strict';

const { Assertion, expect, util: utils } = require( 'chai' );
const util = require( 'util' );
const Ajv = require( 'ajv' );
const { readFileSync } = require( 'fs' );

function purple( str ) {
	return `\x1b[38;5;99m${str}`;
}

function normal( str ) {
	return `\x1b[0m${str}`;
}

function saveAndRestoreColor( str ) {
	return `\x1b7${str}\x1b8`;
}

function format( obj ) {
	const options = { depth: 4, colors: true };
	const formattedObj = util.inspect( obj, options );
	return normal( formattedObj );
}

/**
 * @param {string} url
 * @param {Array} paths
 * @return {string|undefined}
 */
function getMatchingSchemaPath( url, paths ) {
	const urlParts = url.split( '/' );

	pathLoop: for ( const path of paths ) {
		const pathParts = path.split( '/' );
		if ( urlParts.length !== pathParts.length ) {
			continue;
		}

		for ( const [ index, pathPart ] of pathParts.entries() ) {
			// skip comparison if pathPart is a path variable (e.g. {item_id})
			const regex = new RegExp( /^{.*}$/ );
			if ( regex.test( pathPart ) ) {
				continue;
			}

			// continue to next path if parts don't match
			if ( pathPart !== urlParts[ index ] ) {
				continue pathLoop;
			}
		}

		// return the path as all non-variable parts match
		return path;
	}
}

Assertion.addChainableMethod(
	'status',
	function ( code ) {
		this.equals( code );
	},
	function () {
		const response = utils.flag( this, 'object' );
		utils.flag( this, 'object', response.status );
		utils.flag( this, 'response', response );
		const formattedResponseBody = saveAndRestoreColor(
			`${purple( 'Response body:' )} ${format( response.body )}`
		);
		utils.flag( this, 'message', `\n${formattedResponseBody}\nInvalid status` );
	}
);

Assertion.addProperty(
	'satisfyApiSchema',
	function () {
		const openApiSchema = JSON.parse( readFileSync( `${__dirname}/../../../src/RouteHandlers/openapi.json` ) );

		const ajv = new Ajv( { strictTypes: false } );

		const response = utils.flag( this, 'response' ) || utils.flag( this, 'object' );
		utils.flag( this, 'response', response );
		const request = response.request;

		const requestUrl = request.url.split( 'wikibase/v1' )[ 1 ];
		const requestPath = getMatchingSchemaPath( requestUrl, Object.keys( openApiSchema.paths ) );

		// TODO: add better handling in case keys don't exist (might not be needed if we compile the whole schema)
		const openApiResponse = openApiSchema.paths[ requestPath ][ request.method.toLowerCase() ].responses[ response.status ];

		if ( Object.keys( response.body ).length > 0 ) {
			// TODO: add better handling in case keys don't exist (might not be needed if we compile the whole schema)
			const responseBodySchema = openApiResponse.content[ response.headers[ 'content-type' ] ].schema;
			// TODO: compile whole OpenAPI schema and then get the bit we want using `ajv.getSchema()`?
			// TODO: make reusable so we don't repeatedly compile the schema for each test?
			const validateBody = ajv.compile( responseBodySchema );
			if ( !validateBody( response.body ) ) {
				const error = validateBody.errors[ 0 ];
				this.assert( false, `${error.message} at '${error.instancePath}'` );
			}
		}

		if ( 'headers' in openApiResponse ) {
			for ( const [ header, openApiHeader ] of Object.entries( openApiResponse.headers ) ) {
				const schema = openApiHeader.schema;
				if ( header.toLowerCase() in response.headers ) {
					// TODO: compile whole OpenAPI schema and then get the bit we want using `ajv.getSchema()`?
					// TODO: make reusable so don't need to compile for each test?
					const validateHeader = ajv.compile( schema );
					if ( !validateHeader( response.headers[ header.toLowerCase() ] ) ) {
						const error = validateHeader.errors[ 0 ];
						this.assert( false, `'${header.toLowerCase()}' header doesn't match schema: ${error.message}` );
					}
				} else if ( openApiHeader.required ) {
					this.assert( false, `response does not contain required header '${header}'` );
				}
			}
		}

	}
);

module.exports = { expect };
