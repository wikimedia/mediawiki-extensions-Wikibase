'use strict';

/* eslint-disable security/detect-non-literal-fs-filename */
const fs = require( 'fs' );
const path = require( 'path' );

function findSchemasFiles() {
	const domainsPath = path.join( __dirname, '../../domains' );
	if ( !fs.existsSync( domainsPath ) ) {
		throw new Error( `Domains directory not found at: ${ domainsPath}` );
	}

	const schemaFiles = [];
	const domains = fs.readdirSync( domainsPath )
		.filter( ( item ) => fs.statSync( path.join( domainsPath, item ) ).isDirectory() );

	domains.forEach( ( domain ) => {
		const specsPath = path.join( domainsPath, domain, 'specs' );
		if ( !fs.existsSync( specsPath ) ) {
			return;
		}

		const searchForSchemas = ( dir ) => {
			const specsFiles = fs.readdirSync( dir );
			specsFiles.forEach( ( item ) => {
				const fullPath = path.join( dir, item );
				const stat = fs.statSync( fullPath );

				if ( stat.isDirectory() ) {
					searchForSchemas( fullPath );
				} else if ( item === 'schemas.json' ) {
					schemaFiles.push( fullPath );
				}
			} );
		};

		searchForSchemas( specsPath );
	} );

	if ( schemaFiles.length === 0 ) {
		throw new Error( 'No schemas.json file found in any specs directory' );
	}

	return schemaFiles;
}

function loadSchemas() {
	const schemaFiles = findSchemasFiles();
	const allSchemas = {};

	schemaFiles.forEach( ( source ) => {
		const schemas = JSON.parse( ( fs.readFileSync( source, 'utf8' ) ) );
		Object.assign( allSchemas, schemas );
	} );

	return allSchemas;
}

module.exports = function () {
	return {
		id: 'filterSchemas',
		decorators: {
			oas3: {
				filterSchemas: function () {
					const validSchemas = loadSchemas();
					return {
						Components: {
							leave( components ) {
								for ( const key in components.schemas || {} ) {
									if ( validSchemas[ key ] === undefined ) {
										delete components.schemas[ key ];
									}
								}
							}
						}
					};
				}
			}
		}
	};
};
