import './bootstrap';

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-password-toggle-btn]').forEach((btn) => {
        const wrapper = btn.closest('.input-group');
        if (!wrapper) return;
        const input = wrapper.querySelector('[data-password-toggle]');
        const showIcon = btn.querySelector('[data-password-toggle-show]');
        const hideIcon = btn.querySelector('[data-password-toggle-hide]');
        if (!input || !showIcon || !hideIcon) return;

        btn.addEventListener('click', () => {
            const showing = input.type === 'text';
            input.type = showing ? 'password' : 'text';
            showIcon.classList.toggle('hidden', !showing);
            hideIcon.classList.toggle('hidden', showing);
        });
    });
});
