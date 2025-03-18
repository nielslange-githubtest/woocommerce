export type Settings = {
	[ key: string ]: unknown;
};

type RegularSettings = {
	[ key: string ]: string;
};

type BaseSettingsGroup = {
	settingsErrors?: Record< string, unknown >;
	lastReceived?: Date;
	error?: unknown;
	isRequesting?: boolean;
	dirty?: string[];
};

type UnknownSettingsGroup = BaseSettingsGroup & {
	settings?: Settings;
};

type RegularSettingsGroup = BaseSettingsGroup & {
	settings?: RegularSettings;
};

type WCAdminSettingsGroup = BaseSettingsGroup & {
	settings?: Settings & {
		shopUrl: string;
		siteUrl: string;
		homeUrl: string;
		// Add other properties as needed
	};
};

type RegularSettingsGroups =
	| 'general'
	| 'products'
	| 'tax'
	| 'account'
	| 'email'
	| 'advanced';

export type SettingsGroupType< T extends string > =
	T extends RegularSettingsGroups
		? RegularSettings
		: T extends 'wcadmin'
		? NonNullable< WCAdminSettingsGroup[ 'settings' ] >
		: Settings;

export type SettingsState = {
	[ K in string ]: K extends RegularSettingsGroups
		? RegularSettingsGroup
		: K extends 'wcadmin'
		? WCAdminSettingsGroup
		: UnknownSettingsGroup;
};
