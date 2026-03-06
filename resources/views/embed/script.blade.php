(function () {
  'use strict';

  const EMBED_BASE_SERVER = @json($embedBaseUrl);
  const CONFIG_PATH = '/api/public/sites';
  const EVENTS_PATH = '/api/public/events';
  const SHOPIFY_COUNT_PATH = '/api/public/shopify/count';
  const SHOPIFY_ADD_TO_CART_PATH = '/api/public/shopify/add-to-cart';

  const scriptEl = document.currentScript;
  const scriptSrc = scriptEl && scriptEl.src ? scriptEl.src : '';
  /** Use script origin so embed works even when snippet was copied from localhost; API base is always where the script was loaded from. */
  let EMBED_BASE = EMBED_BASE_SERVER;
  try {
    if (scriptSrc && typeof URL !== 'undefined') {
      var _u = new URL(scriptSrc, typeof location !== 'undefined' ? location.href : 'https://example.com');
      EMBED_BASE = _u.origin;
    }
  } catch (e) {}

  const queryString = scriptSrc.indexOf('?') >= 0 ? scriptSrc.split('?')[1] || '' : '';
  const params = new URLSearchParams(queryString);
  const siteKey = params.get('site') || params.get('site_key');
  const debugMode = params.get('debug') === '1' || params.get('debug') === 'true';

  /**
   * Block registry: type -> function(blockConfig, siteKey). Block scripts register here via window.SiteBlocks.blockRegistry.
   */
  const blockRegistry = {};

  /** Show debug badge as soon as script runs (so we know the file loaded). Uses documentElement so it works before body exists. */
  if (debugMode && typeof document !== 'undefined') {
    try {
      var earlyId = 'siteblocks-debug-badge';
      var earlyEl = document.getElementById(earlyId);
      if (!earlyEl) {
        earlyEl = document.createElement('div');
        earlyEl.id = earlyId;
        earlyEl.setAttribute('data-embed', 'siteblocks');
        earlyEl.style.cssText = 'position:fixed;bottom:12px;right:12px;z-index:999999;background:#111;color:#0f0;font:12px monospace;padding:8px 12px;border-radius:6px;box-shadow:0 2px 8px rgba(0,0,0,0.3);max-width:280px;';
        (document.body || document.documentElement).appendChild(earlyEl);
      }
      earlyEl.textContent = 'SiteBlocks: script loaded, waiting for DOM…';
    } catch (e) {}
  }

  /** Show a visible badge on the page when debug=1 so you can see the script loaded without opening console */
  function showDebugBadge(text) {
    if (!debugMode || typeof document === 'undefined') return;
    var id = 'siteblocks-debug-badge';
    var el = document.getElementById(id);
    if (!el) {
      el = document.createElement('div');
      el.id = id;
      el.style.cssText = 'position:fixed;bottom:12px;right:12px;z-index:999999;background:#111;color:#0f0;font:12px monospace;padding:8px 12px;border-radius:6px;box-shadow:0 2px 8px rgba(0,0,0,0.3);max-width:280px;';
      var target = document.body || document.documentElement;
      if (target) target.appendChild(el);
    }
    if (el) el.textContent = text;
  }

  function log() {
    if (debugMode && typeof console !== 'undefined' && console.log) {
      console.log.apply(console, ['[Embed]'].concat(Array.prototype.slice.call(arguments)));
    }
  }

  /** Expose for block scripts: paths, registry, helpers. Block scripts read from this and register runners. */
  if (typeof window !== 'undefined') {
    window.SiteBlocks = {
      loaded: true,
      siteKey: siteKey || null,
      debug: debugMode,
      EMBED_BASE: EMBED_BASE,
      CONFIG_PATH: CONFIG_PATH,
      EVENTS_PATH: EVENTS_PATH,
      SHOPIFY_COUNT_PATH: SHOPIFY_COUNT_PATH,
      SHOPIFY_ADD_TO_CART_PATH: SHOPIFY_ADD_TO_CART_PATH,
      blockRegistry: blockRegistry,
      log: log,
      showDebugBadge: showDebugBadge
    };
  }

  /**
   * Check if the block should be shown based on display_rules (URL params, page type, path).
   * @param {Object} displayRules - Optional { url_param: {key, value}, page_types: [], url_path_contains: string }
   * @returns {boolean}
   */
  function shouldShowBlock(displayRules) {
    if (!displayRules || typeof displayRules !== 'object') return true;
    const url = typeof location !== 'undefined' ? location : { href: '', pathname: '' };
    const searchParams = typeof URLSearchParams !== 'undefined' ? new URLSearchParams((url.href || '').split('?')[1] || '') : null;

    if (displayRules.url_param && searchParams) {
      const key = displayRules.url_param.key;
      const want = displayRules.url_param.value;
      const have = searchParams.get(key);
      if (key && want !== undefined && have !== want) return false;
    }
    if (Array.isArray(displayRules.page_types) && displayRules.page_types.length > 0) {
      const path = (url.pathname || url.href.split('?')[0] || '').toLowerCase();
      const isProduct = /\/products\/[^/]+/.test(path) || path.indexOf('/product') >= 0;
      const isCollection = /\/collections\//.test(path);
      const isCart = /\/cart/.test(path);
      const isHome = path === '/' || path === '';
      let match = false;
      displayRules.page_types.forEach(function (t) {
        const type = String(t).toLowerCase();
        if (type === 'product' && isProduct) match = true;
        if (type === 'collection' && isCollection) match = true;
        if (type === 'cart' && isCart) match = true;
        if (type === 'home' && isHome) match = true;
      });
      if (!match) return false;
    }
    if (displayRules.url_path_contains) {
      const path = (url.pathname || '').toLowerCase();
      const sub = String(displayRules.url_path_contains).toLowerCase();
      if (path.indexOf(sub) < 0) return false;
    }
    return true;
  }

  /**
   * Fetch site config and run each block via the registry. Each block runs in try/catch.
   */
  function loadAndRun() {
    if (!siteKey) {
      log('Missing site key in script URL (?site=...)');
      showDebugBadge('SiteBlocks: missing ?site= in script URL');
      return;
    }
    showDebugBadge('SiteBlocks: loading...');
    log('Embed loaded for site:', siteKey);
    const configUrl = EMBED_BASE + CONFIG_PATH + '/' + encodeURIComponent(siteKey) + '/config';
    log('Fetching config:', configUrl);

    fetch(configUrl, { method: 'GET', credentials: 'omit' })
      .then(function (res) {
        if (!res.ok) throw new Error('Config ' + res.status);
        return res.json();
      })
      .then(function (data) {
        const blocks = data && data.blocks ? data.blocks : [];
        log('Blocks:', blocks.length);
        showDebugBadge('SiteBlocks: loaded, ' + blocks.length + ' block(s)');
        const types = blocks.length ? [...new Set(blocks.map(function (b) { return b.type; }))] : [];
        loadBlockScripts(types, blocks, runBlocks);
      })
      .catch(function (err) {
        log('Config fetch error', err);
        showDebugBadge('SiteBlocks: config failed (CORS? 404? open F12 → Network & Console)');
      });
  }

  /**
   * Load block scripts for given types, then run all blocks. Each script registers its runner in blockRegistry.
   * On script load error we continue; that block type is skipped when running.
   */
  function loadBlockScripts(types, blocks, runBlocks) {
    if (!types || types.length === 0) {
      runBlocks(blocks);
      return;
    }
    var pending = types.length;
    function done() {
      pending--;
      if (pending === 0) runBlocks(blocks);
    }
    types.forEach(function (type) {
      var script = document.createElement('script');
      script.src = EMBED_BASE + '/embed/blocks/' + encodeURIComponent(type) + '.js';
      script.onload = done;
      script.onerror = function () {
        log('Block script failed to load:', type);
        done();
      };
      (document.head || document.documentElement).appendChild(script);
    });
  }

  /**
   * Run each block: shouldShowBlock filter and try/catch per block so one failure does not break others.
   */
  function runBlocks(blocks) {
    blocks.forEach(function (block) {
      if (!shouldShowBlock(block.display_rules)) {
        log('Block skipped by display_rules:', block.id);
        return;
      }
      var runner = blockRegistry[block.type];
      if (typeof runner !== 'function') {
        log('No runner for type:', block.type);
        return;
      }
      try {
        runner(block, siteKey);
      } catch (err) {
        log('Block error', block.id, block.type, err);
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', loadAndRun);
  } else {
    loadAndRun();
  }
})();
