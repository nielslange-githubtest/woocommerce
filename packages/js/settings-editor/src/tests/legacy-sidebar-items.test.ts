/**
 * Internal dependencies
 */
import {
	getPrimaryLegacySidebarItems,
	getSecondaryLegacySidebarItems,
} from '../utils/index';

const mockSettingsData = {
	pages: {
		childless: {
			label: 'Childless',
			icon: 'settings',
			slug: 'childless',
			sections: {
				default: {
					label: 'General',
					settings: [],
				},
			},
			is_modern: false,
			start: null,
			end: null,
		},
		childful: {
			label: 'Childful',
			icon: 'settings',
			slug: 'childful',
			sections: {
				default: {
					label: 'General',
					settings: [],
				},
				one: {
					label: 'One',
					settings: [],
				},
				two: {
					label: 'Two',
					settings: [],
				},
			},
			is_modern: false,
			start: null,
			end: null,
		},
	},
	start: null,
	_wpnonce: 'test-nonce',
};

describe( 'getPrimaryLegacySidebarItems', () => {
	it( 'should return the primary legacy sidebar items', () => {
		const activePage = 'childless';
		const primarySidebarItems = getPrimaryLegacySidebarItems(
			mockSettingsData,
			activePage
		);
		expect( primarySidebarItems ).toHaveLength( 2 );

		const childlessItem = primarySidebarItems[ 0 ];
		expect( childlessItem.slug ).toBe( 'childless' );
		expect( childlessItem.label ).toBe( 'Childless' );
		expect( childlessItem.icon ).toBe( 'settings' );
		expect( childlessItem.to ).toBe( 'wc-settings?tab=childless' );
		expect( childlessItem.withChevron ).toBe( false );
		expect( childlessItem.isCurrent ).toBe( true );

		const childfulItem = primarySidebarItems[ 1 ];
		expect( childfulItem.slug ).toBe( 'childful' );
		expect( childfulItem.label ).toBe( 'Childful' );
		expect( childfulItem.icon ).toBe( 'settings' );
		expect( childfulItem.to ).toBe( 'wc-settings?tab=childful' );
		expect( childfulItem.withChevron ).toBe( true );
		expect( childfulItem.isCurrent ).toBe( false );
	} );
} );

describe( 'getSecondaryLegacySidebarItems', () => {
	it( 'should return the secondary legacy sidebar items', () => {
		const activePage = 'childful';
		const activeSection = 'one';
		const secondarySidebarItems = getSecondaryLegacySidebarItems(
			mockSettingsData,
			activePage,
			activeSection
		);
		expect( secondarySidebarItems ).toHaveLength( 3 );

		const itemDefault = secondarySidebarItems[ 0 ];
		expect( itemDefault.slug ).toBe( 'default' );
		expect( itemDefault.label ).toBe( 'General' );
		expect( itemDefault.to ).toBe( 'wc-settings?tab=childful' );
		expect( itemDefault.withChevron ).toBe( false );
		expect( itemDefault.isCurrent ).toBe( false );

		const itemOne = secondarySidebarItems[ 1 ];
		expect( itemOne.slug ).toBe( 'one' );
		expect( itemOne.label ).toBe( 'One' );
		expect( itemOne.to ).toBe( 'wc-settings?tab=childful&section=one' );
		expect( itemOne.withChevron ).toBe( false );
		expect( itemOne.isCurrent ).toBe( true );

		const itemTwo = secondarySidebarItems[ 2 ];
		expect( itemTwo.slug ).toBe( 'two' );
		expect( itemTwo.label ).toBe( 'Two' );
		expect( itemTwo.to ).toBe( 'wc-settings?tab=childful&section=two' );
		expect( itemTwo.withChevron ).toBe( false );
		expect( itemTwo.isCurrent ).toBe( false );
	} );
} );
