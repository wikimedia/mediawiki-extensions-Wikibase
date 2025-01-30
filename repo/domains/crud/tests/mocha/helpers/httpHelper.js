'use strict';

module.exports = {
	makeEtag( ...revisionIds ) {
		return revisionIds.map( ( revId ) => `"${revId}"` ).join( ',' );
	}
};
