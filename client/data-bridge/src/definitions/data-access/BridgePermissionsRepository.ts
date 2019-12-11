import { ApplicationErrorBase } from '@/definitions/ApplicationError';

export enum PageNotEditable {
	BLOCKED_ON_PAGE = 'blocked_on_client_page',
	BLOCKED_ON_ITEM = 'blocked_on_repo_item',
	PAGE_CASCADE_PROTECTED = 'cascadeprotected_on_client_page',
	ITEM_FULLY_PROTECTED = 'protectedpage',
	ITEM_SEMI_PROTECTED = 'semiprotectedpage',
	ITEM_CASCADE_PROTECTED = 'cascadeprotected',
	UNKNOWN = 'unknown',
}

export interface BlockInfo {
	blockId: number;
	blockedBy: string;
	blockedById: number;
	blockReason: string;
	blockedTimestamp: string;
	blockExpiry: string;
	blockPartial: boolean;
	// currentIP: string; // removed until T240565 is fixed
}

export interface BlockReason extends ApplicationErrorBase {
	type: typeof PageNotEditable.BLOCKED_ON_ITEM | typeof PageNotEditable.BLOCKED_ON_PAGE;
	info: BlockInfo;
}

export interface ProtectedReason extends ApplicationErrorBase {
	type: typeof PageNotEditable.ITEM_FULLY_PROTECTED
	| typeof PageNotEditable.ITEM_SEMI_PROTECTED
	| typeof PageNotEditable.ITEM_CASCADE_PROTECTED
	| typeof PageNotEditable.PAGE_CASCADE_PROTECTED;
}

export interface UnknownReason extends ApplicationErrorBase {
	type: typeof PageNotEditable.UNKNOWN;
	info: {
		code: string;
		messageKey: string;
		messageParams: ( string|number )[];
	};
}

export type MissingPermissionsError = BlockReason | ProtectedReason | UnknownReason;

/**
 * A repository for determining potential permission errors when using the Data Bridge.
 */
export interface BridgePermissionsRepository {
	/**
	 * Is the user allowed to use the Data Bridge for this target item and client page?
	 * @param repoItemTitle The title of the item page on the repo wiki.
	 * This is a title, not an entity ID, so it may include a namespace.
	 * @param clientPageTitle The title of the page on the client wiki.
	 */
	canUseBridgeForItemAndPage( repoItemTitle: string, clientPageTitle: string ): Promise<MissingPermissionsError[]>;
}
