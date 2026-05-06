'use strict';
const assert = require('assert');
const fs     = require('fs');
const vm     = require('vm');
const path   = require('path');

const SRC = fs.readFileSync(
    path.join(__dirname, '..', 'ele-conditions', 'assets', 'js', 'triggers.js'), 'utf8'
);

// ── Test runner ───────────────────────────────────────────────
let passed = 0, failed = 0;
function test(name, fn) {
    try {
        fn();
        process.stdout.write('  ✓ ' + name + '\n');
        passed++;
    } catch(e) {
        process.stdout.write('  ✗ ' + name + '\n    → ' + e.message + '\n');
        failed++;
    }
}
function section(title) { console.log('\n── ' + title); }

// ── Mock element factory ──────────────────────────────────────
function makeEl(triggers, opts) {
    opts = opts || {};
    const styleMap   = {};
    const classSet   = new Set();
    const listenersMap = {};

    (opts.classes || []).forEach(c => classSet.add(c));

    return {
        style: {
            get display()    { return styleMap.display; },
            set display(v)   { styleMap.display = v; },
            get visibility() { return styleMap.visibility; },
            set visibility(v){ styleMap.visibility = v; },
            removeProperty(p){ delete styleMap[p]; },
            _map: styleMap,
        },
        classList: {
            add   : (c) => classSet.add(c),
            remove: (c) => classSet.delete(c),
            toggle: (c) => classSet.has(c) ? classSet.delete(c) : classSet.add(c),
            has   : (c) => classSet.has(c),
            _set  : classSet,
        },
        _focused  : false,
        _scrolled : false,
        _listeners: listenersMap,
        _selector : opts.selector || null,
        addEventListener(event, fn) {
            listenersMap[event] = listenersMap[event] || [];
            listenersMap[event].push(fn);
        },
        focus()          { this._focused = true; },
        scrollIntoView() { this._scrolled = true; },
        getAttribute(name) {
            if (name === 'data-elecond-triggers')
                return triggers != null ? JSON.stringify(triggers) : null;
            if (name === 'data-elecond-hide-initially')
                return opts.hideInitially ? '1' : null;
            return null;
        },
    };
}

// ── Sandbox runner ────────────────────────────────────────────
function run(elements, opts) {
    opts = opts || {};
    const lsData       = opts.ls || {};
    const storage      = Object.assign({}, lsData);
    const docListeners = {};
    const timeouts     = [];
    const allEls       = (opts.extraEls || []).concat(elements);

    const sandbox = {
        localStorage: opts.throwLS
            ? { getItem() { throw new Error('Storage disabled'); },
                setItem() { throw new Error('Storage disabled'); } }
            : { getItem : k => k in storage ? storage[k] : null,
                setItem : (k, v) => { storage[k] = String(v); } },

        document: {
            readyState: 'complete',
            querySelectorAll(sel) {
                if (sel === '[data-elecond-triggers]')
                    return allEls.filter(e => e.getAttribute('data-elecond-triggers') !== null);
                if (sel === '[data-elecond-hide-initially]')
                    return allEls.filter(e => e.getAttribute('data-elecond-hide-initially') !== null);
                if (sel.startsWith('.')) {
                    const cls = sel.slice(1);
                    return allEls.filter(e => e.classList.has(cls));
                }
                return [];
            },
            querySelector(sel) {
                return allEls.find(e => e._selector === sel) || null;
            },
            addEventListener(e, fn) {
                (docListeners[e] = docListeners[e] || []).push(fn);
            },
        },

        window: {
            location   : { pathname: opts.pathname || '/test-page' },
            getComputedStyle(el) {
                return { display: el.style._map.display || 'block' };
            },
            IntersectionObserver: opts.realIO
                ? function(cb) {
                    let observedEl = null;
                    const obs = {
                        observe(el) { observedEl = el; },
                        unobserve() {},
                        fire() { cb([{ isIntersecting: true }], obs); },
                    };
                    return obs;
                }
                : null,
        },

        setTimeout: (fn, ms) => { timeouts.push({ fn, ms }); },
        console,
    };

    vm.runInNewContext(SRC, sandbox);
    return { storage, docListeners, timeouts };
}

// ── 1. localStorage safety ────────────────────────────────────
section('1. localStorage safety (private browsing)');

test('script does not crash when localStorage throws', () => {
    // Should complete without throwing
    run([], { throwLS: true });
});

test('visitCount defaults to 1 when localStorage throws', () => {
    // first_visit action should fire because visitCount === 1 is the default
    const el = makeEl([{ trigger_type: 'first_visit', action_type: 'hide' }]);
    run([el], { throwLS: true });
    assert.strictEqual(el.style._map.display, 'none',
        'first_visit should fire (visitCount=1 when LS unavailable)');
});

test('A/B trigger does not crash when localStorage throws', () => {
    const el = makeEl([{ trigger_type: 'ab_group_a', action_type: 'hide', trigger_ab_name: 'test' }]);
    run([el], { throwLS: true });
    // No assertion on result — just verifying no crash
});

// ── 2. Visit counting ─────────────────────────────────────────
section('2. Visit counting');

test('first page load stores visitCount = 1', () => {
    const { storage } = run([], { pathname: '/about', ls: {} });
    assert.strictEqual(storage['elecond_v_/about'], '1');
});

test('second page load increments visitCount to 2', () => {
    const { storage } = run([], {
        pathname: '/about',
        ls: { 'elecond_v_/about': '1' },
    });
    assert.strictEqual(storage['elecond_v_/about'], '2');
});

test('visit counts are independent per URL', () => {
    const { storage: s1 } = run([], { pathname: '/home',  ls: { 'elecond_v_/home':  '3' } });
    const { storage: s2 } = run([], { pathname: '/about', ls: { 'elecond_v_/about': '1' } });
    assert.strictEqual(s1['elecond_v_/home'],  '4');
    assert.strictEqual(s2['elecond_v_/about'], '2');
});

// ── 3. Hide initially ─────────────────────────────────────────
section('3. Hide initially');

test('element with data-elecond-hide-initially gets display:none', () => {
    const el = makeEl(null, { hideInitially: true });
    run([el]);
    assert.strictEqual(el.style._map.display, 'none');
});

test('element without hideInitially is unaffected', () => {
    const el = makeEl(null, { hideInitially: false });
    run([el]);
    assert.strictEqual(el.style._map.display, undefined);
});

test('hideInitially applies even with no triggers_list', () => {
    const el = makeEl(null, { hideInitially: true }); // triggers = null, hideInitially = true
    run([el]);
    assert.strictEqual(el.style._map.display, 'none');
});

// ── 4. first_visit trigger ────────────────────────────────────
section('4. first_visit trigger');

test('first_visit fires on visit #1', () => {
    const el = makeEl([{ trigger_type: 'first_visit', action_type: 'hide' }]);
    run([el], { ls: {} });
    assert.strictEqual(el.style._map.display, 'none');
});

test('first_visit does NOT fire on visit #2', () => {
    const el = makeEl([{ trigger_type: 'first_visit', action_type: 'hide' }]);
    run([el], { ls: { 'elecond_v_/test-page': '1' } });
    assert.notStrictEqual(el.style._map.display, 'none');
});

test('first_visit does NOT fire on visit #5', () => {
    const el = makeEl([{ trigger_type: 'first_visit', action_type: 'hide' }]);
    run([el], { ls: { 'elecond_v_/test-page': '4' } });
    assert.notStrictEqual(el.style._map.display, 'none');
});

// ── 5. nth_visit trigger ──────────────────────────────────────
section('5. nth_visit trigger');

test('nth_visit fires on exactly the Nth visit', () => {
    const el = makeEl([{ trigger_type: 'nth_visit', action_type: 'hide', trigger_visit_count: '3' }]);
    run([el], { ls: { 'elecond_v_/test-page': '2' } }); // stored=2, visitCount becomes 3
    assert.strictEqual(el.style._map.display, 'none');
});

test('nth_visit does NOT fire before the Nth visit', () => {
    const el = makeEl([{ trigger_type: 'nth_visit', action_type: 'hide', trigger_visit_count: '5' }]);
    run([el], { ls: { 'elecond_v_/test-page': '2' } }); // visitCount=3, need 5
    assert.notStrictEqual(el.style._map.display, 'none');
});

test('nth_visit does NOT fire after the Nth visit', () => {
    const el = makeEl([{ trigger_type: 'nth_visit', action_type: 'hide', trigger_visit_count: '2' }]);
    run([el], { ls: { 'elecond_v_/test-page': '5' } }); // visitCount=6, needed 2
    assert.notStrictEqual(el.style._map.display, 'none');
});

test('nth_visit uses default of 2 when trigger_visit_count is missing', () => {
    const el = makeEl([{ trigger_type: 'nth_visit', action_type: 'hide' }]);
    run([el], { ls: { 'elecond_v_/test-page': '1' } }); // visitCount=2
    assert.strictEqual(el.style._map.display, 'none');
});

// ── 6. A/B trigger ────────────────────────────────────────────
section('6. A/B group triggers');

test('ab_group_a fires when group is a', () => {
    const el = makeEl([{ trigger_type: 'ab_group_a', action_type: 'hide', trigger_ab_name: 'hero' }]);
    run([el], { ls: { 'elecond_ab_hero': 'a' } });
    assert.strictEqual(el.style._map.display, 'none');
});

test('ab_group_a does NOT fire when group is b', () => {
    const el = makeEl([{ trigger_type: 'ab_group_a', action_type: 'hide', trigger_ab_name: 'hero' }]);
    run([el], { ls: { 'elecond_ab_hero': 'b' } });
    assert.notStrictEqual(el.style._map.display, 'none');
});

test('ab_group_b fires when group is b', () => {
    const el = makeEl([{ trigger_type: 'ab_group_b', action_type: 'hide', trigger_ab_name: 'hero' }]);
    run([el], { ls: { 'elecond_ab_hero': 'b' } });
    assert.strictEqual(el.style._map.display, 'none');
});

test('ab_group_b does NOT fire when group is a', () => {
    const el = makeEl([{ trigger_type: 'ab_group_b', action_type: 'hide', trigger_ab_name: 'hero' }]);
    run([el], { ls: { 'elecond_ab_hero': 'a' } });
    assert.notStrictEqual(el.style._map.display, 'none');
});

test('A/B group assigned and persisted on first run', () => {
    const { storage } = run([], { ls: {} });
    // Force group assignment by running an ab trigger
    const el = makeEl([{ trigger_type: 'ab_group_a', action_type: 'hide', trigger_ab_name: 'promo' }]);
    const { storage: s } = run([el], { ls: {} });
    const group = s['elecond_ab_promo'];
    assert.ok(group === 'a' || group === 'b', 'group must be a or b');
});

test('A/B group is consistent across runs for same name', () => {
    // Run once to assign group
    const el1 = makeEl([{ trigger_type: 'ab_group_a', action_type: 'hide', trigger_ab_name: 'banner' }]);
    const { storage: s1 } = run([el1], { ls: {} });
    const group = s1['elecond_ab_banner'];

    // Second run with persisted group — ab trigger result should be consistent
    const el2 = makeEl([{ trigger_type: 'ab_group_a', action_type: 'hide', trigger_ab_name: 'banner' }]);
    run([el2], { ls: s1 });

    if (group === 'a') {
        assert.strictEqual(el2.style._map.display, 'none', 'group a: hide should fire again');
    } else {
        assert.notStrictEqual(el2.style._map.display, 'none', 'group b: hide should not fire');
    }
});

test('different A/B test names have independent groups', () => {
    const { storage } = run([], { ls: {} });
    const el1 = makeEl([{ trigger_type: 'ab_group_a', action_type: 'hide', trigger_ab_name: 'test1' }]);
    const el2 = makeEl([{ trigger_type: 'ab_group_a', action_type: 'hide', trigger_ab_name: 'test2' }]);
    const { storage: s } = run([el1, el2], { ls: {} });
    // Both groups are assigned
    assert.ok('elecond_ab_test1' in s);
    assert.ok('elecond_ab_test2' in s);
});

// ── 7. Event-based triggers (listener attachment) ─────────────
section('7. Event-based triggers');

test('click trigger attaches click listener', () => {
    const el = makeEl([{ trigger_type: 'click', action_type: 'hide' }]);
    run([el]);
    assert.ok(el._listeners.click && el._listeners.click.length > 0,
        'click listener must be registered');
});

test('click listener fires action (hide)', () => {
    const el = makeEl([{ trigger_type: 'click', action_type: 'hide' }]);
    run([el]);
    el._listeners.click[0]({ stopPropagation: () => {} });
    assert.strictEqual(el.style._map.display, 'none');
});

test('hover trigger attaches mouseenter listener', () => {
    const el = makeEl([{ trigger_type: 'hover', action_type: 'add_class', action_class: 'hovered' }]);
    run([el]);
    assert.ok(el._listeners.mouseenter && el._listeners.mouseenter.length > 0);
});

test('hover listener fires action (add_class)', () => {
    const el = makeEl([{ trigger_type: 'hover', action_type: 'add_class', action_class: 'active' }]);
    run([el]);
    el._listeners.mouseenter[0]();
    assert.ok(el.classList.has('active'));
});

test('delay trigger schedules setTimeout with correct ms', () => {
    const el = makeEl([{ trigger_type: 'delay', action_type: 'hide', trigger_delay_ms: '2500' }]);
    const { timeouts } = run([el]);
    const found = timeouts.find(t => t.ms === 2500);
    assert.ok(found, 'setTimeout(fn, 2500) must be registered');
});

test('delay trigger fires action when timeout runs', () => {
    const el = makeEl([{ trigger_type: 'delay', action_type: 'hide', trigger_delay_ms: '1000' }]);
    const { timeouts } = run([el]);
    timeouts[0].fn();
    assert.strictEqual(el.style._map.display, 'none');
});

test('time_on_page schedules setTimeout(fn, seconds * 1000)', () => {
    const el = makeEl([{ trigger_type: 'time_on_page', action_type: 'hide', trigger_time_seconds: '15' }]);
    const { timeouts } = run([el]);
    const found = timeouts.find(t => t.ms === 15000);
    assert.ok(found, 'setTimeout must be called with 15000ms');
});

test('time_on_page defaults to 10s when trigger_time_seconds missing', () => {
    const el = makeEl([{ trigger_type: 'time_on_page', action_type: 'hide' }]);
    const { timeouts } = run([el]);
    const found = timeouts.find(t => t.ms === 10000);
    assert.ok(found);
});

// ── 8. Exit intent ────────────────────────────────────────────
section('8. Exit intent');

test('exit_intent attaches mouseleave listener to document', () => {
    const el = makeEl([{ trigger_type: 'exit_intent', action_type: 'hide' }]);
    const { docListeners } = run([el]);
    assert.ok(docListeners.mouseleave && docListeners.mouseleave.length > 0);
});

test('exit_intent fires when clientY <= 0', () => {
    const el = makeEl([{ trigger_type: 'exit_intent', action_type: 'hide' }]);
    const { docListeners } = run([el]);
    docListeners.mouseleave[0]({ clientY: 0 });
    assert.strictEqual(el.style._map.display, 'none');
});

test('exit_intent does NOT fire when clientY > 0 (scroll, not exit)', () => {
    const el = makeEl([{ trigger_type: 'exit_intent', action_type: 'hide' }]);
    const { docListeners } = run([el]);
    docListeners.mouseleave[0]({ clientY: 50 });
    assert.notStrictEqual(el.style._map.display, 'none');
});

test('exit_intent fires only once (callbacks cleared after first fire)', () => {
    // Use toggle_class: first fire adds class, second fire would remove it
    const el = makeEl([{ trigger_type: 'exit_intent', action_type: 'toggle_class', action_class: 'exited' }]);
    const { docListeners } = run([el]);
    docListeners.mouseleave[0]({ clientY: 0 }); // first exit → class added
    docListeners.mouseleave[0]({ clientY: 0 }); // second exit → should NOT toggle again
    assert.ok(el.classList.has('exited'), 'class must still be present (callback fired only once)');
});

test('only one mouseleave listener added regardless of multiple exit_intent triggers', () => {
    const el1 = makeEl([{ trigger_type: 'exit_intent', action_type: 'hide' }]);
    const el2 = makeEl([{ trigger_type: 'exit_intent', action_type: 'add_class', action_class: 'exited' }]);
    const { docListeners } = run([el1, el2]);
    assert.strictEqual(docListeners.mouseleave.length, 1, 'only one mouseleave listener');
});

test('all exit_intent actions fire in one event', () => {
    const el1 = makeEl([{ trigger_type: 'exit_intent', action_type: 'hide' }]);
    const el2 = makeEl([{ trigger_type: 'exit_intent', action_type: 'add_class', action_class: 'exited' }]);
    const { docListeners } = run([el1, el2]);
    docListeners.mouseleave[0]({ clientY: 0 });
    assert.strictEqual(el1.style._map.display, 'none');
    assert.ok(el2.classList.has('exited'));
});

// ── 9. scroll_into_view ───────────────────────────────────────
section('9. scroll_into_view');

test('scroll_into_view fires action when IntersectionObserver triggers', () => {
    // IntersectionObserver must be a top-level global (code uses `new IntersectionObserver`, not `new window.IntersectionObserver`)
    const el = makeEl([{ trigger_type: 'scroll_into_view', action_type: 'hide' }]);
    function IOMock(cb) {
        const obs = { observe(target) { cb([{ isIntersecting: true }], obs); }, unobserve() {} };
        return obs;
    }
    const sandbox = {
        localStorage: { getItem: () => null, setItem: () => {} },
        IntersectionObserver: IOMock,   // top-level global (used by `new IntersectionObserver`)
        document: {
            readyState: 'complete',
            querySelectorAll(sel) {
                return sel === '[data-elecond-triggers]' ? [el] : [];
            },
            querySelector() { return null; },
            addEventListener() {},
        },
        window: {
            location: { pathname: '/test' },
            getComputedStyle: (e) => ({ display: e.style._map.display || 'block' }),
            IntersectionObserver: IOMock, // checked via `window.IntersectionObserver` guard
        },
        setTimeout: () => {},
        console,
    };
    vm.runInNewContext(SRC, sandbox);
    assert.strictEqual(el.style._map.display, 'none');
});

test('scroll_into_view skipped silently when IntersectionObserver unavailable', () => {
    const el = makeEl([{ trigger_type: 'scroll_into_view', action_type: 'hide' }]);
    const sandbox = {
        localStorage: { getItem: () => null, setItem: () => {} },
        document: {
            readyState: 'complete',
            querySelectorAll(sel) {
                return sel === '[data-elecond-triggers]' ? [el] : [];
            },
            querySelector() { return null; },
            addEventListener() {},
        },
        window: {
            location: { pathname: '/test' },
            getComputedStyle: (e) => ({ display: 'block' }),
            IntersectionObserver: null, // not supported
        },
        setTimeout: () => {},
        console,
    };
    vm.runInNewContext(SRC, sandbox); // must not crash
    assert.notStrictEqual(el.style._map.display, 'none');
});

// ── 10. executeAction: show / hide / toggle ───────────────────
section('10. executeAction: show / hide / toggle');

test('hide sets display to none', () => {
    const el = makeEl([{ trigger_type: 'click', action_type: 'hide' }]);
    run([el]);
    el._listeners.click[0]({ stopPropagation: () => {} });
    assert.strictEqual(el.style._map.display, 'none');
});

test('show removes display (reveals element)', () => {
    const el = makeEl([{ trigger_type: 'click', action_type: 'show' }]);
    el.style.display = 'none'; // hidden initially
    run([el]);
    el._listeners.click[0]({ stopPropagation: () => {} });
    assert.strictEqual(el.style._map.display, undefined, 'display property removed');
});

test('toggle: hides visible element', () => {
    const el = makeEl([{ trigger_type: 'click', action_type: 'toggle' }]);
    // style._map.display is undefined → getComputedStyle returns 'block'
    run([el]);
    el._listeners.click[0]({ stopPropagation: () => {} });
    assert.strictEqual(el.style._map.display, 'none');
});

test('toggle: shows hidden element', () => {
    const el = makeEl([{ trigger_type: 'click', action_type: 'toggle' }]);
    el.style.display = 'none';
    run([el]);
    el._listeners.click[0]({ stopPropagation: () => {} });
    assert.strictEqual(el.style._map.display, undefined, 'display property removed after toggle');
});

// ── 11. executeAction: classes ────────────────────────────────
section('11. executeAction: add/remove/toggle class');

test('add_class adds CSS class to element', () => {
    const el = makeEl([{ trigger_type: 'click', action_type: 'add_class', action_class: 'is-active' }]);
    run([el]);
    el._listeners.click[0]({ stopPropagation: () => {} });
    assert.ok(el.classList.has('is-active'));
});

test('remove_class removes CSS class from element', () => {
    const el = makeEl([{ trigger_type: 'click', action_type: 'remove_class', action_class: 'is-active' }],
                      { classes: ['is-active'] });
    run([el]);
    el._listeners.click[0]({ stopPropagation: () => {} });
    assert.ok(!el.classList.has('is-active'));
});

test('toggle_class adds class when absent', () => {
    const el = makeEl([{ trigger_type: 'click', action_type: 'toggle_class', action_class: 'open' }]);
    run([el]);
    el._listeners.click[0]({ stopPropagation: () => {} });
    assert.ok(el.classList.has('open'));
});

test('toggle_class removes class when present', () => {
    const el = makeEl([{ trigger_type: 'click', action_type: 'toggle_class', action_class: 'open' }],
                      { classes: ['open'] });
    run([el]);
    el._listeners.click[0]({ stopPropagation: () => {} });
    assert.ok(!el.classList.has('open'));
});

// ── 12. executeAction: focus / scroll_to ─────────────────────
section('12. executeAction: focus / scroll_to');

test('focus calls focus() on target', () => {
    const el = makeEl([{ trigger_type: 'click', action_type: 'focus' }]);
    run([el]);
    el._listeners.click[0]({ stopPropagation: () => {} });
    assert.ok(el._focused);
});

test('scroll_to calls scrollIntoView() on target', () => {
    const el = makeEl([{ trigger_type: 'click', action_type: 'scroll_to' }]);
    run([el]);
    el._listeners.click[0]({ stopPropagation: () => {} });
    assert.ok(el._scrolled);
});

// ── 13. executeAction: close_others ──────────────────────────
section('13. executeAction: close_others');

test('close_others hides sibling elements with group class', () => {
    const el1 = makeEl([{ trigger_type: 'click', action_type: 'close_others', action_group_class: 'accordion' }],
                        { classes: ['accordion'] });
    const el2 = makeEl(null, { classes: ['accordion'] });
    const el3 = makeEl(null, { classes: ['accordion'] });
    run([el1], { extraEls: [el2, el3] });
    el1._listeners.click[0]({ stopPropagation: () => {} });
    assert.strictEqual(el2.style._map.display, 'none', 'sibling el2 must be hidden');
    assert.strictEqual(el3.style._map.display, 'none', 'sibling el3 must be hidden');
});

test('close_others does NOT hide the clicked element itself', () => {
    const el1 = makeEl([{ trigger_type: 'click', action_type: 'close_others', action_group_class: 'accordion' }],
                        { classes: ['accordion'] });
    const el2 = makeEl(null, { classes: ['accordion'] });
    run([el1], { extraEls: [el2] });
    el1._listeners.click[0]({ stopPropagation: () => {} });
    assert.notStrictEqual(el1.style._map.display, 'none', 'clicked element must stay visible');
});

test('close_others does nothing when action_group_class is empty', () => {
    const el1 = makeEl([{ trigger_type: 'click', action_type: 'close_others', action_group_class: '' }],
                        { classes: ['group'] });
    const el2 = makeEl(null, { classes: ['group'] });
    run([el1], { extraEls: [el2] });
    el1._listeners.click[0]({ stopPropagation: () => {} });
    assert.notStrictEqual(el2.style._map.display, 'none', 'no group class → nothing hidden');
});

// ── 14. CSS selector target ───────────────────────────────────
section('14. Target resolution (CSS selector)');

test('empty action_target applies action to self', () => {
    const el = makeEl([{ trigger_type: 'click', action_type: 'hide', action_target: '' }]);
    run([el]);
    el._listeners.click[0]({ stopPropagation: () => {} });
    assert.strictEqual(el.style._map.display, 'none');
});

test('CSS selector routes action to a different element', () => {
    const target = makeEl(null, { selector: '#my-target' });
    const trigger = makeEl([{ trigger_type: 'click', action_type: 'hide', action_target: '#my-target' }]);
    run([trigger], { extraEls: [target] });
    trigger._listeners.click[0]({ stopPropagation: () => {} });
    assert.strictEqual(target.style._map.display, 'none', 'target element must be hidden');
    assert.notStrictEqual(trigger.style._map.display, 'none', 'trigger element must be unaffected');
});

// ── 15. Error resilience ──────────────────────────────────────
section('15. Error resilience');

test('invalid JSON in data-elecond-triggers does not crash', () => {
    const el = {
        style: { _map: {}, removeProperty() {} },
        classList: { has: () => false, add() {}, remove() {}, toggle() {} },
        _listeners: {},
        addEventListener() {},
        focus() {},
        scrollIntoView() {},
        getAttribute(name) {
            return name === 'data-elecond-triggers' ? '{bad json{{' : null;
        },
    };
    run([el]); // must not throw
});

test('non-array JSON in data-elecond-triggers does not crash', () => {
    const el = {
        style: { _map: {}, removeProperty() {} },
        classList: { has: () => false, add() {}, remove() {}, toggle() {} },
        _listeners: {},
        addEventListener() {},
        focus() {},
        scrollIntoView() {},
        getAttribute(name) {
            return name === 'data-elecond-triggers' ? '"just-a-string"' : null;
        },
    };
    run([el]); // must not throw
});

test('multiple triggers on same element all attach', () => {
    const el = makeEl([
        { trigger_type: 'click', action_type: 'hide' },
        { trigger_type: 'hover', action_type: 'add_class', action_class: 'hovered' },
    ]);
    run([el]);
    assert.ok(el._listeners.click       && el._listeners.click.length       > 0);
    assert.ok(el._listeners.mouseenter  && el._listeners.mouseenter.length  > 0);
});

// ── Summary ───────────────────────────────────────────────────
console.log('\n' + '─'.repeat(50));
console.log('Results: ' + passed + ' passed, ' + failed + ' failed out of ' + (passed + failed) + ' tests');
if (failed > 0) {
    process.exit(1);
}
