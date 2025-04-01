/**
 * Internal dependencies
 */
import { initNameField } from './name';
import { initRegularPriceField } from './regular-price';
import { initSalePriceField } from './sale-price';
import { initTextAreaField } from './text-area';

export function initFields() {
	initNameField();
	initRegularPriceField();
	initSalePriceField();
	initTextAreaField();
}

export * from './registration';
