export enum ValidApplicationStatus {
	INITIALIZING = 'initializing',
	READY = 'ready',
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
