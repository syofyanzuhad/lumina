import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';

const { routerGet, usePageMock } = vi.hoisted(() => ({
    routerGet: vi.fn(),
    usePageMock: vi.fn(),
}));

vi.mock('@inertiajs/vue3', () => ({
    usePage: usePageMock,
    router: { get: routerGet },
}));

import SiteSwitcher from './SiteSwitcher.vue';

const defaultPage = {
    props: {
        sites: [
            { id: 1, domain: 'one.example.com' },
            { id: 2, domain: 'two.example.com' },
        ],
        active_site_id: null,
    },
    url: '/dashboard',
};

function mountSwitcher() {
    return mount(SiteSwitcher, {
        global: {
            stubs: {
                Select: {
                    name: 'Select',
                    props: ['modelValue'],
                    emits: ['update:modelValue'],
                    template: `<select :value="modelValue" @change="$emit('update:modelValue', $event.target.value)"><slot /></select>`,
                },
                SelectContent: {
                    template: '<div><slot /></div>',
                },
                SelectTrigger: {
                    template: '<div><slot /></div>',
                },
                SelectValue: {
                    template: '<div><slot /></div>',
                },
                SelectSeparator: {
                    template: '<div />',
                },
                SelectItem: {
                    props: ['value'],
                    template: '<option :value="value"><slot /></option>',
                },
                CreateSiteModal: {
                    template: '<div><slot :open="() => {}" /></div>',
                },
            },
        },
    });
}

describe('SiteSwitcher', () => {
    beforeEach(() => {
        routerGet.mockClear();
        usePageMock.mockReturnValue(defaultPage);
    });

    it('renders an option for every site', () => {
        const wrapper = mountSwitcher();

        const options = wrapper.findAll('option');
        const labels = options.map((o) => o.text());

        expect(labels).toContain('one.example.com');
        expect(labels).toContain('two.example.com');
    });

    it('includes the Add New Site control in the dropdown', () => {
        const wrapper = mountSwitcher();

        expect(wrapper.text()).toContain('Add New Site');
    });

    it('uses the site_id from the URL as the active site', () => {
        usePageMock.mockReturnValue({
            props: {
                sites: [{ id: 1, domain: 'one.example.com' }],
                active_site_id: null,
            },
            url: '/dashboard?site_id=1',
        });

        const wrapper = mountSwitcher();

        expect(wrapper.find('select').attributes('value')).toBe('1');
    });

    it('prefers the path site on a site detail page over a stale site_id param', () => {
        usePageMock.mockReturnValue({
            props: {
                sites: [
                    { id: 6, domain: 'six.example.com' },
                    { id: 8, domain: 'eight.example.com' },
                ],
                active_site_id: null,
            },
            url: '/sites/6?site_id=8',
        });

        const wrapper = mountSwitcher();

        expect(wrapper.find('select').attributes('value')).toBe('6');
    });

    it('navigates to the selected site page when switching on a site detail page', async () => {
        usePageMock.mockReturnValue({
            props: {
                sites: [
                    { id: 6, domain: 'six.example.com' },
                    { id: 8, domain: 'eight.example.com' },
                ],
                active_site_id: null,
            },
            url: '/sites/6',
        });

        const wrapper = mountSwitcher();

        const select = wrapper.findComponent({ name: 'Select' });
        await select.vm.$emit('update:modelValue', '8');

        expect(routerGet).toHaveBeenCalledTimes(1);
        const [url, params, options] = routerGet.mock.calls[0];

        expect(url).toBe('/sites/8');
        expect(params).toEqual({});
        expect(options).toEqual({
            preserveState: true,
            preserveScroll: true,
        });
    });

    it('falls back to the active_site_id prop when no URL param exists', () => {
        usePageMock.mockReturnValue({
            props: {
                sites: [
                    { id: 1, domain: 'one.example.com' },
                    { id: 2, domain: 'two.example.com' },
                ],
                active_site_id: 2,
            },
            url: '/dashboard',
        });

        const wrapper = mountSwitcher();

        expect(wrapper.find('select').attributes('value')).toBe('2');
    });

    it('navigates with the selected site_id preserving state and scroll', async () => {
        const wrapper = mountSwitcher();

        const select = wrapper.findComponent({ name: 'Select' });
        await select.vm.$emit('update:modelValue', '2');

        expect(routerGet).toHaveBeenCalledTimes(1);
        const [url, params, options] = routerGet.mock.calls[0];

        expect(url).toContain('/dashboard');
        expect(
            new URL(url, 'http://localhost').searchParams.get('site_id'),
        ).toBe('2');
        expect(params).toEqual({});
        expect(options).toEqual({
            preserveState: true,
            preserveScroll: true,
        });
    });
});
