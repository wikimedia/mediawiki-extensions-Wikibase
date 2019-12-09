import { ApplicationErrorBase } from '@/definitions/ApplicationError';

export enum PageNotEditable {
	BLOCKED_ON_PAGE = 'blocked_on_client_page',
	BLOCKED_ON_ITEM = 'blocked_on_repo_item',
	ITEM_FULLY_PROTECTED = 'protectedpage',
	ITEM_SEMI_PROTECTED = 'semiprotectedpage',
	ITEM_CASCADE_PROTECTED = 'cascadeprotected',
	UNKNOWN = 'unknown',
}

interface BlockInfo {
	blockedBy: string;
	blockedById: number;
	blockReason: string;
	blockedTimestamp: string;
	blockExpiry: string;
	blockPartial: boolean;
	currentIP: string;
}

export interface BlockReason extends ApplicationErrorBase {
	type: typeof PageNotEditable.BLOCKED_ON_ITEM | typeof PageNotEditable.BLOCKED_ON_PAGE;
	info: BlockInfo;
}

export interface ProtectedReason extends ApplicationErrorBase {
	type: typeof PageNotEditable.ITEM_FULLY_PROTECTED
	| typeof PageNotEditable.ITEM_SEMI_PROTECTED
	| typeof PageNotEditable.ITEM_CASCADE_PROTECTED;
}

export interface UnknownReason extends ApplicationErrorBase {
	type: typeof PageNotEditable.UNKNOWN;
	info: {
		code: string;
		messageKey: string;
		messageParams: string[];
	};
}

export type MissingPermissionsError = BlockReason | ProtectedReason | UnknownReason;

export interface PageEditPermissionsRepository {
	isUserAllowedToEditPage(): Promise<MissingPermissionsError[]>;
}
