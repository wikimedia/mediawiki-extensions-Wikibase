'use strict';

const getCommits = require( './queries/getCommits' );
const getSizes = require( './queries/getSizes' );

module.exports = async function ( client, repoOwner, repoName, filePath ) {
	const history = [];

	let hasNextPage = true;
	let endCursor = null;
	do {
		const fileHistoryPage = await client
			.query( {
				query: getCommits(),
				variables: {
					repoOwner,
					repoName,
					filePath,
					endCursor,
				},
			} )
			.then( ( result ) => result.data.repository.defaultBranchRef.target.history );

		const fileHistory = fileHistoryPage.nodes;

		const byteSizes = await client
			.query( {
				query: getSizes( repoOwner, repoName, filePath, fileHistory ),
			} )
			.then( ( result ) => result.data.repository );

		fileHistory.forEach( ( value, index ) => {
			const sizeInfo = byteSizes[ `commit${index}` ];
			history.push( {
				sha: value.oid,
				date: value.committedDate,
				subject: value.messageHeadline,
				size: sizeInfo ? sizeInfo.byteSize : null,
			} );
		} );

		hasNextPage = fileHistoryPage.pageInfo.hasNextPage;
		endCursor = fileHistoryPage.pageInfo.endCursor;
	} while ( hasNextPage );

	return history;
};
