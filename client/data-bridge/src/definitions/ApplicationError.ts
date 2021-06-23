import {
	DataType,
	SnakType,
} from '@wmde/wikibase-datamodel-types';
import { MissingPermissionsError } from '@/definitions/data-access/BridgePermissionsRepository';

export enum ErrorTypes {
	INITIALIZATION_ERROR = 'INITIALIZATION_ERROR',
	APPLICATION_LOGIC_ERROR = 'APPLICATION_LOGIC_ERROR',
	INVALID_ENTITY_STATE_ERROR = 'INVALID_ENTITY_STATE_ERROR',
	UNSUPPORTED_AMBIGUOUS_STATEMENT = 'UNSUPPORTED_AMBIGUOUS_STATEMENT',
	UNSUPPORTED_DEPRECATED_STATEMENT = 'UNSUPPORTED_DEPRECATED_STATEMENT',
	UNSUPPORTED_SNAK_TYPE = 'UNSUPPORTED_SNAK_TYPE',
	UNSUPPORTED_DATATYPE = 'UNSUPPORTED_DATATYPE',
	UNSUPPORTED_DATAVALUE_TYPE = 'UNSUPPORTED_DATAVALUE_TYPE',
	SAVING_FAILED = 'SAVING_FAILED',
	ASSERT_ANON_FAILED = 'ASSERT_ANON_FAILED',
	ASSERT_USER_FAILED = 'ASSERT_USER_FAILED',
	ASSERT_NAMED_USER_FAILED = 'ASSERT_NAMED_USER_FAILED',
	EDIT_CONFLICT = 'EDIT_CONFLICT',
	BAD_TAGS = 'BAD_TAGS',
	NO_SUCH_REVID = 'NO_SUCH_REVID',
	CENTRALAUTH_BADTOKEN = 'CENTRALAUTH_BADTOKEN',
}

export interface ApplicationErrorBase {
	type: string;
	info?: object;
}

interface InitializationError extends ApplicationErrorBase {
	type: ErrorTypes.INITIALIZATION_ERROR
	| ErrorTypes.CENTRALAUTH_BADTOKEN;
	info: object;
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
	| ErrorTypes.UNSUPPORTED_DATAVALUE_TYPE;
}

export interface UnsupportedDatatypeError extends ApplicationErrorBase {
	type: ErrorTypes.UNSUPPORTED_DATATYPE;
	info: {
		unsupportedDatatype: DataType;
	};
}

export interface UnsupportedSnakTypeError extends ApplicationErrorBase {
	type: ErrorTypes.UNSUPPORTED_SNAK_TYPE;
	info: {
		snakType: SnakType;
	};
}

export interface SavingFailedError extends ApplicationErrorBase {
	type: ErrorTypes.SAVING_FAILED
	| ErrorTypes.ASSERT_ANON_FAILED
	| ErrorTypes.ASSERT_USER_FAILED
	| ErrorTypes.ASSERT_NAMED_USER_FAILED
	| ErrorTypes.EDIT_CONFLICT
	| ErrorTypes.BAD_TAGS
	| ErrorTypes.NO_SUCH_REVID;
}

type ApplicationError = MissingPermissionsError
| InitializationError
| ApplicationLogicError
| InvalidEntityStateError
| UnsupportedDatatypeError
| UnsupportedSnakTypeError
| SavingFailedError;

export default ApplicationError;
