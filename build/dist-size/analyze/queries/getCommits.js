'use strict';

const gql = require( 'apollo-boost' ).gql;

module.exports = function () {
	return gql`
		query getCommitOid( $repoOwner: String!, $repoName: String!, $filePath: String!, $endCursor: String ) {
			repository( owner: $repoOwner, name: $repoName ) {
				defaultBranchRef {
					target {
						... on Commit {
							history( first: 100, after: $endCursor, path: $filePath ) {
								nodes {
									committedDate
									oid
									messageHeadline
								}
								pageInfo {
									hasNextPage
									endCursor
								}
							}
						}
					}
				}
			}
		}
	`;
};
