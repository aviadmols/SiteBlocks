(function () {
  'use strict';

  const EMBED_BASE = @json($embedBaseUrl);
  const CONFIG_PATH = '/api/public/sites';
  const EVENTS_PATH = '/api/public/events';
  const SHOPIFY_COUNT_PATH = '/api/public/shopify/count';
  const SHOPIFY_ADD_TO_CART_PATH = '/api/public/shopify/add-to-cart';

  const scriptEl = document.currentScript;
  const scriptSrc = scriptEl && scriptEl.src ? scriptEl.src : '';
  const queryString = scriptSrc.indexOf('?') >= 0 ? scriptSrc.split('?')[1] || '' : '';
  const params = new URLSearchParams(queryString);
  const siteKey = params.get('site') || params.get('site_key');
  const debugMode = params.get('debug') === '1' || params.get('debug') === 'true';

  /** Expose load state so you can check in console: e.g. window.SiteBlocks */
  if (typeof window !== 'undefined') {
    window.SiteBlocks = { loaded: true, siteKey: siteKey || null, debug: debugMode };
  }

  function log() {
    if (debugMode && typeof console !== 'undefined' && console.log) {
      console.log.apply(console, ['[Embed]'].concat(Array.prototype.slice.call(arguments)));
    }
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
      return;
    }
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
        blocks.forEach(function (block) {
          if (!shouldShowBlock(block.display_rules)) {
            log('Block skipped by display_rules:', block.id);
            return;
          }
          const runner = blockRegistry[block.type];
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
      })
      .catch(function (err) {
        log('Config fetch error', err);
      });
  }

  /**
   * Block registry: type -> function(blockConfig, siteKey).
   */
  const blockRegistry = {};

  /**
   * Shopify Add To Cart Counter: show count under add-to-cart, intercept /cart/add.js.
   */
  function runShopifyAddToCartCounter(block, siteKey) {
    const settings = block.settings || {};
    const targetSelector = settings.target_selector || '[data-product-form], form[action*="/cart/add"]';
    const insertPosition = settings.insert_position || 'after';
    const messageTemplate = settings.message_template || 'This product was added to cart {{count}} times';
    const messageClass = settings.message_class || 'embed-add-to-cart-count';
    const minCountToShow = settings.min_count_to_show != null ? Number(settings.min_count_to_show) : 0;
    const countScope = settings.count_scope || 'variant';
    const apiBase = EMBED_BASE;

    function getProductId() {
      try {
        if (typeof window.ShopifyAnalytics !== 'undefined' && window.ShopifyAnalytics.meta && window.ShopifyAnalytics.meta.product && window.ShopifyAnalytics.meta.product.id) {
          return String(window.ShopifyAnalytics.meta.product.id);
        }
        if (typeof window.meta !== 'undefined' && window.meta && window.meta.product && window.meta.product.id) {
          return String(window.meta.product.id);
        }
      } catch (e) {}
      return null;
    }

    function getVariantIdFromForm(form) {
      if (!form) return null;
      const input = form.querySelector('input[name="id"]');
      return input ? String(input.value).trim() : null;
    }

    function getIds() {
      const productId = getProductId();
      const form = document.querySelector('form[action*="/cart/add"]');
      const variantId = getVariantIdFromForm(form);
      const scope = countScope === 'product' && productId ? 'product' : 'variant';
      const pid = scope === 'product' ? productId : null;
      const vid = scope === 'variant' ? (variantId || productId) : null;
      return { scope: scope, productId: pid, variantId: vid };
    }

    function buildCountUrl(ids) {
      const u = apiBase + SHOPIFY_COUNT_PATH + '?site_key=' + encodeURIComponent(siteKey);
      if (ids.productId) u += '&product_id=' + encodeURIComponent(ids.productId);
      if (ids.variantId) u += '&variant_id=' + encodeURIComponent(ids.variantId);
      return u;
    }

    function renderMessage(count) {
      const text = messageTemplate.replace(/\{\{\s*count\s*\}\}/g, String(count));
      const el = document.createElement('div');
      el.className = messageClass;
      el.setAttribute('data-embed-block-id', String(block.id));
      el.textContent = text;
      return el;
    }

    function placeMessage(el, anchor) {
      if (!anchor || !anchor.parentNode) return;
      switch (insertPosition) {
        case 'before':
          anchor.parentNode.insertBefore(el, anchor);
          break;
        case 'prepend':
          anchor.insertBefore(el, anchor.firstChild);
          break;
        case 'append':
          anchor.appendChild(el);
          break;
        default:
          anchor.parentNode.insertBefore(el, anchor.nextSibling);
      }
    }

    function updateDisplay(count, messageEl) {
      if (messageEl) {
        const text = messageTemplate.replace(/\{\{\s*count\s*\}\}/g, String(count));
        messageEl.textContent = text;
      }
    }

    function fetchCountAndShow(anchor, messageEl) {
      const ids = getIds();
      if (ids.scope === 'variant' && !ids.variantId) return;
      if (ids.scope === 'product' && !ids.productId) return;

      fetch(buildCountUrl(ids), { method: 'GET', credentials: 'omit' })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          const count = data && typeof data.count === 'number' ? data.count : 0;
          if (count >= minCountToShow) {
            if (!messageEl) {
              messageEl = renderMessage(count);
              placeMessage(messageEl, anchor);
            }
            updateDisplay(count, messageEl);
          }
        })
        .catch(function () {});
    }

    function incrementAndRefresh(ids, pageUrl) {
      const body = {
        site_key: siteKey,
        block_id: block.id,
        product_id: ids.productId || null,
        variant_id: ids.variantId || null,
        page_url: pageUrl || (typeof location !== 'undefined' ? location.href : '')
      };
      fetch(apiBase + SHOPIFY_ADD_TO_CART_PATH, {
        method: 'POST',
        credentials: 'omit',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body)
      })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          const count = data && typeof data.count === 'number' ? data.count : 0;
          const anchor = document.querySelector(targetSelector);
          const messageEl = anchor ? anchor.parentNode.querySelector('.' + messageClass) : null;
          if (count >= minCountToShow) {
            if (!messageEl && anchor) {
              const newEl = renderMessage(count);
              placeMessage(newEl, anchor);
            } else {
              updateDisplay(count, messageEl);
            }
          }
        })
        .catch(function () {});
    }

    const anchor = document.querySelector(targetSelector);
    if (anchor) {
      fetchCountAndShow(anchor, null);
    }

    const origFetch = window.fetch;
    if (typeof origFetch === 'function') {
      window.fetch = function (url, opts) {
        const req = origFetch.apply(this, arguments);
        const urlStr = typeof url === 'string' ? url : (url && url.url) || '';
        if (urlStr.indexOf('/cart/add.js') >= 0) {
          req.then(function (res) {
            if (res && res.ok && res.clone) {
              res.clone().json().then(function (body) {
                try {
                  const variantId = body.variant_id ? String(body.variant_id) : null;
                  const productId = getProductId();
                  const scope = countScope === 'product' && productId ? 'product' : 'variant';
                  const ids = { scope: scope, productId: productId, variantId: variantId || getVariantIdFromForm(document.querySelector('form[action*="/cart/add"]')) };
                  if (ids.variantId || ids.productId) {
                    incrementAndRefresh(ids, typeof location !== 'undefined' ? location.href : '');
                  }
                } catch (e) {}
              }).catch(function () {});
            }
          });
        }
        return req;
      };
    }
  }

  blockRegistry.shopify_add_to_cart_counter = runShopifyAddToCartCounter;

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', loadAndRun);
  } else {
    loadAndRun();
  }
})();
