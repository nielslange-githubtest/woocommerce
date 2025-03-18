/**
 * External dependencies
 */
import { renderHook, act } from '@testing-library/react';
import { apiFetch } from '@wordpress/data-controls';
import { dispatch } from '@wordpress/data';
import type { APIFetchOptions } from '@wordpress/api-fetch';
import deprecated from '@wordpress/deprecated';

/**
 * Internal dependencies
 */
import { useSettings } from '../use-settings';
import { createTestSettings } from './utils';
import { store } from '..';

// Mock data-controls
jest.mock('@wordpress/data-controls', () => ({
	...jest.requireActual('@wordpress/data-controls'),
	apiFetch: jest.fn(),
}));

jest.mock('../resolvers', () => ({}));

jest.mock( '@wordpress/deprecated', () => jest.fn() );

describe('useSettings', () => {

	beforeEach(() => {
		dispatch(store).clearSettings();
	});

	it('should return settings and utility functions', () => {
		const { result } = renderHook(
			() => useSettings('general', ['setting1']),
			{
				wrapper: ({ children }) => children,
			}
		);

		expect(result.current).toEqual({
			settingsError: false,
			isRequesting: false,
			isDirty: false,
			setting1: undefined,
			updateAndPersistSettings: expect.any(Function),
			persistSettings: expect.any(Function),
			updateSettings: expect.any(Function),
		});
	});

	it('should update settings', () => {
		const { result } = renderHook(
			() => useSettings('general', ['setting1']),
			{
				wrapper: ({ children }) => children,
			}
		);

		const testSettings = createTestSettings();

		act(() => {
			result.current.updateSettings(testSettings);
		});

		expect(result.current.setting1).toEqual(
			testSettings.setting1
		);
		expect(result.current.isDirty).toBe(true);
	});

	it('should persist settings', async () => {
		const { result } = renderHook(
			() => useSettings('general', ['setting1']),
			{
				wrapper: ({ children }) => children,
			}
		);
		(apiFetch as jest.Mock).mockImplementation(
			({ path, method, data }: APIFetchOptions) => {
				expect(path).toContain(
					'/wc-analytics/settings/general/batch'
				);
				expect(method).toBe('POST');

				return Promise.resolve();
			}
		);

		const testSettings = createTestSettings();

		act(() => {
			result.current.updateSettings(testSettings);
		});

		await act(async () => {
			await result.current.persistSettings();
		});

		expect(result.current.setting1).toEqual(
			testSettings.setting1
		);
		expect(result.current.isDirty).toBe(false);
	});

	it('should update and persist settings', async () => {
		const { result } = renderHook(
			() => useSettings('general', ['setting1']),
			{
				wrapper: ({ children }) => children,
			}
		);

		(apiFetch as jest.Mock).mockImplementation(
			({ path, method, data }: APIFetchOptions) => {
				expect(path).toContain(
					'/wc-analytics/settings/general/batch'
				);
				expect(method).toBe('POST');

				return Promise.resolve();
			}
		);


		const testSettings = createTestSettings();

		await act(async () => {
			await result.current.updateAndPersistSettings(testSettings);
		});

		expect(result.current.setting1).toEqual(testSettings.setting1);
		expect(result.current.isDirty).toBe(false);
	});

	it('should update settings with name and settings object', () => {
		const { result } = renderHook(
			() => useSettings('general', ['newSetting']),
			{
				wrapper: ({ children }) => children,
			}
		);

		act(() => {
			result.current.updateSettings('general', { newSetting: 'new' });
		});

		expect(result.current.newSetting).toEqual( 'new' );
		expect(result.current.isDirty).toBe(true);
		expect(deprecated).toHaveBeenCalled();
	});

	it('should update and persist settings with name and settings object', async () => {
		const { result } = renderHook(
			() => useSettings('general', ['newSetting']),
			{
				wrapper: ({ children }) => children,
			}
		);

		(apiFetch as jest.Mock).mockImplementation(
			({ path, method, data }: APIFetchOptions) => {
				expect(path).toContain(
					'/wc-analytics/settings/general/batch'
				);
				expect(method).toBe('POST');

				return Promise.resolve();
			}
		);

		await act(async () => {
			await result.current.updateAndPersistSettings('general', { newSetting: 'new' });
		});

		expect(result.current.newSetting).toEqual('new');
		expect(result.current.isDirty).toBe(false);
		expect(deprecated).toHaveBeenCalled();
	});
});
