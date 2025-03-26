/**
 * External dependencies
 */
import type { WPNotice } from '@wordpress/notices/build-types/store/selectors';

export type StoreNoticeStatus =
	| 'success'
	| 'error'
	| 'info'
	| 'warning'
	| 'default';
export interface NoticeType extends Partial< Omit< WPNotice, 'status' > > {
	id: string;
	content: string;
	status: StoreNoticeStatus;
	isDismissible: boolean;
	context?: string | undefined;
}
