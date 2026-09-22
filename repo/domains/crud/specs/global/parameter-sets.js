'use strict';

const ref = ( name ) => ( { "$ref": `#/components/parameters/${ name }` } );

module.exports = {
	ReadConditionalHeaders: [ 'IfNoneMatch', 'IfModifiedSince', 'IfMatch', 'IfUnmodifiedSince' ].map( ref ),

	// If-Modified-Since is only defined for GET and HEAD requests. See T318715#8269376.
	EditConditionalHeaders: [ 'IfNoneMatch', 'IfMatch', 'IfUnmodifiedSince' ].map( ref )
};
