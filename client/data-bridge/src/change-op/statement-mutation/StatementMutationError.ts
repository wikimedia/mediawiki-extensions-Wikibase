enum StatementMutationError {
	NO_SNAK_FOUND = 'snak not found',
	NO_STATEMENT_GROUP_FOUND = 'statement group not found',
	INCONSISTENT_PAYLOAD_TYPE =
	'targetvalue\'s datavalue type is different from the snak\'s datavalue type in the state',
}

export default StatementMutationError;
