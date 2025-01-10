'use strict';

const { Assertion, expect, util: utils } = require( 'chai' );
const util = require( 'util' );
const Ajv = require( 'ajv' );
const { readFileSync } = require( 'fs' );

function compileSchemaAndValidator() {
	const openApiSchema = JSON.parse(
		readFileSync( `${__dirname}/../../../src/RouteHandlers/openapi.json` )
	);

	const schemaValidator = new Ajv( { strictTypes: false } );

	Object.entries( openApiSchema.paths ).forEach( ( [ path, pathData ] ) => {
		Object.entries( pathData ).forEach( ( [ method, methodData ] ) => {
			Object.entries( methodData.responses ).forEach( ( [ status, responseData ] ) => {
				if ( responseData.content ) {
					Object.entries( responseData.content ).forEach( ( [ contentType, content ] ) => {
						schemaValidator.addSchema( content.schema, `${path}|${method}|${status}|${contentType}` );
					} );
				}
				if ( responseData.headers ) {
					Object.entries( responseData.headers ).forEach( ( [ header, headerData ] ) => {
						schemaValidator.addSchema(
							headerData.schema,
							`${path}|${method}|${status}|header|${header.toLowerCase()}`
						);
					} );
				}
			} );
		} );
	} );

	return { openApiSchema, schemaValidator };
}

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

function buildSatisfyApiSchema( { openApiSchema, schemaValidator } ) {
	return function () {
		const response = utils.flag( this, 'response' ) || utils.flag( this, 'object' );
		utils.flag( this, 'response', response );
		const request = response.request;

		const requestUrl = request.url.split( 'wikibase' )[ 1 ];
		const requestPath = getMatchingSchemaPath( requestUrl, Object.keys( openApiSchema.paths ) );
		const requestMethod = request.method.toLowerCase();
		const responseStatus = response.status;
		const openApiResponse = openApiSchema.paths[ requestPath ][ requestMethod ].responses[ responseStatus ];

		if ( Object.keys( response.body ).length > 0 ) {
			const contentType = response.headers[ 'content-type' ];
			const schemaKey = `${requestPath}|${requestMethod}|${responseStatus}|${contentType}`;

			const validateBody = schemaValidator.getSchema( schemaKey );
			if ( !validateBody ) {
				throw new Error( `Schema not found for ${schemaKey} in OpenAPI schema.` );
			}

			if ( !validateBody( response.body ) ) {
				const error = validateBody.errors[ 0 ];
				this.assert( false, `${error.message} at '${error.instancePath}'` );
			}
		}

		if ( 'headers' in openApiResponse ) {
			for ( const [ header, openApiHeader ] of Object.entries( openApiResponse.headers ) ) {
				const headerKey = `${requestPath}|${requestMethod}|${responseStatus}|header|${header.toLowerCase()}`;

				const validateHeader = schemaValidator.getSchema( headerKey );
				if ( !validateHeader ) {
					throw new Error( `Schema not found for header '${header}' in OpenAPI schema.` );
				}

				if ( header.toLowerCase() in response.headers ) {
					if ( !validateHeader( response.headers[ header.toLowerCase() ] ) ) {
						const error = validateHeader.errors[ 0 ];
						this.assert(
							false,
							`'${header.toLowerCase()}' header doesn't match schema: ${error.message}`
						);
					}
				} else if ( openApiHeader.required ) {
					this.assert( false, `response does not contain required header '${header}'` );
				}
			}
		}
	};
}

Assertion.addProperty( 'satisfyApiSchema', buildSatisfyApiSchema( compileSchemaAndValidator() ) );

module.exports = { expect };
