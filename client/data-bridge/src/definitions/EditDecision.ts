/**
 * A decision of which type of edit to make, chosen by the user.
 * Not to be confused with the EditFlow,
 * which is determined by the page when creating the edit link.
 */
enum EditDecision {
	/** The previous value was incorrect. Replace it. */
	REPLACE = 'replace',
	/** The previous value was correct but is now outdated. Keep it as non-best rank. */
	UPDATE = 'update',
}

export default EditDecision; // "export default enum" not supported, see microsoft/Typescript#3792
