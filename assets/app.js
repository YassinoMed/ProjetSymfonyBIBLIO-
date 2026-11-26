import './styles/app.css';

import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import Lenis from 'lenis';

gsap.registerPlugin(ScrollTrigger);

const THEMES = ['editorial', 'premium', 'chaleureux', 'futuriste'];
const THEME_COLORS = {
    editorial: '#f3efe6',
    premium: '#0d0d14',
    chaleureux: '#fdf8ee',
    futuriste: '#08080a',
};
const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

const normalize = (value) =>
    String(value ?? '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLocaleLowerCase('fr');

const focusableSelector = [
    'a[href]',
    'button:not([disabled])',
    'input:not([disabled])',
    'select:not([disabled])',
    'textarea:not([disabled])',
    '[tabindex]:not([tabindex="-1"])',
].join(',');

function initThemes() {
    const switcher = document.querySelector('[data-theme-switcher]');
    const trigger = document.querySelector('[data-theme-toggle]');
    const menu = document.querySelector('[data-theme-menu]');
    const status = document.querySelector('[data-theme-status]');
    const options = [...document.querySelectorAll('[data-theme-option]')];

    if (!switcher || !trigger || !menu || options.length === 0) {
        return;
    }

    const applyTheme = (theme, announce = true) => {
        if (!THEMES.includes(theme)) {
            return;
        }

        document.documentElement.dataset.theme = theme;

        try {
            localStorage.setItem('bibliotheque-theme', theme);
            document.cookie = `theme=${theme}; Path=/; Max-Age=31536000; SameSite=Lax`;
        } catch (_) {
            // Le thème reste appliqué pour la session courante.
        }

        options.forEach((option) => {
            option.setAttribute('aria-pressed', String(option.dataset.themeOption === theme));
        });

        const color = THEME_COLORS[theme] ?? THEME_COLORS.editorial;
        document.querySelector('meta[name="theme-color"]')?.setAttribute('content', color);

        if (announce && status) {
            const selected = options.find((option) => option.dataset.themeOption === theme);
            status.textContent = `Direction visuelle ${selected?.querySelector('strong')?.textContent ?? ''} activée.`;
        }
    };

    const currentTheme = document.documentElement.dataset.theme;
    applyTheme(THEMES.includes(currentTheme) ? currentTheme : 'editorial', false);

    const closeMenu = ({ restoreFocus = false } = {}) => {
        menu.hidden = true;
        trigger.setAttribute('aria-expanded', 'false');
        if (restoreFocus) {
            trigger.focus();
        }
    };

    trigger.addEventListener('click', () => {
        const willOpen = menu.hidden;
        menu.hidden = !willOpen;
        trigger.setAttribute('aria-expanded', String(willOpen));

        if (willOpen) {
            menu.querySelector('[aria-pressed="true"]')?.focus();
        }
    });

    options.forEach((option) => {
        option.addEventListener('click', () => {
            applyTheme(option.dataset.themeOption);
            closeMenu({ restoreFocus: true });
        });
    });

    document.addEventListener('click', (event) => {
        if (!switcher.contains(event.target) && !menu.hidden) {
            closeMenu();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !menu.hidden) {
            closeMenu({ restoreFocus: true });
        }
    });
}

function initMobileNavigation() {
    const toggle = document.querySelector('[data-menu-toggle]');
    const menu = document.querySelector('[data-mobile-menu]');

    if (!toggle || !menu) {
        return;
    }

    const closeMenu = ({ restoreFocus = false } = {}) => {
        menu.classList.remove('open');
        menu.classList.add('hidden');
        toggle.setAttribute('aria-expanded', 'false');
        document.body.classList.remove('mobile-menu-open');

        if (restoreFocus) {
            toggle.focus();
        }
    };

    const openMenu = () => {
        menu.classList.remove('hidden');
        menu.classList.add('open');
        toggle.setAttribute('aria-expanded', 'true');
        document.body.classList.add('mobile-menu-open');
        menu.querySelector(focusableSelector)?.focus();
    };

    toggle.addEventListener('click', () => {
        const isOpen = toggle.getAttribute('aria-expanded') === 'true';
        if (isOpen) {
            closeMenu();
        } else {
            openMenu();
        }
    });

    menu.querySelectorAll('a').forEach((link) => link.addEventListener('click', () => closeMenu()));

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') {
            closeMenu({ restoreFocus: true });
        }
    });

    window.addEventListener('resize', () => {
        if (window.innerWidth >= 768 && toggle.getAttribute('aria-expanded') === 'true') {
            closeMenu();
        }
    });
}

function initSidebar() {
    const sidebar = document.querySelector('[data-app-sidebar]');
    const openButton = document.querySelector('[data-sidebar-open]');
    const closeButton = document.querySelector('[data-sidebar-close]');

    if (!sidebar || !openButton) {
        return;
    }

    const backdrop = document.createElement('div');
    backdrop.className = 'app-sidebar-backdrop';
    backdrop.hidden = true;
    document.body.append(backdrop);

    const closeSidebar = ({ restoreFocus = false } = {}) => {
        sidebar.classList.remove('open');
        sidebar.removeAttribute('data-open');
        backdrop.hidden = true;
        document.body.classList.remove('sidebar-open');

        if (restoreFocus) {
            openButton.focus();
        }
    };

    const openSidebar = () => {
        sidebar.classList.add('open');
        sidebar.dataset.open = 'true';
        backdrop.hidden = false;
        document.body.classList.add('sidebar-open');
        closeButton?.focus();
    };

    openButton.addEventListener('click', openSidebar);
    closeButton?.addEventListener('click', () => closeSidebar({ restoreFocus: true }));
    backdrop.addEventListener('click', () => closeSidebar({ restoreFocus: true }));

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && sidebar.classList.contains('open')) {
            closeSidebar({ restoreFocus: true });
        }
    });
}

function initPasswordToggles() {
    document.querySelectorAll('[data-password-toggle]').forEach((toggle) => {
        const input = toggle.getAttribute('aria-controls')
            ? document.getElementById(toggle.getAttribute('aria-controls'))
            : toggle.parentElement?.querySelector('input');

        if (!input) {
            return;
        }

        toggle.addEventListener('click', () => {
            const showPassword = input.type === 'password';
            input.type = showPassword ? 'text' : 'password';
            toggle.setAttribute('aria-label', showPassword ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
            toggle.setAttribute('aria-pressed', String(showPassword));
        });
    });
}

function initCatalog() {
    const form = document.querySelector('[data-catalog-form]');
    const grid = document.querySelector('[data-catalog-grid]');
    const empty = document.querySelector('[data-catalog-empty]');
    const count = document.querySelector('[data-catalog-count]');
    const search = document.querySelector('[data-catalog-search]');
    const filters = [...document.querySelectorAll('[data-catalog-filter]')];
    const sort = document.querySelector('[data-catalog-sort]');
    const reset = document.querySelector('[data-catalog-reset]');
    const cards = [...document.querySelectorAll('[data-book-card]')];

    if (!form || !grid || cards.length === 0) {
        return;
    }

    const categoryFilter = filters.find((filter) => filter.name === 'category');
    const statusFilter = filters.find((filter) => filter.name === 'status');
    const hiddenCategory = form.querySelector('[data-catalog-hidden-category]');
    const hiddenStatus = form.querySelector('[data-catalog-hidden-status]');
    const hiddenSort = form.querySelector('[data-catalog-hidden-sort]');

    const updateUrl = () => {
        const url = new URL(window.location.href);
        const query = search?.value.trim() ?? '';
        const category = categoryFilter?.value ?? '';
        const status = statusFilter?.value ?? '';
        const selectedSort = sort?.value ?? 'title';

        url.searchParams.delete('q');
        url.searchParams.delete('category');
        url.searchParams.delete('status');
        url.searchParams.delete('sort');

        if (query) url.searchParams.set('q', query);
        if (category) url.searchParams.set('category', category);
        if (status) url.searchParams.set('status', status);
        if (selectedSort !== 'title') url.searchParams.set('sort', selectedSort);

        window.history.replaceState({}, '', url);
    };

    const applyFilters = () => {
        const query = normalize(search?.value);
        const category = categoryFilter?.value ?? '';
        const status = statusFilter?.value ?? '';
        const selectedSort = sort?.value ?? 'title';
        const statusOrder = ['available', 'reserved', 'loan', 'repair'];
        const visibleCards = [];

        cards.forEach((card) => {
            const haystack = normalize(
                `${card.dataset.title} ${card.dataset.author} ${card.dataset.category} ${card.dataset.summary}`,
            );
            const matchesQuery = query === '' || haystack.includes(query);
            const matchesCategory = category === '' || card.dataset.category === category;
            const matchesStatus = status === '' || card.dataset.status === status;
            const isVisible = matchesQuery && matchesCategory && matchesStatus;

            card.hidden = !isVisible;
            if (isVisible) {
                visibleCards.push(card);
            }
        });

        visibleCards.sort((left, right) => {
            if (selectedSort === 'year') {
                return Number(right.dataset.year) - Number(left.dataset.year);
            }

            if (selectedSort === 'availability') {
                return statusOrder.indexOf(left.dataset.status) - statusOrder.indexOf(right.dataset.status);
            }

            return normalize(left.dataset.title).localeCompare(normalize(right.dataset.title), 'fr');
        });

        visibleCards.forEach((card) => grid.append(card));

        if (count) {
            count.innerHTML = `<strong>${visibleCards.length}</strong><span>${
                visibleCards.length === 1 ? 'ouvrage trouvé' : 'ouvrages trouvés'
            }</span>`;
        }

        if (empty) {
            empty.hidden = visibleCards.length !== 0;
        }

        if (hiddenCategory) hiddenCategory.value = category;
        if (hiddenStatus) hiddenStatus.value = status;
        if (hiddenSort) hiddenSort.value = selectedSort;
        updateUrl();
    };

    search?.addEventListener('input', applyFilters);
    filters.forEach((filter) => filter.addEventListener('change', applyFilters));
    sort?.addEventListener('change', applyFilters);
    form.addEventListener('submit', (event) => {
        event.preventDefault();
        applyFilters();
    });

    reset?.addEventListener('click', (event) => {
        event.preventDefault();
        if (search) search.value = '';
        if (categoryFilter) categoryFilter.value = '';
        if (statusFilter) statusFilter.value = '';
        if (sort) sort.value = 'title';
        applyFilters();
    });
}

function initBookDialog() {
    const dialog = document.querySelector('[data-book-dialog]');

    if (!dialog) {
        return;
    }

    const title = dialog.querySelector('[data-dialog-title]');
    const author = dialog.querySelector('[data-dialog-author]');
    const category = dialog.querySelector('[data-dialog-category]');
    const year = dialog.querySelector('[data-dialog-year]');
    const isbn = dialog.querySelector('[data-dialog-isbn]');
    const summary = dialog.querySelector('[data-dialog-summary]');
    const copies = dialog.querySelector('[data-dialog-copies]');
    const status = dialog.querySelector('[data-dialog-status]');
    const cover = dialog.querySelector('[data-dialog-cover]');

    const close = () => {
        if (dialog.open) {
            dialog.close();
        }
    };

    document.querySelectorAll('[data-book-open]').forEach((button) => {
        button.addEventListener('click', () => {
            const card = button.closest('[data-book-card]');
            if (!card) {
                return;
            }

            if (title) title.textContent = card.dataset.title ?? '';
            if (author) author.textContent = card.dataset.author ?? '';
            if (category) category.textContent = card.dataset.category ?? '';
            if (year) year.textContent = card.dataset.year ?? '';
            if (isbn) isbn.textContent = `ISBN ${card.dataset.isbn ?? ''}`;
            if (summary) summary.textContent = card.dataset.summary ?? '';
            if (copies) copies.textContent = `${card.dataset.copies ?? '0'} exemplaire(s) dans le catalogue`;

            if (status) {
                status.className = 'status-dot';
                status.classList.add(`status-dot--${card.dataset.status ?? 'available'}`);
            }

            if (cover) {
                [...cover.classList].forEach((className) => {
                    if (className.startsWith('book-cover--')) {
                        cover.classList.remove(className);
                    }
                });
                cover.classList.add(`book-cover--${card.dataset.accent ?? 'ochre'}`);
            }

            if (typeof dialog.showModal === 'function') {
                dialog.showModal();
            } else {
                dialog.setAttribute('open', 'true');
            }

            dialog.querySelector('[data-dialog-close]')?.focus();
        });
    });

    dialog.querySelectorAll('[data-dialog-close]').forEach((button) => button.addEventListener('click', close));
    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) {
            close();
        }
    });

    dialog.querySelector('[data-dialog-borrow]')?.addEventListener('click', () => {
        showToast('Le prêt a été préparé. Confirmez votre choix dans votre espace.');
        close();
    });
}

function showToast(message) {
    const region = document.querySelector('[data-toast-region]');

    if (!region) {
        return;
    }

    const toast = document.createElement('div');
    toast.className = 'toast';
    toast.setAttribute('role', 'status');
    toast.textContent = message;
    region.append(toast);

    window.setTimeout(() => {
        toast.classList.add('toast--leaving');
        window.setTimeout(() => toast.remove(), 250);
    }, 4200);
}

function initToasts() {
    document.querySelectorAll('[data-toast-message]').forEach((button) => {
        button.addEventListener('click', () => {
            showToast(button.dataset.toastMessage ?? 'Action effectuée.');
        });
    });

    document.querySelector('[data-notification-button]')?.addEventListener('click', () => {
        showToast('Aucune nouvelle notification critique.');
    });

    document.querySelectorAll('[data-newsletter-form]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            showToast('Merci ! Votre inscription à la sélection est confirmée.');
            form.reset();
        });
    });
}

function initChat() {
    document.querySelectorAll('[data-chat]').forEach((chat) => {
        const form = chat.querySelector('[data-chat-form]');
        const messages = chat.querySelector('[data-chat-messages]');
        const input = chat.querySelector('[data-chat-input]');
        const submit = form?.querySelector('button[type="submit"]');
        const endpoint = chat.dataset.chatEndpoint;
        const token = chat.dataset.chatToken ?? '';

        if (!form || !messages || !input || !endpoint) {
            return;
        }

        let pending = false;

        const scrollToLatest = () => {
            messages.scrollTop = messages.scrollHeight;
        };

        const addMessage = (role, text) => {
            const message = document.createElement('div');
            message.className = `chat-message chat-message--${role}`;

            const avatar = document.createElement('span');
            avatar.className = 'chat-message__avatar';
            avatar.setAttribute('aria-hidden', 'true');
            avatar.textContent = role === 'user' ? 'YN' : '✦';

            const paragraph = document.createElement('p');
            paragraph.textContent = text;

            message.append(avatar, paragraph);
            messages.append(message);
            scrollToLatest();

            return message;
        };

        const addPendingMessage = () => {
            const message = document.createElement('div');
            message.className = 'chat-message chat-message--assistant chat-message--pending';
            message.setAttribute('role', 'status');
            message.setAttribute('aria-label', 'L’assistant rédige une réponse');

            const avatar = document.createElement('span');
            avatar.className = 'chat-message__avatar';
            avatar.setAttribute('aria-hidden', 'true');
            avatar.textContent = '✦';

            const bubble = document.createElement('p');
            bubble.className = 'chat-typing';
            for (let index = 0; index < 3; index += 1) {
                bubble.append(document.createElement('span'));
            }

            message.append(avatar, bubble);
            messages.append(message);
            scrollToLatest();

            return message;
        };

        const setPending = (state) => {
            pending = state;
            chat.setAttribute('aria-busy', String(state));

            if (submit) {
                submit.disabled = state;
            }
        };

        const readErrorMessage = async (response) => {
            const retryAfter = response.headers.get('Retry-After');

            if (response.status === 429) {
                return `Trop de demandes. Réessayez dans ${retryAfter ?? 'quelques'} seconde(s).`;
            }

            if (response.status === 403) {
                return 'Votre session de sécurité a expiré. Rechargez la page puis réessayez.';
            }

            try {
                const payload = await response.json();
                if (typeof payload?.error === 'string' && payload.error.trim() !== '') {
                    return payload.error;
                }
            } catch (_) {
                // Réponse non JSON : on garde le message générique.
            }

            return 'L’assistant est momentanément indisponible. Réessayez plus tard.';
        };

        const sendQuestion = async (question) => {
            const cleanQuestion = question.trim();

            if (cleanQuestion === '' || pending) {
                return;
            }

            addMessage('user', cleanQuestion);
            input.value = '';
            const pendingMessage = addPendingMessage();
            setPending(true);

            try {
                const response = await fetch(endpoint, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': token,
                    },
                    body: JSON.stringify({ prompt: cleanQuestion }),
                });

                if (response.redirected) {
                    window.location.assign(response.url);
                    return;
                }

                if (!response.ok) {
                    throw new Error(await readErrorMessage(response));
                }

                let payload = null;
                try {
                    payload = await response.json();
                } catch (_) {
                    // Réponse illisible : traitée comme une réponse vide.
                }

                const answer = typeof payload?.response === 'string' ? payload.response.trim() : '';

                if (answer === '') {
                    throw new Error('L’assistant n’a renvoyé aucune réponse.');
                }

                pendingMessage.remove();
                addMessage('assistant', answer);
            } catch (error) {
                pendingMessage.remove();
                addMessage('error', error instanceof Error ? error.message : 'Une erreur inattendue est survenue.');
            } finally {
                setPending(false);
                input.focus();
            }
        };

        form.addEventListener('submit', (event) => {
            event.preventDefault();
            sendQuestion(input.value);
        });

        input.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                form.requestSubmit();
            }
        });

        chat.parentElement?.querySelectorAll('[data-chat-prompt]').forEach((prompt) => {
            prompt.addEventListener('click', () => {
                sendQuestion(prompt.dataset.chatPrompt ?? '');
            });
        });
    });

    document.querySelectorAll('[data-chat-confirm]').forEach((button) => {
        button.addEventListener('click', () => {
            showToast('La prolongation a été préparée. Confirmez-la dans votre espace.');
        });
    });
}

function initTabs() {
    document.querySelectorAll('.segmented-tabs').forEach((tabs) => {
        const buttons = [...tabs.querySelectorAll('[role="tab"]')];

        buttons.forEach((button) => {
            button.addEventListener('click', () => {
                buttons.forEach((candidate) => candidate.setAttribute('aria-selected', String(candidate === button)));
            });
        });
    });
}

function initAuditFilters() {
    const filters = [...document.querySelectorAll('[data-audit-filter]')];
    const rows = [...document.querySelectorAll('[data-audit-row]')];

    if (filters.length === 0 || rows.length === 0) {
        return;
    }

    filters.forEach((filter) => {
        filter.addEventListener('click', () => {
            const selected = filter.dataset.auditFilter ?? 'all';

            filters.forEach((candidate) => candidate.classList.toggle('is-active', candidate === filter));
            rows.forEach((row) => {
                row.hidden = selected !== 'all' && row.dataset.auditCategory !== selected;
            });
        });
    });
}

function initCounters() {
    document.querySelectorAll('[data-count]').forEach((counter) => {
        const target = Number(counter.dataset.count);
        const finalText = counter.textContent ?? String(target);

        if (!Number.isFinite(target) || reducedMotion) {
            counter.textContent = finalText;
            return;
        }

        const state = { value: 0 };
        gsap.to(state, {
            value: target,
            duration: 1.5,
            ease: 'power2.out',
            scrollTrigger: {
                trigger: counter,
                start: 'top 90%',
                once: true,
            },
            onUpdate: () => {
                counter.textContent = Math.round(state.value).toLocaleString('fr-FR');
            },
        });
    });
}

function initMotion() {
    if (reducedMotion) {
        document.querySelectorAll('[data-reveal], [data-reveal-item]').forEach((element) => {
            element.dataset.motionReveal = 'disabled';
        });
        return;
    }

    const revealElements = [...document.querySelectorAll('[data-reveal], [data-reveal-item]')];
    revealElements.forEach((element) => {
        element.dataset.motionReveal = 'ready';
        gsap.set(element, { opacity: 0, y: 28 });
    });

    revealElements.forEach((element) => {
        gsap.to(element, {
            opacity: 1,
            y: 0,
            duration: 0.8,
            ease: 'power3.out',
            scrollTrigger: {
                trigger: element,
                start: 'top 88%',
                once: true,
            },
        });
    });

    const hero = document.querySelector('[data-hero]');
    if (hero) {
        const timeline = gsap.timeline({ defaults: { ease: 'power3.out' } });
        timeline
            .from('[data-hero-item]', { opacity: 0, y: 24, duration: 0.7, stagger: 0.1 })
            .from('[data-hero-title] .hero__line > span', { yPercent: 110, duration: 1, stagger: 0.12 }, '-=0.45')
            .from('[data-hero-visual]', { scale: 0.94, rotate: 2, duration: 1 }, '-=0.75');
    }

    document.querySelectorAll('[data-parallax-section]').forEach((section) => {
        gsap.to(section, {
            yPercent: -7,
            ease: 'none',
            scrollTrigger: {
                trigger: section,
                start: 'top bottom',
                end: 'bottom top',
                scrub: true,
            },
        });
    });

    document.querySelectorAll('.dashboard-panel, .metric-card').forEach((panel) => {
        gsap.from(panel, {
            opacity: 0,
            y: 18,
            duration: 0.55,
            ease: 'power2.out',
            stagger: 0.06,
            scrollTrigger: {
                trigger: panel,
                start: 'top 92%',
                once: true,
            },
        });
    });

    document.querySelectorAll('[data-magnetic]').forEach((element) => {
        const move = (event) => {
            const bounds = element.getBoundingClientRect();
            const x = (event.clientX - bounds.left - bounds.width / 2) * 0.16;
            const y = (event.clientY - bounds.top - bounds.height / 2) * 0.16;
            gsap.to(element, { x, y, duration: 0.35, ease: 'power2.out' });
        };

        const reset = () => gsap.to(element, { x: 0, y: 0, duration: 0.45, ease: 'elastic.out(1, 0.35)' });

        element.addEventListener('pointermove', move);
        element.addEventListener('pointerleave', reset);
    });

    if (!window.matchMedia('(pointer: fine)').matches) {
        return;
    }

    const cursor = document.createElement('div');
    cursor.className = 'cursor';
    cursor.dataset.cursor = '';
    cursor.setAttribute('aria-hidden', 'true');
    document.body.append(cursor);

    const moveX = gsap.quickTo(cursor, 'x', { duration: 0.22, ease: 'power3' });
    const moveY = gsap.quickTo(cursor, 'y', { duration: 0.22, ease: 'power3' });

    window.addEventListener('pointermove', (event) => {
        moveX(event.clientX);
        moveY(event.clientY);
        cursor.classList.add('open');
    });

    document.querySelectorAll('a, button, [data-book-card]').forEach((element) => {
        element.addEventListener('pointerenter', () => cursor.classList.add('open'));
        element.addEventListener('pointerleave', () => cursor.classList.remove('open'));
    });
}

function initSmoothScroll() {
    if (reducedMotion) {
        document.documentElement.style.scrollBehavior = 'auto';
        return;
    }

    const lenis = new Lenis({
        duration: 1.05,
        smoothWheel: true,
        syncTouch: false,
    });

    lenis.on('scroll', ScrollTrigger.update);
    gsap.ticker.add((time) => lenis.raf(time * 1000));
    gsap.ticker.lagSmoothing(0);
}

function init() {
    initThemes();
    initMobileNavigation();
    initSidebar();
    initPasswordToggles();
    initCatalog();
    initBookDialog();
    initToasts();
    initChat();
    initTabs();
    initAuditFilters();
    initCounters();
    initMotion();
    initSmoothScroll();

    window.addEventListener('load', () => ScrollTrigger.refresh(), { once: true });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init, { once: true });
} else {
    init();
}
