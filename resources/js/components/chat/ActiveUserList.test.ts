import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import { ref } from 'vue';
import { createI18n } from 'vue-i18n';
import ActiveUserList from './ActiveUserList.vue';

const i18n = createI18n({
    legacy: false,
    locale: 'en',
    messages: {
        en: {
            activeUsers: {
                title: 'Active Users',
                noUsers: 'No active users',
                muteTimed: 'Mute (Timed)',
                mutePermanent: 'Mute Permanently',
                muteReason: 'Reason',
                muteDuration: 'Duration (minutes)',
                showInLanCore: 'Show in LanCore',
                unmute: 'Unmute',
                muteSuccess: 'User has been muted',
                unmuteSuccess: 'User has been unmuted',
                actions: 'Actions',
            },
            common: {
                loading: 'Loading...',
                cancel: 'Cancel',
            },
        },
    },
});

// Mock Inertia usePage
const mockPageProps = ref({
    isModerator: false,
});

vi.mock('@inertiajs/vue3', () => ({
    usePage: () => ({ props: mockPageProps.value }),
}));

import { vi } from 'vitest';

const sampleUsers = [
    {
        id: 1,
        name: 'Alice',
        chat_color: '#ff0000',
        lancore_user_id: 100,
        roles: ['admin'],
    },
    {
        id: 2,
        name: 'Bob',
        chat_color: null,
        lancore_user_id: null,
        roles: ['user'],
    },
    {
        id: 3,
        name: 'Carol',
        chat_color: '#00ff00',
        lancore_user_id: 200,
        roles: ['moderator'],
    },
];

function mountList(props: Record<string, unknown> = {}) {
    return mount(ActiveUserList, {
        props: {
            activeUsers: sampleUsers,
            lancoreBaseUrl: 'http://lancore.test',
            ...props,
        },
        global: {
            plugins: [i18n],
        },
    });
}

describe('ActiveUserList', () => {
    it('renders list of active users', () => {
        const wrapper = mountList();
        expect(wrapper.text()).toContain('Alice');
        expect(wrapper.text()).toContain('Bob');
        expect(wrapper.text()).toContain('Carol');
    });

    it('shows user count', () => {
        const wrapper = mountList();
        expect(wrapper.text()).toContain('(3)');
    });

    it('displays user name with correct custom color', () => {
        const wrapper = mountList();
        const nameSpans = wrapper.findAll('span.font-medium');
        const aliceSpan = nameSpans.find((s) => s.text() === 'Alice');
        expect(aliceSpan?.attributes('style')).toContain('color: #ff0000');
    });

    it('shows role badge for admin', () => {
        const wrapper = mountList();
        expect(wrapper.text()).toContain('Admin');
    });

    it('shows role badge for moderator', () => {
        const wrapper = mountList();
        expect(wrapper.text()).toContain('Moderator');
    });

    it('shows empty state when no users', () => {
        const wrapper = mountList({ activeUsers: [] });
        expect(wrapper.text()).toContain('No active users');
        expect(wrapper.text()).toContain('(0)');
    });

    it('hides action dropdown for regular users', () => {
        mockPageProps.value = { isModerator: false };
        const wrapper = mountList();
        // No dropdown trigger buttons should be present
        const buttons = wrapper.findAll('button');
        const dropdownButtons = buttons.filter((b) =>
            b.classes().includes('opacity-0'),
        );
        expect(dropdownButtons.length).toBe(0);
    });

    it('shows action dropdown for moderators', () => {
        mockPageProps.value = { isModerator: true };
        const wrapper = mountList();
        // Should have dropdown triggers (one per user, hidden until hover)
        const buttons = wrapper.findAll('button');
        expect(buttons.length).toBeGreaterThanOrEqual(3);
    });
});
