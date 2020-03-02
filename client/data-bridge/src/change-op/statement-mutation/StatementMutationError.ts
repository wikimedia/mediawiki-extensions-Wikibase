enum StatementMutationError {
	NO_SNAK_FOUND = 'snak not found',
	INCONSISTENT_PAYLOAD_TYPE =
	'targetvalue\'s datavalue type is different from the snak\'s datavalue type in the state',
}

export default StatementMutationError;
