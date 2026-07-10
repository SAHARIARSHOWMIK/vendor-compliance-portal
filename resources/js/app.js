
const sidebar = document.querySelector('[data-sidebar]');
const overlay = document.querySelector('[data-sidebar-overlay]');
const openButtons = document.querySelectorAll('[data-sidebar-open]');
const closeButtons = document.querySelectorAll('[data-sidebar-close]');

const openSidebar = () => {
    sidebar?.classList.add('is-open');
    overlay?.classList.add('is-open');
    document.body.classList.add('overflow-hidden');
};
const closeSidebar = () => {
    sidebar?.classList.remove('is-open');
    overlay?.classList.remove('is-open');
    document.body.classList.remove('overflow-hidden');
};
openButtons.forEach((button) => button.addEventListener('click', openSidebar));
closeButtons.forEach((button) => button.addEventListener('click', closeSidebar));
overlay?.addEventListener('click', closeSidebar);

const toast = document.querySelector('[data-toast]');
if (toast) {
    window.setTimeout(() => {
        toast.classList.add('opacity-0', 'translate-y-2');
        window.setTimeout(() => toast.remove(), 250);
    }, 4500);
}

document.querySelectorAll('[data-confirm]').forEach((element) => {
    element.addEventListener('click', (event) => {
        if (!window.confirm(element.getAttribute('data-confirm') || 'Continue with this action?')) {
            event.preventDefault();
        }
    });
});

document.querySelectorAll('[data-progress]').forEach((element) => {
    const value = Math.min(100, Math.max(0, Number(element.getAttribute('data-progress') || 0)));
    requestAnimationFrame(() => { element.style.width = `${value}%`; });
});

const commandInput = document.querySelector('[data-command-search]');
if (commandInput) {
    document.addEventListener('keydown', (event) => {
        if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
            event.preventDefault();
            commandInput.focus();
        }
    });
}
