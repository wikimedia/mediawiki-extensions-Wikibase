enum StatementMutationError {
	NO_SNAK_FOUND = 'snak not found',
	WRONG_PAYLOAD_TYPE = 'payload type does not match',
	WRONG_PAYLOAD_VALUE_TYPE = 'payload value is not a string',
}

export default StatementMutationError;
