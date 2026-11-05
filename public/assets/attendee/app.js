(() => {
    'use strict';

    const API = '/api/v1';
    const app = document.getElementById('app');
    const headerNav = document.getElementById('header-nav');
    const toastEl = document.getElementById('toast');

    const state = {
        token: localStorage.getItem('ws_token') || '',
        user: readUser(),
        eventCache: new Map(),
        registrations: null,
        toastTimer: null,
    };

    /* ------------------------------------------------------------------ */
    /* Utilities                                                          */
    /* ------------------------------------------------------------------ */

    function readUser() {
        try {
            return JSON.parse(localStorage.getItem('ws_user') || 'null');
        } catch (error) {
            return null;
        }
    }

    function saveSession(token, user) {
        state.token = token;
        state.user = user;
        state.registrations = null;
        localStorage.setItem('ws_token', token);
        localStorage.setItem('ws_user', JSON.stringify(user));
    }

    function clearSession() {
        state.token = '';
        state.user = null;
        state.registrations = null;
        localStorage.removeItem('ws_token');
        localStorage.removeItem('ws_user');
    }

    function esc(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function money(value) {
        if (value === null || value === undefined) {
            return '—';
        }

        const number = Number(value);
        const text = Number.isInteger(number) ? String(number) : number.toFixed(2);

        return text + '.-';
    }

    function sum(values) {
        return values.reduce((total, value) => total + Number(value || 0), 0);
    }

    function dateObject(iso) {
        if (!iso) {
            return null;
        }

        return new Date(iso + 'T00:00:00');
    }

    function longDate(iso) {
        const date = dateObject(iso);

        if (!date) {
            return '';
        }

        return new Intl.DateTimeFormat('en-US', {
            weekday: 'long',
            month: 'long',
            day: 'numeric',
            year: 'numeric',
        }).format(date);
    }

    function shortDate(iso) {
        const date = dateObject(iso);

        return date
            ? {
                day: new Intl.DateTimeFormat('en-US', { day: 'numeric' }).format(date),
                month: new Intl.DateTimeFormat('en-US', { month: 'short' }).format(date),
            }
            : { day: '', month: '' };
    }

    function minutesOf(dateTime) {
        const time = String(dateTime).split(' ')[1] || '00:00:00';
        const parts = time.split(':').map(Number);

        return (parts[0] || 0) * 60 + (parts[1] || 0);
    }

    function clockOf(dateTime) {
        const time = String(dateTime).split(' ')[1] || '00:00:00';

        return time.slice(0, 5);
    }

    function clockFromMinutes(minutes) {
        const hours = Math.floor(minutes / 60);
        const rest = minutes % 60;

        return `${String(hours).padStart(2, '0')}:${String(rest).padStart(2, '0')}`;
    }

    function toast(message, type = 'success') {
        clearTimeout(state.toastTimer);
        toastEl.textContent = message;
        toastEl.className = 'toast show' + (type === 'error' ? ' error' : '');
        state.toastTimer = setTimeout(() => {
            toastEl.className = 'toast';
        }, 3600);
    }

    async function api(path, options = {}) {
        const response = await fetch(API + path, {
            method: options.method || 'GET',
            headers: {
                Accept: 'application/json',
                ...(options.body ? { 'Content-Type': 'application/json' } : {}),
            },
            body: options.body ? JSON.stringify(options.body) : undefined,
        });

        const text = await response.text();
        let data = null;

        if (text) {
            try {
                data = JSON.parse(text);
            } catch (error) {
                data = { message: text };
            }
        }

        if (!response.ok) {
            const error = new Error((data && data.message) || 'Request failed');
            error.status = response.status;
            error.data = data;
            throw error;
        }

        return data;
    }

    function loader(label = 'Loading…') {
        app.innerHTML = `
            <div class="loading-state">
                <span class="spinner" aria-hidden="true"></span>
                <p>${esc(label)}</p>
            </div>`;
    }

    function notFound(message = 'We could not find the page you are looking for.') {
        app.innerHTML = `
            <section class="not-found">
                <div class="code">404</div>
                <h1>Page not found</h1>
                <p>${esc(message)}</p>
                <a class="btn-primary" href="#/">Browse events</a>
            </section>`;
    }

    function failureMessage(error) {
        if (error.status === 404) {
            return error.message || 'Not found';
        }

        return error.message || 'Something went wrong. Please try again.';
    }

    /* ------------------------------------------------------------------ */
    /* Header                                                             */
    /* ------------------------------------------------------------------ */

    function renderHeader() {
        if (state.token && state.user) {
            const username = state.user.username || 'attendee';
            headerNav.innerHTML = `
                <span class="user-chip">
                    <span class="avatar" aria-hidden="true">${esc(username.slice(0, 2))}</span>
                    <span id="current-user">${esc(username)}</span>
                </span>
                <button type="button" class="logout-btn" id="logout-btn">Logout</button>`;

            const logout = document.getElementById('logout-btn');
            logout.addEventListener('click', async () => {
                logout.disabled = true;

                try {
                    if (state.token) {
                        await api('/logout?token=' + encodeURIComponent(state.token), { method: 'POST' });
                    }
                } catch (error) {
                    // The local session is cleared even if the token already expired.
                }

                clearSession();
                renderHeader();
                toast('You have been logged out.');
                location.hash = '#/login';
            });

            return;
        }

        headerNav.innerHTML = `
            <a class="login-btn" href="#/login?redirect=${encodeURIComponent(location.hash || '#/')}">
                Login
            </a>`;
    }

    /* ------------------------------------------------------------------ */
    /* Data helpers                                                       */
    /* ------------------------------------------------------------------ */

    async function getEvent(organizerSlug, eventSlug) {
        const key = organizerSlug + '/' + eventSlug;

        if (!state.eventCache.has(key)) {
            state.eventCache.set(key, await api(`/organizers/${encodeURIComponent(organizerSlug)}/events/${encodeURIComponent(eventSlug)}`));
        }

        return state.eventCache.get(key);
    }

    async function getRegistrations() {
        if (!state.token) {
            return [];
        }

        if (state.registrations) {
            return state.registrations;
        }

        try {
            const data = await api('/registrations?token=' + encodeURIComponent(state.token));
            state.registrations = data.registrations || [];
        } catch (error) {
            if (error.status === 401) {
                clearSession();
                renderHeader();
            }
            state.registrations = [];
        }

        return state.registrations;
    }

    function registrationFor(registrations, eventId) {
        return registrations.find((registration) => registration.event.id === eventId) || null;
    }

    function allSessions(event) {
        const sessions = [];

        (event.channels || []).forEach((channel) => {
            (channel.rooms || []).forEach((room) => {
                (room.sessions || []).forEach((session) => {
                    sessions.push({ ...session, room: room.name, channel: channel.name });
                });
            });
        });

        return sessions.sort((a, b) => a.start.localeCompare(b.start) || a.id - b.id);
    }

    /* ------------------------------------------------------------------ */
    /* Events list                                                        */
    /* ------------------------------------------------------------------ */

    async function renderEvents() {
        loader('Loading the programme…');
        const data = await api('/events');
        const events = data.events || [];

        const cards = events.map((event, index) => {
            const date = shortDate(event.date);

            return `
                <a class="event" href="#/event/${encodeURIComponent(event.organizer.slug)}/${encodeURIComponent(event.slug)}"
                   style="animation-delay:${Math.min(index * 45, 320)}ms">
                    <div class="event-date">
                        <span class="day">${esc(date.day)}</span>
                        <span class="month">${esc(date.month)}</span>
                    </div>
                    <div>
                        <h2 class="event-name">${esc(event.name)}</h2>
                        <p class="event-meta">${esc(event.organizer.name)} · ${esc(longDate(event.date))}</p>
                    </div>
                    <span class="event-arrow" aria-hidden="true">↗</span>
                </a>`;
        }).join('');

        app.innerHTML = `
            <section class="hero">
                <div class="hero-grid">
                    <div>
                        <p class="eyebrow">WorldSkills conference programme</p>
                        <h1 class="display">Find your next<br>conference.</h1>
                        <p class="hero-text">
                            Explore upcoming events from every organizer, study the agenda,
                            and reserve your place in the rooms that matter to you.
                        </p>
                    </div>
                    <aside class="hero-note">
                        <strong>How it works</strong>
                        Browse freely. Sign in with your last name and registration code when
                        you are ready to register.
                    </aside>
                </div>
            </section>

            <section aria-label="Upcoming events">
                ${events.length
                    ? `<div class="event-list">${cards}</div>`
                    : `<div class="empty-state">There are no upcoming events right now. Please check back soon.</div>`}
            </section>`;
    }

    /* ------------------------------------------------------------------ */
    /* Agenda                                                             */
    /* ------------------------------------------------------------------ */

    async function renderAgenda(organizerSlug, eventSlug) {
        loader('Loading the agenda…');

        const [event, registrations] = await Promise.all([
            getEvent(organizerSlug, eventSlug),
            getRegistrations(),
        ]);

        const registration = registrationFor(registrations, event.id);
        const registeredSessions = new Set(registration ? registration.session_ids : []);
        const sessions = allSessions(event);

        if (!sessions.length) {
            app.innerHTML = eventHeader(event, registration, organizerSlug, eventSlug) + `
                <div class="empty-state">The agenda for this event has not been published yet.</div>`;
            bindEventActions(event, organizerSlug, eventSlug, registration);
            return;
        }

        const starts = sessions.map((session) => minutesOf(session.start));
        const ends = sessions.map((session) => minutesOf(session.end));
        const startMinute = Math.floor(Math.min(...starts) / 60) * 60;
        const endMinute = Math.ceil(Math.max(...ends) / 60) * 60;
        const span = Math.max(endMinute - startMinute, 60);
        const hours = Math.max(Math.round(span / 60), 1);
        const dayHeight = Math.max(560, hours * 90);

        const percent = (minute) => ((minute - startMinute) / span) * 100;

        const rooms = [];
        (event.channels || []).forEach((channel) => {
            (channel.rooms || []).forEach((room) => {
                rooms.push({ room, channel });
            });
        });

        let roomColumn = 2;
        const channelBands = (event.channels || []).map((channel) => {
            const count = Math.max((channel.rooms || []).length, 1);
            const band = `
                <div class="day-channel" style="grid-column:${roomColumn} / span ${count}">
                    ${esc(channel.name)}
                </div>`;
            roomColumn += count;
            return band;
        }).join('');

        const roomHeads = rooms.map(({ room }, index) => `
            <div class="day-room" style="grid-column:${index + 2}">
                ${esc(room.name)}
            </div>
        `).join('');

        const hourTicks = [];
        for (let minute = startMinute; minute <= endMinute; minute += 60) {
            hourTicks.push(minute);
        }

        const axis = hourTicks.map((minute, index) => {
            const position = percent(minute);
            const edge = index === 0 ? ' edge-start' : (index === hourTicks.length - 1 ? ' edge-end' : '');

            return `<span class="day-hour${edge}" style="top:${position}%">${clockFromMinutes(minute)}</span>`;
        }).join('');

        const columns = rooms.map(({ room }, index) => {
            const cards = (room.sessions || []).map((session) => {
                const isRegistered = Boolean(registration)
                    && (session.type === 'talk' || registeredSessions.has(session.id));

                const top = percent(minutesOf(session.start));
                const height = Math.max(percent(minutesOf(session.end)) - top, 6);
                const duration = minutesOf(session.end) - minutesOf(session.start);
                const short = duration <= 45;
                const detailed = duration >= 90;

                return `
                    <a class="day-session type-${esc(session.type)} ${isRegistered ? 'registered' : ''} ${short ? 'short' : ''}"
                       style="top:calc(${top}% + 3px);height:calc(${height}% - 6px)"
                       href="#/event/${encodeURIComponent(organizerSlug)}/${encodeURIComponent(eventSlug)}/session/${session.id}"
                       title="${esc(session.title)} · ${esc(clockOf(session.start))}–${esc(clockOf(session.end))}">
                        <span class="day-session-time">${esc(clockOf(session.start))}–${esc(clockOf(session.end))}</span>
                        <span class="day-session-title">${esc(session.title)}</span>
                        <span class="day-session-speaker">${esc(session.speaker)}</span>
                        ${detailed && session.description
                            ? `<span class="day-session-desc">${esc(session.description)}</span>`
                            : ''}
                    </a>`;
            }).join('');

            return `<div class="day-col" style="grid-column:${index + 2}">${cards}</div>`;
        }).join('');

        app.innerHTML = eventHeader(event, registration, organizerSlug, eventSlug) + `
            <section class="agenda-card" aria-label="Event agenda">
                <div class="agenda-scroll">
                    <div class="agenda-day" style="--rooms:${Math.max(rooms.length, 1)};--hours:${hours};--day-height:${dayHeight}px">
                        <div class="day-head">
                            <div class="day-corner">Time</div>
                            ${channelBands}
                            ${roomHeads}
                        </div>
                        <div class="day-body">
                            <div class="day-axis">${axis}</div>
                            ${columns}
                        </div>
                    </div>
                </div>
                <div class="agenda-legend">
                    <span class="legend-item"><span class="swatch talk"></span> Talk</span>
                    <span class="legend-item"><span class="swatch workshop"></span> Workshop</span>
                    <span class="legend-item"><span class="swatch registered"></span> Included in your registration</span>
                </div>
            </section>
            ${registration ? ratingBox('event', event.id, 'How was this event?') : ''}`;

        bindEventActions(event, organizerSlug, eventSlug, registration);
        if (registration) {
            bindRating('event', event.id);
        }
    }

    function eventHeader(event, registration, organizerSlug, eventSlug) {
        const eventUrl = `#/event/${encodeURIComponent(organizerSlug)}/${encodeURIComponent(eventSlug)}`;

        return `
            <a class="back-link" href="#/">← All events</a>
            <section class="event-hero">
                <div>
                    <p class="eyebrow">Event programme</p>
                    <h1 class="display">${esc(event.name)}</h1>
                    <div class="event-sub">
                        <span>${esc(longDate(event.date))}</span>
                        <span>${event.channels.length} channels</span>
                    </div>
                </div>
                <div class="event-actions">
                    ${registration
                        ? `<span class="registered-badge">✓ Registered</span>
                           <a class="btn-ghost" href="${eventUrl}/register">Details</a>`
                        : `<a class="btn-primary" id="register" href="${eventUrl}/register">
                              Register for this event
                           </a>`}
                </div>
            </section>`;
    }

    function bindEventActions() {
        // Links are handled by the hash router; this hook exists for future actions.
    }

    /* ------------------------------------------------------------------ */
    /* Session detail                                                     */
    /* ------------------------------------------------------------------ */

    async function renderSession(organizerSlug, eventSlug, sessionId) {
        loader('Loading session…');

        const [event, registrations] = await Promise.all([
            getEvent(organizerSlug, eventSlug),
            getRegistrations(),
        ]);

        const session = allSessions(event).find((item) => String(item.id) === String(sessionId));

        if (!session) {
            notFound('This session does not exist in the selected event.');
            return;
        }

        const registration = registrationFor(registrations, event.id);
        const registeredSessions = new Set(registration ? registration.session_ids : []);
        const isRegistered = Boolean(registration)
            && (session.type === 'talk' || registeredSessions.has(session.id));

        app.innerHTML = `
            <a class="back-link" href="#/event/${encodeURIComponent(organizerSlug)}/${encodeURIComponent(eventSlug)}">
                ← Back to ${esc(event.name)}
            </a>
            <div class="detail-shell">
                <article class="detail-card">
                    <span class="type-pill ${esc(session.type)}">${esc(session.type)}</span>
                    ${isRegistered ? '<span class="registered-badge" style="margin-left:8px">✓ In your agenda</span>' : ''}
                    <h1 class="detail-title">${esc(session.title)}</h1>
                    <p class="detail-speaker">with ${esc(session.speaker)}</p>
                    <p class="detail-description">${esc(session.description || 'No description provided.')}</p>

                    <div class="meta-grid">
                        <div class="meta-item">
                            <span class="meta-label">Starts</span>
                            <span class="meta-value">${esc(clockOf(session.start))}</span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">Ends</span>
                            <span class="meta-value">${esc(clockOf(session.end))}</span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">Cost</span>
                            <span class="meta-value">${session.cost === null || Number(session.cost) === 0
                                ? (session.type === 'talk' ? 'Included' : 'Free')
                                : money(session.cost)}</span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">Room</span>
                            <span class="meta-value">${esc(session.room)}</span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">Channel</span>
                            <span class="meta-value">${esc(session.channel)}</span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">Date</span>
                            <span class="meta-value">${esc(longDate(event.date))}</span>
                        </div>
                    </div>

                    ${registration ? ratingBox('session', session.id, 'How was this session?') : ''}
                </article>
            </div>`;

        if (registration) {
            bindRating('session', session.id);
        }
    }

    /* ------------------------------------------------------------------ */
    /* Registration                                                       */
    /* ------------------------------------------------------------------ */

    async function renderRegistration(organizerSlug, eventSlug) {
        if (!state.token) {
            location.hash = `#/login?redirect=${encodeURIComponent(location.hash)}`;
            return;
        }

        loader('Preparing registration…');

        const [event, registrations] = await Promise.all([
            getEvent(organizerSlug, eventSlug),
            getRegistrations(),
        ]);

        if (!state.token) {
            location.hash = `#/login?redirect=${encodeURIComponent(location.hash)}`;
            return;
        }

        const registration = registrationFor(registrations, event.id);

        if (registration) {
            app.innerHTML = `
                <a class="back-link" href="#/event/${encodeURIComponent(organizerSlug)}/${encodeURIComponent(eventSlug)}">
                    ← Back to ${esc(event.name)}
                </a>
                <div class="panel" style="max-width:640px">
                    <h2 class="panel-title">You are already registered</h2>
                    <p class="panel-hint">Your place at ${esc(event.name)} is confirmed.</p>
                    <div class="cost-row"><span>Workshops in your agenda</span><span>${registration.session_ids.length}</span></div>
                    <a class="btn-primary" style="margin-top:18px"
                       href="#/event/${encodeURIComponent(organizerSlug)}/${encodeURIComponent(eventSlug)}">
                        View my agenda
                    </a>
                </div>`;
            return;
        }

        const workshops = allSessions(event).filter((session) => session.type === 'workshop');

        const tickets = (event.tickets || []).map((ticket) => `
            <label class="ticket ${ticket.available ? '' : 'unavailable'}">
                <input type="radio" name="ticket" value="${ticket.id}"
                       data-cost="${Number(ticket.cost || 0)}"
                       ${ticket.available ? '' : 'disabled'}>
                <span class="ticket-box" aria-hidden="true"></span>
                <span>
                    <span class="ticket-name">${esc(ticket.name)}</span>
                    <span class="ticket-note">${esc(ticket.available ? (ticket.description || 'Available') : 'No longer available')}</span>
                </span>
                <span class="ticket-cost">${money(ticket.cost)}</span>
            </label>`).join('');

        const workshopList = workshops.length
            ? workshops.map((session) => `
                <label class="workshop">
                    <input type="checkbox" name="workshop" value="${session.id}"
                           data-cost="${Number(session.cost || 0)}">
                    <span class="workshop-box" aria-hidden="true"></span>
                    <span>
                        <span class="workshop-name">${esc(session.title)}</span>
                        <span class="workshop-meta">${esc(session.room)} · ${esc(clockOf(session.start))}–${esc(clockOf(session.end))}</span>
                    </span>
                    <span class="workshop-cost">${Number(session.cost || 0) === 0 ? 'Free' : money(session.cost)}</span>
                </label>`).join('')
            : '<p class="panel-hint">This event has no additional workshops.</p>';

        app.innerHTML = `
            <a class="back-link" href="#/event/${encodeURIComponent(organizerSlug)}/${encodeURIComponent(eventSlug)}">
                ← Back to ${esc(event.name)}
            </a>
            <div class="event-hero" style="padding-top:0">
                <div>
                    <p class="eyebrow">Registration</p>
                    <h1 class="display" style="font-size:clamp(32px,4vw,48px)">${esc(event.name)}</h1>
                    <div class="event-sub"><span>${esc(longDate(event.date))}</span></div>
                </div>
            </div>

            <div class="registration-layout">
                <div>
                    <section class="panel">
                        <h2 class="panel-title">Choose your ticket</h2>
                        <p class="panel-hint">Ticket availability is checked by the event platform.</p>
                        <div class="ticket-grid">${tickets}</div>
                    </section>

                    <section class="panel">
                        <h2 class="panel-title">Select additional workshops</h2>
                        <p class="panel-hint">Talks are included with every ticket. Workshops can be added below.</p>
                        <div class="workshop-list">${workshopList}</div>
                    </section>
                </div>

                <aside class="panel summary">
                    <h2 class="panel-title">Order summary</h2>
                    <div class="cost-row"><span>Event ticket</span><span id="event-cost">—</span></div>
                    <div class="cost-row"><span>Additional workshops</span><span id="additional-cost">0.-</span></div>
                    <div class="cost-row total"><span>Total</span><span id="total-cost">0.-</span></div>
                    <div id="purchase-error"></div>
                    <button type="button" class="btn-primary" id="purchase" disabled>Purchase</button>
                    <p class="summary-note">You will receive a confirmation immediately. Your registration
                        is linked to your attendee account.</p>
                </aside>
            </div>`;

        const purchase = document.getElementById('purchase');
        const eventCost = document.getElementById('event-cost');
        const additionalCost = document.getElementById('additional-cost');
        const totalCost = document.getElementById('total-cost');
        const errorBox = document.getElementById('purchase-error');

        function ticketInputs() {
            return Array.from(document.querySelectorAll('input[name="ticket"]'));
        }

        function workshopInputs() {
            return Array.from(document.querySelectorAll('input[name="workshop"]'));
        }

        function refresh() {
            const ticket = ticketInputs().find((input) => input.checked);
            const workshopTotal = sum(
                workshopInputs().filter((input) => input.checked).map((input) => input.dataset.cost)
            );

            ticketInputs().forEach((input) => {
                input.closest('.ticket').classList.toggle('selected', input.checked);
            });

            workshopInputs().forEach((input) => {
                input.closest('.workshop').classList.toggle('selected', input.checked);
            });

            eventCost.textContent = ticket ? money(ticket.dataset.cost) : '—';
            additionalCost.textContent = money(workshopTotal);
            totalCost.textContent = ticket ? money(Number(ticket.dataset.cost) + workshopTotal) : '0.-';
            purchase.disabled = !ticket;
        }

        ticketInputs().forEach((input) => input.addEventListener('change', refresh));
        workshopInputs().forEach((input) => input.addEventListener('change', refresh));

        purchase.addEventListener('click', async () => {
            const ticket = ticketInputs().find((input) => input.checked);

            if (!ticket) {
                return;
            }

            purchase.disabled = true;
            purchase.textContent = 'Processing…';
            errorBox.innerHTML = '';

            const sessionIds = workshopInputs()
                .filter((input) => input.checked)
                .map((input) => Number(input.value));

            try {
                await api(
                    `/organizers/${encodeURIComponent(organizerSlug)}/events/${encodeURIComponent(eventSlug)}/registration?token=${encodeURIComponent(state.token)}`,
                    {
                        method: 'POST',
                        body: { ticket_id: Number(ticket.value), session_ids: sessionIds },
                    }
                );

                state.registrations = null;
                state.eventCache.delete(organizerSlug + '/' + eventSlug);
                toast('Registration successful');
                location.hash = `#/event/${encodeURIComponent(organizerSlug)}/${encodeURIComponent(eventSlug)}`;
            } catch (error) {
                if (error.status === 401 && /not logged in/i.test(error.message)) {
                    clearSession();
                    renderHeader();
                    location.hash = `#/login?redirect=${encodeURIComponent(location.hash)}`;
                    return;
                }

                errorBox.innerHTML = `<p class="form-error">${esc(failureMessage(error))}</p>`;
                toast(failureMessage(error), 'error');
                purchase.disabled = false;
                purchase.textContent = 'Purchase';
            }
        });

        refresh();
    }

    /* ------------------------------------------------------------------ */
    /* Login                                                              */
    /* ------------------------------------------------------------------ */

    async function renderLogin(query) {
        if (state.token) {
            location.hash = '#/';
            return;
        }

        const redirect = query.get('redirect') || '#/';

        app.innerHTML = `
            <div class="auth-wrap">
                <div class="auth-copy">
                    <p class="eyebrow">Attendee sign in</p>
                    <h1 class="display">Your place is<br>waiting.</h1>
                    <p>Use the last name and six-character registration code from your
                       attendee confirmation to manage your registrations.</p>
                </div>

                <section class="auth-card">
                    <h2>Login</h2>
                    <p class="hint">Enter the credentials from your registration email.</p>
                    <div id="login-error"></div>
                    <form id="login-form" novalidate>
                        <div class="field">
                            <label for="lastname">Lastname</label>
                            <input id="lastname" name="lastname" autocomplete="family-name"
                                   placeholder="Yakovich" required>
                        </div>
                        <div class="field">
                            <label for="registration_code">Registration Code</label>
                            <input id="registration_code" name="registration_code" maxlength="6"
                                   autocomplete="one-time-code" placeholder="35DGZX" required>
                        </div>
                        <button type="submit" class="btn-primary" id="login">Login</button>
                    </form>
                    <p class="auth-foot">Demo account: <strong>Yakovich</strong> / <strong>35DGZX</strong></p>
                </section>
            </div>`;

        const form = document.getElementById('login-form');
        const errorBox = document.getElementById('login-error');

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            errorBox.innerHTML = '';

            const button = document.getElementById('login');
            button.disabled = true;
            button.textContent = 'Signing in…';

            try {
                const data = await api('/login', {
                    method: 'POST',
                    body: {
                        lastname: form.lastname.value.trim(),
                        registration_code: form.registration_code.value.trim(),
                    },
                });

                saveSession(data.token, data);
                state.registrations = null;
                renderHeader();
                toast(`Welcome back, ${data.username}`);
                location.hash = redirect.startsWith('#') ? redirect : '#/';
            } catch (error) {
                errorBox.innerHTML = `<p class="form-error">Lastname or registration code not correct</p>`;
                button.disabled = false;
                button.textContent = 'Login';
                form.registration_code.focus();
            }
        });
    }

    /* ------------------------------------------------------------------ */
    /* Ratings                                                            */
    /* ------------------------------------------------------------------ */

    function ratingBox(type, id, prompt) {
        return `
            <div class="rating-box" data-rating-type="${type}" data-rating-id="${id}">
                <p>${esc(prompt)}</p>
                <div class="stars" role="group" aria-label="Rating from 1 to 5">
                    ${[1, 2, 3, 4, 5].map((value) => `
                        <button type="button" class="star" data-value="${value}"
                                aria-label="${value} out of 5">★</button>`).join('')}
                </div>
                <span class="rating-status" aria-live="polite"></span>
            </div>`;
    }

    async function bindRating(type, id) {
        const box = document.querySelector(`.rating-box[data-rating-type="${type}"][data-rating-id="${id}"]`);

        if (!box || !state.token) {
            return;
        }

        const stars = Array.from(box.querySelectorAll('.star'));
        const status = box.querySelector('.rating-status');

        function paint(value) {
            stars.forEach((star) => {
                star.classList.toggle('active', Number(star.dataset.value) <= value);
            });
        }

        try {
            const data = await api('/ratings?token=' + encodeURIComponent(state.token));
            const list = type === 'event' ? data.events : data.sessions;
            const key = type === 'event' ? 'event_id' : 'session_id';
            const existing = (list || []).find((item) => Number(item[key]) === Number(id));

            if (existing) {
                paint(existing.rating);
                status.textContent = 'Saved';
            }
        } catch (error) {
            // Ratings are optional; ignore loading issues.
        }

        stars.forEach((star) => {
            star.addEventListener('click', async () => {
                const value = Number(star.dataset.value);

                try {
                    await api(`/${type === 'event' ? 'events' : 'sessions'}/${id}/rating?token=${encodeURIComponent(state.token)}`, {
                        method: 'POST',
                        body: { rating: value },
                    });
                    paint(value);
                    status.textContent = 'Thanks for rating';
                    toast('Your rating has been saved');
                } catch (error) {
                    toast(failureMessage(error), 'error');
                }
            });
        });
    }

    /* ------------------------------------------------------------------ */
    /* Router                                                             */
    /* ------------------------------------------------------------------ */

    async function route() {
        const hash = location.hash || '#/';
        const [pathPart, queryPart] = hash.replace(/^#/, '').split('?');
        const segments = pathPart.split('/').filter(Boolean).map((segment) => decodeURIComponent(segment));
        const query = new URLSearchParams(queryPart || '');

        renderHeader();
        window.scrollTo(0, 0);

        try {
            if (segments.length === 0) {
                await renderEvents();
                return;
            }

            if (segments[0] === 'login') {
                await renderLogin(query);
                return;
            }

            if (segments[0] === 'event' && segments.length >= 3) {
                const [organizerSlug, eventSlug] = [segments[1], segments[2]];

                if (segments[3] === 'session' && segments[4]) {
                    await renderSession(organizerSlug, eventSlug, segments[4]);
                    return;
                }

                if (segments[3] === 'register') {
                    await renderRegistration(organizerSlug, eventSlug);
                    return;
                }

                await renderAgenda(organizerSlug, eventSlug);
                return;
            }

            notFound();
        } catch (error) {
            if (error.status === 404) {
                notFound(failureMessage(error));
                return;
            }

            app.innerHTML = `
                <section class="not-found">
                    <div class="code">!</div>
                    <h1>Something went wrong</h1>
                    <p>${esc(failureMessage(error))}</p>
                    <a class="btn-primary" href="#/">Back to events</a>
                </section>`;
        }
    }

    window.addEventListener('hashchange', route);

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', route);
    } else {
        route();
    }
})();
