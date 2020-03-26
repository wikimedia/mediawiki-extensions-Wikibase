export enum ValidApplicationStatus {
	INITIALIZING = 'initializing',
	READY = 'ready',
	SAVING = 'saving',
	SAVED = 'saved',
}

enum ErrorStatus {
	ERROR = 'error',
}

const ApplicationStatus = {
	...ValidApplicationStatus,
	...ErrorStatus,
};

type ApplicationStatus = ValidApplicationStatus | ErrorStatus;
export default ApplicationStatus;
