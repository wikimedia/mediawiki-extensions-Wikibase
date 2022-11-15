'use strict';

const { assert, clientFactory } = require( 'api-testing' );
const SwaggerParser = require( '@apidevtools/swagger-parser' );
const { default: OpenAPIRequestCoercer } = require( 'openapi-request-coercer' );
const { default: OpenAPIRequestValidator } = require( 'openapi-request-validator' );

const basePath = 'rest.php/wikibase/v0';

// "static" because it can be shared across requests, and we don't want to dereference it every time
let openApiSpec = null;

async function getOrLoadSpec() {
	openApiSpec = openApiSpec || await SwaggerParser.dereference( './specs/openapi.json' );

	return openApiSpec;
}

class RequestBuilder {

	constructor() {
		this.route = null;
		this.method = null;
		this.pathParams = {};
		this.queryParams = {};
		this.jsonBodyParams = {};
		this.headers = { 'user-agent': 'e2e tests' };
		this.user = null;
		this.validate = false;
		this.assertValid = false;
	}

	/**
	 * @param {string} method HTTP method to use for the request
	 * @param {string} route the route as it appears in the spec, e.g. '/entities/items/{item_id}'
	 * @return {this}
	 */
	withRoute( method, route ) {
		this.method = method;
		this.route = route;
		return this;
	}

	/**
	 * @param {string} name path param name, e.g. 'item_id' for /entities/items/{item_id}
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

	withJsonBodyParam( name, value ) {
		this.headers[ 'content-type' ] = 'application/json';
		this.jsonBodyParams[ name ] = value;
		return this;
	}

	withHeader( name, value ) {
		this.headers[ name.toLowerCase() ] = value;
		return this;
	}

	/**
	 * @param {Object} user e.g. `await action.mindy()`
	 * @return {this}
	 */
	withUser( user ) {
		this.user = user;
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

	async makeRequest() {
		const XDEBUG_SESSION = process.env.XDEBUG_SESSION;
		if ( XDEBUG_SESSION ) {
			this.withHeader( 'Cookie', `XDEBUG_SESSION=${XDEBUG_SESSION}` );
		}

		const spec = await getOrLoadSpec();
		this.validateRouteAndMethod( spec );
		if ( this.validate ) {
			this.validateRequest( spec );
		}

		let body = null;
		switch ( this.headers[ 'content-type' ] ) {
			case 'multipart/form-data':
				body = new URLSearchParams( this.jsonBodyParams ).toString();
				break;
			case 'application/json':
			case 'application/json-patch+json':
				body = this.jsonBodyParams;
				break;
		}

		const rest = clientFactory.getRESTClient( basePath, this.user );

		switch ( this.method.toUpperCase() ) {
			case 'GET':
				return rest.request( this.makePath(), this.method, this.queryParams, this.headers );
			case 'POST':
			case 'PUT':
			case 'PATCH':
			case 'DELETE':
				return rest.req[ this.method.toLowerCase() ]( basePath + this.makePath() )
					.set( this.headers )
					.query( this.queryParams )
					.send( body );
			default:
				throw new Error( `The "${this.method}" method is not supported by ${this.constructor.name}` );
		}

	}

	validateRouteAndMethod( spec ) {
		if ( !this.method ) {
			throw new Error( 'No HTTP method provided.' );
		}
		if ( !this.route ) {
			throw new Error( 'No route provided.' );
		}
		if ( !spec.paths[ this.route ] ) {
			throw new Error( `The route "${this.route}" does not exist in the spec.` );
		}
		if ( !spec.paths[ this.route ][ this.method.toLowerCase() ] ) {
			throw new Error( `The route "${this.route}" does not allow method "${this.method}".` );
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

	validateRequest( spec ) {
		const requestSpec = spec.paths[ this.route ][ this.method.toLowerCase() ];
		const specParameters = { parameters: requestSpec.parameters };
		// copy, since the unchanged request is still needed
		const coercedRequest = JSON.parse( JSON.stringify( {
			endpoint: this.route,
			params: this.pathParams,
			query: this.queryParams,
			body: this.jsonBodyParams,
			headers: this.headers
		} ) );

		new OpenAPIRequestCoercer( specParameters ).coerce( coercedRequest );

		const errors = new OpenAPIRequestValidator( requestSpec ).validateRequest( coercedRequest );

		if ( this.assertValid ) {
			let errorMessage = '';

			if ( typeof errors !== 'undefined' ) {
				const error = errors.errors[ 0 ];
				errorMessage = `[${error.errorCode}] ${error.path} ${error.message} in '${error.location}'`;
			}
			assert.isUndefined( errors, errorMessage );
		} else {
			assert.isDefined( errors );
		}
	}

	getRouteDescription() {
		return `${this.method} ${this.route}`;
	}

}

module.exports = { RequestBuilder };
