'use strict';

const { REST, assert } = require( 'api-testing' );
const SwaggerParser = require( '@apidevtools/swagger-parser' );
const { default: OpenAPIRequestCoercer } = require( 'openapi-request-coercer' );
const { default: OpenAPIRequestValidator } = require( 'openapi-request-validator' );

const basePath = 'rest.php/wikibase/v0';
const rest = new REST( basePath );

// "static" because it can be shared across requests, and we don't want to dereference it every time
let openApiSpec = null;

async function getOrLoadSpec() {
	openApiSpec = openApiSpec || await SwaggerParser.dereference( './specs/openapi.json' );

	return openApiSpec;
}

class RequestBuilder {

	constructor() {
		this.route = null;
		this.pathParams = {};
		this.queryParams = {};
		this.headers = {};
		this.validate = false;
		this.assertValid = false;
	}

	/**
	 * @param {string} route the route as it appears in the spec, e.g. '/entities/items/{entity_id}'
	 * @return {this}
	 */
	withRoute( route ) {
		this.route = route;
		return this;
	}

	/**
	 * @param {string} name path param name, e.g. 'entity_id' for /entities/items/{entity_id}
	 * @param {string} value
	 * @return {this}
	 */
	withPathParam( name, value ) {
		this.pathParams[ name ] = value;
		return this;
	}

	withQueryParam( name, value ) {
		this.queryParams[ name ] = value;
		return this;
	}

	withHeader( name, value ) {
		this.headers[ name ] = value;
		return this;
	}

	assertValidRequest() {
		this.validate = true;
		this.assertValid = true;
		return this;
	}

	assertInvalidRequest() {
		this.validate = true;
		this.assertValid = false;
		return this;
	}

	async makeRequest( method = 'GET' ) {
		const spec = await getOrLoadSpec();
		this.validateRouteAndMethod( spec, method );
		if ( this.validate ) {
			this.validateRequest( spec, method );
		}

		return rest.request( this.makePath(), method, this.queryParams, this.headers );
	}

	validateRouteAndMethod( spec, method ) {
		if ( !this.route ) {
			throw new Error( 'No route provided.' );
		}
		if ( !spec.paths[ this.route ] ) {
			throw new Error( `The route "${this.route}" does not exist in the spec.` );
		}
		if ( !spec.paths[ this.route ][ method.toLowerCase() ] ) {
			throw new Error( `The route "${this.route}" does not allow method "${method}".` );
		}
	}

	makePath() {
		let path = this.route;
		Object.keys( this.pathParams ).forEach( ( param ) => {
			path = path.replace( `{${param}}`, this.pathParams[ param ] );
		} );

		if ( path.includes( '{' ) ) { // feels a bit hacky but should be ok?!
			throw new Error(
				`Path params "${JSON.stringify( this.pathParams )}" do not set all params in "${this.route}".`
			);
		}

		return path;
	}

	validateRequest( spec, method ) {
		const requestSpec = spec.paths[ this.route ][ method.toLowerCase() ];
		const specParameters = { parameters: requestSpec.parameters };
		// copy, since the unchanged request is still needed
		const coercedRequest = JSON.parse( JSON.stringify( {
			endpoint: this.route,
			params: this.pathParams,
			query: this.queryParams,
			headers: this.headers
		} ) );

		new OpenAPIRequestCoercer( specParameters ).coerce( coercedRequest );

		const errors = new OpenAPIRequestValidator( requestSpec ).validateRequest( coercedRequest );

		if ( this.assertValid ) {
			let errorMessage = '';

			if ( typeof errors !== 'undefined' ) {
				const error = errors.errors[ 0 ];
				errorMessage = `[${error.errorCode}] ${error.path} ${error.message} in ${error.location}`;
			}
			assert.isUndefined( errors, errorMessage );
		} else {
			assert.isDefined( errors );
		}
	}

}

module.exports = { RequestBuilder };
