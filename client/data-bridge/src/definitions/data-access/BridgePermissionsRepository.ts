import { ApplicationErrorBase } from '@/definitions/ApplicationError';

export enum PageNotEditable {
	BLOCKED_ON_CLIENT_PAGE = 'blocked_on_client_page',
	BLOCKED_ON_REPO_ITEM = 'blocked_on_repo_item',
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
	type: PageNotEditable.BLOCKED_ON_REPO_ITEM | PageNotEditable.BLOCKED_ON_CLIENT_PAGE;
	info: BlockInfo;
}

export interface ProtectedReason extends ApplicationErrorBase {
	type: PageNotEditable.ITEM_FULLY_PROTECTED
	| PageNotEditable.ITEM_SEMI_PROTECTED;
	info: {
		right: string;
	};
}

export interface CascadeProtectedReason extends ApplicationErrorBase {
	type: PageNotEditable.ITEM_CASCADE_PROTECTED
	| PageNotEditable.PAGE_CASCADE_PROTECTED;
	info: {
		pages: readonly string[];
	};
}

export interface UnknownReason extends ApplicationErrorBase {
	type: PageNotEditable.UNKNOWN;
	info: {
		code: string;
		messageKey: string;
		messageParams: readonly ( string|number )[];
	};
}

export type MissingPermissionsError = BlockReason | ProtectedReason | CascadeProtectedReason | UnknownReason;

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
