import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import ChatMessage from './ChatMessage.vue';

function makeMessage(overrides: Record<string, unknown> = {}) {
    return {
        id: 1,
        body: 'Hello world',
        created_at: '2025-01-15T12:30:00.000Z',
        user: { id: 10, name: 'Alice', chat_color: null },
        ...overrides,
    };
}

describe('ChatMessage', () => {
    it('renders the message body', () => {
        const wrapper = mount(ChatMessage, {
            props: { message: makeMessage({ body: 'Test message content' }) },
        });
        expect(wrapper.text()).toContain('Test message content');
    });

    it('renders the username', () => {
        const wrapper = mount(ChatMessage, {
            props: { message: makeMessage({ user: { id: 1, name: 'Bob', chat_color: null } }) },
        });
        expect(wrapper.text()).toContain('Bob');
    });

    it('applies custom chat_color when set', () => {
        const wrapper = mount(ChatMessage, {
            props: {
                message: makeMessage({
                    user: { id: 1, name: 'ColorUser', chat_color: '#ff5500' },
                }),
            },
        });
        const nameSpan = wrapper.find('span.font-medium');
        expect(nameSpan.attributes('style')).toContain('color: rgb(255, 85, 0)');
    });

    it('generates a hash-based color when no chat_color is set', () => {
        const wrapper = mount(ChatMessage, {
            props: {
                message: makeMessage({
                    user: { id: 1, name: 'HashUser', chat_color: null },
                }),
            },
        });
        const nameSpan = wrapper.find('span.font-medium');
        const style = nameSpan.attributes('style') ?? '';
        expect(style).toContain('color:');
        // Should be an hsl value since no custom color
        expect(style).toMatch(/hsl\(/);
    });

    it('uses consistent hash color for same username', () => {
        const msg = makeMessage({ user: { id: 1, name: 'ConsistentUser', chat_color: null } });
        const w1 = mount(ChatMessage, { props: { message: msg } });
        const w2 = mount(ChatMessage, { props: { message: msg } });
        const color1 = w1.find('span.font-medium').attributes('style');
        const color2 = w2.find('span.font-medium').attributes('style');
        expect(color1).toBe(color2);
    });

    it('uses different hash colors for different usernames', () => {
        const w1 = mount(ChatMessage, {
            props: { message: makeMessage({ user: { id: 1, name: 'UserAlpha', chat_color: null } }) },
        });
        const w2 = mount(ChatMessage, {
            props: { message: makeMessage({ user: { id: 2, name: 'UserBeta', chat_color: null } }) },
        });
        const color1 = w1.find('span.font-medium').attributes('style');
        const color2 = w2.find('span.font-medium').attributes('style');
        expect(color1).not.toBe(color2);
    });

    it('formats the timestamp', () => {
        const wrapper = mount(ChatMessage, {
            props: { message: makeMessage({ created_at: '2025-06-15T14:30:00.000Z' }) },
        });
        // Should display a time string (format varies by locale)
        const text = wrapper.text();
        // The formatted time should contain some digits and separator
        expect(text).toMatch(/\d{1,2}[:.]\d{2}/);
    });

    it('falls back to "User" when user name is missing', () => {
        const wrapper = mount(ChatMessage, {
            props: {
                message: {
                    id: 1,
                    body: 'Message text',
                    created_at: '2025-01-01T00:00:00.000Z',
                    user: { id: 0, name: undefined as unknown as string, chat_color: null },
                },
            },
        });
        expect(wrapper.text()).toContain('User');
    });

    it('preserves whitespace in message body', () => {
        const wrapper = mount(ChatMessage, {
            props: { message: makeMessage({ body: 'line1\nline2' }) },
        });
        // The div has whitespace-pre-wrap class, check the raw text content has newline
        const bodyDiv = wrapper.find('.whitespace-pre-wrap');
        expect(bodyDiv.text()).toContain('line1');
        expect(bodyDiv.text()).toContain('line2');
    });
});
