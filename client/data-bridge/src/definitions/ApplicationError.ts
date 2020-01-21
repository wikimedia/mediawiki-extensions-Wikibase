import { MissingPermissionsError } from '@/definitions/data-access/BridgePermissionsRepository';

export enum ErrorTypes {
	APPLICATION_LOGIC_ERROR = 'APPLICATION_LOGIC_ERROR',
	INVALID_ENTITY_STATE_ERROR = 'INVALID_ENTITY_STATE_ERROR',
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
	type: ErrorTypes.INVALID_ENTITY_STATE_ERROR;
}

interface SavingFailedError extends ApplicationErrorBase {
	type: ErrorTypes.SAVING_FAILED;
}

type ApplicationError = MissingPermissionsError|ApplicationLogicError|InvalidEntityStateError|SavingFailedError;

export default ApplicationError;
