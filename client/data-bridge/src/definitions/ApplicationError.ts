import DataType from '@/datamodel/DataType';
import { MissingPermissionsError } from '@/definitions/data-access/BridgePermissionsRepository';

export enum ErrorTypes {
	APPLICATION_LOGIC_ERROR = 'APPLICATION_LOGIC_ERROR',
	INVALID_ENTITY_STATE_ERROR = 'INVALID_ENTITY_STATE_ERROR',
	UNSUPPORTED_AMBIGUOUS_STATEMENT = 'UNSUPPORTED_AMBIGUOUS_STATEMENT',
	UNSUPPORTED_DEPRECATED_STATEMENT = 'UNSUPPORTED_DEPRECATED_STATEMENT',
	UNSUPPORTED_SNAK_TYPE = 'UNSUPPORTED_SNAK_TYPE',
	UNSUPPORTED_DATATYPE = 'UNSUPPORTED_DATATYPE',
	UNSUPPORTED_DATAVALUE_TYPE = 'UNSUPPORTED_DATAVALUE_TYPE',
	SAVING_FAILED = 'SAVING_FAILED',
}

export interface ApplicationErrorBase {
	type: string;
	info?: object;
}

interface ApplicationLogicError extends ApplicationErrorBase {
	type: ErrorTypes.APPLICATION_LOGIC_ERROR;
	info: {
		stack?: string;
	};
}

interface InvalidEntityStateError extends ApplicationErrorBase {
	type: ErrorTypes.INVALID_ENTITY_STATE_ERROR
	| ErrorTypes.UNSUPPORTED_AMBIGUOUS_STATEMENT
	| ErrorTypes.UNSUPPORTED_DEPRECATED_STATEMENT
	| ErrorTypes.UNSUPPORTED_SNAK_TYPE
	| ErrorTypes.UNSUPPORTED_DATAVALUE_TYPE;
}

export interface UnsupportedDatatypeError extends ApplicationErrorBase {
	type: ErrorTypes.UNSUPPORTED_DATATYPE;
	info: {
		unsupportedDatatype: DataType;
	};
}

interface SavingFailedError extends ApplicationErrorBase {
	type: ErrorTypes.SAVING_FAILED;
}

type ApplicationError = MissingPermissionsError
| ApplicationLogicError
| InvalidEntityStateError
| UnsupportedDatatypeError
| SavingFailedError;

export default ApplicationError;
