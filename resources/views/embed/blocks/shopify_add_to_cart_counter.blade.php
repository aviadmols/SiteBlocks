// Shopify Add To Cart Counter block script. Loaded dynamically by embed loader. Reads from window.SiteBlocks.
(function () {
  'use strict';
  const siteBlocks = typeof window !== 'undefined' && window.SiteBlocks;
  if (!siteBlocks || !siteBlocks.blockRegistry) return;
  const EMBED_BASE = siteBlocks.EMBED_BASE || '';
  const SHOPIFY_COUNT_PATH = siteBlocks.SHOPIFY_COUNT_PATH || '/api/public/shopify/count';
  const SHOPIFY_ADD_TO_CART_PATH = siteBlocks.SHOPIFY_ADD_TO_CART_PATH || '/api/public/shopify/add-to-cart';
  const blockRegistry = siteBlocks.blockRegistry;

  // Global guard & de-dup for add-to-cart counting + fetch patch.
  var GLOBAL_KEY = '__siteBlocksShopifyAddToCartCounter';
  var g = (typeof window !== 'undefined' ? window : null);
  if (g && !g[GLOBAL_KEY]) {
    g[GLOBAL_KEY] = {
      fetchPatched: false,
      lastIncrementKey: null,
      lastIncrementTs: 0
    };
  }
  var globalState = g ? g[GLOBAL_KEY] : { fetchPatched: false, lastIncrementKey: null, lastIncrementTs: 0 };

  function buildIncrementKey(ids, pageUrl) {
    return String(ids.productId || '') + '::' + String(ids.variantId || '') + '::' + String(pageUrl || '');
  }

  function shouldIncrementOnce(ids, pageUrl) {
    try {
      var now = typeof Date !== 'undefined' && Date.now ? Date.now() : new Date().getTime();
      var key = buildIncrementKey(ids, pageUrl);
      if (globalState.lastIncrementKey === key && globalState.lastIncrementTs && (now - globalState.lastIncrementTs) < 1500) {
        return false;
      }
      globalState.lastIncrementKey = key;
      globalState.lastIncrementTs = now;
      return true;
    } catch (e) {
      return true;
    }
  }

  function runShopifyAddToCartCounter(block, siteKey) {
    var settings = block.settings || {};
    var targetSelector = settings.target_selector || '[data-product-form], form[action*="/cart/add"]';
    var insertPosition = settings.insert_position || 'after';
    var messageTemplate = settings.message_template || 'This product was added to cart {{ count }} times';
    var messageClass = settings.message_class || 'embed-add-to-cart-count';
    var messageMainClass = String(messageClass).split(/\s+/)[0] || 'embed-add-to-cart-count';
    var minCountToShow = settings.min_count_to_show != null ? Number(settings.min_count_to_show) : 0;
    var countScope = settings.count_scope || 'variant';
    var apiBase = EMBED_BASE;
    var debug = !!(settings.debug || (siteBlocks && siteBlocks.debug));
    function log() {
      if (debug && siteBlocks && typeof siteBlocks.log === 'function') {
        siteBlocks.log.apply(siteBlocks, ['[AddToCartCounter]'].concat(Array.prototype.slice.call(arguments)));
      }
    }

    if (settings.custom_css && typeof document !== 'undefined' && document.head) {
      try {
        var styleId = 'data-embed-block-style-' + String(block.id);
        if (!document.getElementById(styleId)) {
          var styleEl = document.createElement('style');
          styleEl.id = styleId;
          styleEl.setAttribute('data-embed-block-id', String(block.id));
          styleEl.textContent = String(settings.custom_css);
          document.head.appendChild(styleEl);
        }
      } catch (e) {}
    }

    function getProductId(form) {
      try {
        if (typeof window.ShopifyAnalytics !== 'undefined' && window.ShopifyAnalytics.meta && window.ShopifyAnalytics.meta.product && window.ShopifyAnalytics.meta.product.id) {
          return String(window.ShopifyAnalytics.meta.product.id);
        }
        if (typeof window.meta !== 'undefined' && window.meta && window.meta.product && window.meta.product.id) {
          return String(window.meta.product.id);
        }
        if (typeof document !== 'undefined') {
          var formEl = form || document.querySelector('form[action*="/cart/add"]');
          if (formEl && formEl.getAttribute('data-product-id')) return String(formEl.getAttribute('data-product-id')).trim();
          var dataEl = document.querySelector('[data-product-id]');
          if (dataEl && dataEl.getAttribute('data-product-id')) return String(dataEl.getAttribute('data-product-id')).trim();
          var jsonLd = document.querySelector('script[type="application/ld+json"]');
          if (jsonLd && jsonLd.textContent) {
            try {
              var obj = JSON.parse(jsonLd.textContent);
              var item = Array.isArray(obj) ? obj.find(function (x) { return x && x['@type'] === 'Product'; }) : (obj && obj['@type'] === 'Product' ? obj : null);
              if (item && item['@id']) {
                var match = String(item['@id']).match(/\/(\d+)$/);
                if (match) return match[1];
              }
              if (item && item.productID) return String(item.productID);
            } catch (e2) {}
          }
        }
      } catch (e) {}
      return null;
    }

    function getVariantIdFromForm(form) {
      if (!form) return null;
      var input = form.querySelector('input[name="id"]') || form.querySelector('input[name="variant_id"]');
      if (input && input.value) return String(input.value).trim();
      if (form.getAttribute && form.getAttribute('data-variant-id')) return String(form.getAttribute('data-variant-id')).trim();
      var dataEl = form.querySelector('[data-variant-id]');
      if (dataEl && dataEl.getAttribute('data-variant-id')) return String(dataEl.getAttribute('data-variant-id')).trim();
      return null;
    }

    function findNearestCartAddForm(startEl) {
      try {
        if (!startEl || typeof document === 'undefined') return document.querySelector('form[action*="/cart/add"]');
        if (startEl.tagName === 'FORM') return startEl;
        if (startEl.closest) {
          var c = startEl.closest('form[action*="/cart/add"]');
          if (c) return c;
        }
        if (startEl.querySelector) {
          var inner = startEl.querySelector('form[action*="/cart/add"]');
          if (inner) return inner;
        }
      } catch (e) {}
      return typeof document !== 'undefined' ? document.querySelector('form[action*="/cart/add"]') : null;
    }

    function getProductSlug() {
      try {
        if (typeof location !== 'undefined' && location.pathname) {
          var m = location.pathname.match(/\/products\/([^/]+)/);
          return m ? m[1] : null;
        }
      } catch (e) {}
      return null;
    }

    function getIds(form) {
      var formEl = form || document.querySelector('form[action*="/cart/add"]');
      var productId = getProductId(formEl);
      var variantId = getVariantIdFromForm(formEl);
      var scope = countScope === 'product' ? 'product' : 'variant';
      var pid = scope === 'product' ? productId : null;
      var vid = scope === 'variant' ? variantId : null;
      return { scope: scope, productId: pid, variantId: vid };
    }

    function buildCountUrl(ids) {
      var u = apiBase + SHOPIFY_COUNT_PATH + '?site_key=' + encodeURIComponent(siteKey);
      if (block.id != null) u += '&block_id=' + encodeURIComponent(String(block.id));
      if (ids.productId) u += '&product_id=' + encodeURIComponent(ids.productId);
      if (ids.variantId) u += '&variant_id=' + encodeURIComponent(ids.variantId);
      return u;
    }

    function renderMessage(count) {
      var text = messageTemplate.replace(/\{\{\s*count\s*\}\}/g, String(count));
      var el = document.createElement('div');
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
        var text = messageTemplate.replace(/\{\{\s*count\s*\}\}/g, String(count));
        messageEl.textContent = text;
      }
    }

    function fetchCountAndShow(anchor, messageEl, formEl) {
      var ids = getIds(formEl);
      if (ids.scope === 'variant' && !ids.variantId) {
        log('Missing variant id; not fetching count');
        return;
      }
      if (ids.scope === 'product' && !ids.productId) {
        log('Missing product id; not fetching count');
        return;
      }

      fetch(buildCountUrl(ids), { method: 'GET', credentials: 'omit' })
        .then(function (r) {
          if (!r.ok) throw new Error('count ' + r.status);
          return r.json();
        })
        .then(function (data) {
          var count = data && data.count != null ? Number(data.count) : 0;
          if (!isFinite(count)) count = 0;
          if (count >= minCountToShow) {
            if (!messageEl) {
              messageEl = renderMessage(count);
              placeMessage(messageEl, anchor);
            }
            updateDisplay(count, messageEl);
          }
        })
        .catch(function (err) {
          log('GET count failed', err);
        });
    }

    function scheduleInitialFetch(anchor, formEl) {
      var stopped = false;
      var attempts = 0;
      var maxAttempts = 60; // up to ~30s (dynamic backoff)
      var observer = null;

      function hasIds() {
        var ids = getIds(formEl);
        if (!ids) return false;
        if (ids.scope === 'variant') return !!ids.variantId;
        if (ids.scope === 'product') return !!ids.productId;
        return false;
      }

      function stop() {
        stopped = true;
        try {
          if (observer) observer.disconnect();
        } catch (e) {}
      }

      function tick() {
        if (stopped) return;
        attempts++;

        var messageEl = anchor && anchor.parentNode ? anchor.parentNode.querySelector('.' + messageMainClass) : null;
        if (hasIds()) {
          fetchCountAndShow(anchor, messageEl, formEl);
          stop();
          return;
        }

        if (attempts >= maxAttempts) {
          log('IDs not available after waiting; will rely on submit/fetch hook');
          stop();
          return;
        }

        // gentle backoff: 250ms..1500ms
        var delay = Math.min(250 + attempts * 50, 1500);
        setTimeout(tick, delay);
      }

      // Watch for theme scripts updating variant id input / DOM swaps.
      try {
        if (typeof MutationObserver !== 'undefined' && typeof document !== 'undefined') {
          var root = document.documentElement || document.body;
          if (root) {
            observer = new MutationObserver(function () {
              if (hasIds()) {
                var messageEl = anchor && anchor.parentNode ? anchor.parentNode.querySelector('.' + messageMainClass) : null;
                fetchCountAndShow(anchor, messageEl, formEl);
                stop();
              }
            });
            observer.observe(root, { childList: true, subtree: true, attributes: true, attributeFilter: ['value', 'data-variant-id', 'data-product-id'] });
          }
        }
      } catch (e) {}

      tick();
    }

    function incrementAndRefresh(ids, pageUrl) {
      var body = {
        site_key: siteKey,
        block_id: block.id,
        product_id: ids.productId || null,
        variant_id: ids.variantId || null,
        page_url: pageUrl || (typeof location !== 'undefined' ? location.href : ''),
        product_slug: getProductSlug() || null
      };
      fetch(apiBase + SHOPIFY_ADD_TO_CART_PATH, {
        method: 'POST',
        credentials: 'omit',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body)
      })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          var count = data && data.count != null ? Number(data.count) : 0;
          if (!isFinite(count)) count = 0;
          var anchor = document.querySelector(targetSelector);
          var messageEl = anchor ? anchor.parentNode.querySelector('.' + messageMainClass) : null;
          if (count >= minCountToShow) {
            if (!messageEl && anchor) {
              var newEl = renderMessage(count);
              placeMessage(newEl, anchor);
            } else if (messageEl) {
              updateDisplay(count, messageEl);
            }
          }
        })
        .catch(function (err) {
          log('POST add-to-cart failed', err);
        });
    }

    var anchor = document.querySelector(targetSelector);
    if (!anchor) log('Anchor not found:', targetSelector);
    if (anchor) {
      var form = findNearestCartAddForm(anchor);
      if (form && !form.getAttribute('data-embed-add-to-cart-submit')) {
        form.setAttribute('data-embed-add-to-cart-submit', '1');
        form.addEventListener('submit', function () {
          var ids = getIds(form);
          var pageUrl = typeof location !== 'undefined' ? location.href : '';
          if ((ids.variantId || ids.productId) && shouldIncrementOnce(ids, pageUrl)) {
            incrementAndRefresh(ids, pageUrl);
          }
        });
      }
      // Small delay so theme/Shopify scripts can set product_id and variant_id before we fetch count
      setTimeout(function () {
        scheduleInitialFetch(anchor, form || null);
      }, 400);
    }

    var origFetch = window.fetch;
    if (typeof origFetch === 'function' && !globalState.fetchPatched) {
      globalState.fetchPatched = true;
      window.fetch = function (url, opts) {
        var req = origFetch.apply(this, arguments);
        var urlStr = typeof url === 'string' ? url : (url && url.url) || '';
        if (urlStr.indexOf('/cart/add.js') >= 0) {
          req.then(function (res) {
            if (res && res.ok && res.clone) {
              res.clone().json().then(function (body) {
                try {
                  var variantId = body && body.variant_id ? String(body.variant_id) : null;
                  // Best-effort: try to anchor to the current product form, fallback to page meta/jsonld.
                  var bestForm = form || findNearestCartAddForm(anchor) || document.querySelector('form[action*="/cart/add"]');
                  var productId = getProductId(bestForm);
                  var ids = {
                    productId: productId,
                    variantId: variantId || getVariantIdFromForm(bestForm)
                  };
                  var pageUrl = typeof location !== 'undefined' ? location.href : '';
                  if ((ids.variantId || ids.productId) && shouldIncrementOnce(ids, pageUrl)) {
                    incrementAndRefresh(ids, pageUrl);
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
})();
