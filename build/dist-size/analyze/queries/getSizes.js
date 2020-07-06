'use strict';

const gql = require( 'apollo-boost' ).gql;

module.exports = function ( repoOwner, repoName, filePath, history ) {
	return gql( `
		query getByteSize {
			repository( owner: "${repoOwner}", name: "${repoName}" ) {
			` +
				history.map( ( value, index ) => {
					return `commit${index}: object( expression: "${value.oid}:${filePath}" ) {
								... on Blob {
										byteSize
									}
								}
							`;
				} ).join( '\n' ) +
			`}
		}
	` );
};
