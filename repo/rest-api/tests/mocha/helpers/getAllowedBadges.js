'use strict';

const { RequestBuilder } = require( './RequestBuilder' );

let allowedBadges;
async function getAllowedBadges() {
	allowedBadges = allowedBadges || [
		( await newBadgeItem( 'badge1-' ) ).body.id,
		( await newBadgeItem( 'badge2-' ) ).body.id
	];

	return allowedBadges;
}

async function newBadgeItem( labelPrefix ) {
	return new RequestBuilder()
		.withRoute( 'POST', '/entities/items' )
		.withJsonBodyParam( 'item', { labels: { en: labelPrefix } } )
		.makeRequest();
}

module.exports = { getAllowedBadges };
