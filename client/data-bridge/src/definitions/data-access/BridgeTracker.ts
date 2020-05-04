export default interface BridgeTracker {
	trackPropertyDatatype( datatype: string ): void;
	trackTitlePurgeError(): void;
	trackUnknownError( type: string ): void;
	trackSavingUnknownError( type: string ): void;
}
