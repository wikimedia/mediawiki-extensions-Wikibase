'use strict';

module.exports = function formatStatementEditSummary( module, action, mainsnak, userComment ) {
	const property = mainsnak.property;
	const value = mainsnak.datavalue.value;
	const autoSummary = `/* ${module}-${action}:1| */ [[Property:${property}]]: ${value}`;

	return userComment ? `${autoSummary}, ${userComment}` : autoSummary;
};
