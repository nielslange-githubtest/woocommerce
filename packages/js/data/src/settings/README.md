# Settings Data Store

The settings data store provides a centralized way to manage WooCommerce settings across different groups like general, products, tax, account, email, and advanced settings.

## Usage

```js
import { useSelect } from '@wordpress/data';
import { store as settingsStore } from '@woocommerce/data';

function MySettingsComponent() {
  const settings = useSelect(( select ) => {
    const { getSettings } = select( settingsStore );
    return getSettings('general');
  }, []);

  return (
    <div>
      {/* Use your settings here */}
    </div>
  );
}
```

## Custom Hooks

### `useSettings`

A custom hook to manage settings for a given group.

```js
import { useSettings } from '@woocommerce/data';

function MyComponent() {
  const {
    settingsError,
    isRequesting,
    isDirty,
    persistSettings,
    updateAndPersistSettings,
    updateSettings,
    ...settings
  } = useSettings('general', ['setting_key']);

  return (
    // Your component implementation
  );
}
```

**Parameters**

- `group` (string): The settings group to manage (e.g., 'general', 'products', 'tax', etc.)
- `settingsKeys` (string[], optional): Specific setting keys to retrieve. If not provided, it doesn't return any settings.

**Returns**

- `Object`:
  - `settingsError` (boolean): Whether there was an error fetching settings
  - `isRequesting` (boolean): Whether settings are currently being requested
  - `isDirty` (boolean): Whether settings have unsaved changes
  - `persistSettings` (Function): Persists all dirty settings in the group
  - `updateAndPersistSettings` (Function): Updates and immediately persists a setting
  - `updateSettings` (Function): Updates a setting without persisting
  - `...settings`: The requested settings values

## Actions

The settings store provides the following actions:

### `updateSettingsForGroup( group: string, settings: Settings, time?: Date )`

Updates settings for a specific group in the store without persisting to the server.

### `updateErrorForGroup( group: string, settings: Settings | null, error: unknown, time?: Date )`

Updates the error state for a specific settings group.

### `setIsRequesting( group: string, isRequesting: boolean )`

Sets the requesting state for a specific settings group.

### `clearIsDirty( group: string )`

Clears the dirty state for a specific settings group.

### `persistSettingsForGroup( group: string )`

Persists all dirty settings for a specific group to the server.

### `updateAndPersistSettingsForGroup( group: string, settings: Settings )`

Updates settings for a specific group and immediately persists them to the server.

### `clearSettings()`

Clears all settings from the store.

## Selectors

### `getSettingsGroupNames( state )`

Retrieves the names of all settings groups.

### `getSettings( state, group )`

Retrieves all settings for a given group.

### `getDirtyKeys( state, group )`

Retrieves the dirty keys for a given group.

### `getIsDirty( state, group, keys?: string[] )`

Returns whether specific settings or a group have unsaved changes.

### `getSettingsForGroup( state, group, keys )`

Returns settings for a specific group, optionally filtered by keys.

### `isUpdateSettingsRequesting( state, group )`

Returns whether settings are being updated for a group.

### `getSetting( state, group, settingId, fallback?, filter? )`

Retrieves a specific setting value from the setting store. Optionally accepts a fallback value and a filter function.

**Parameters**
- `state`: State param added by wp.data
- `group`: The settings group
- `settingId`: The identifier for the setting
- `fallback`: (optional) The value to use if the setting is not found
- `filter`: (optional) A callback for filtering the value before it's returned

### `getLastSettingsErrorForGroup( state, group )`

Returns the last error that occurred for a settings group.

### `getSettingsError( state, group, id )`

Retrieves the settings error for a given group and setting id. If no id is provided, it returns the error for the group.
