{{-- Shopify Add To Cart Counter block script. Loaded dynamically by embed loader. Reads from window.SiteBlocks. --}}
(function () {
  'use strict';
  var siteBlocks = typeof window !== 'undefined' && window.SiteBlocks;
  if (!siteBlocks || !siteBlocks.blockRegistry) return;
  var EMBED_BASE = siteBlocks.EMBED_BASE || '';
  var SHOPIFY_COUNT_PATH = siteBlocks.SHOPIFY_COUNT_PATH || '/api/public/shopify/count';
  var SHOPIFY_ADD_TO_CART_PATH = siteBlocks.SHOPIFY_ADD_TO_CART_PATH || '/api/public/shopify/add-to-cart';
  var blockRegistry = siteBlocks.blockRegistry;

  function runShopifyAddToCartCounter(block, siteKey) {
    var settings = block.settings || {};
    var targetSelector = settings.target_selector || '[data-product-form], form[action*="/cart/add"]';
    var insertPosition = settings.insert_position || 'after';
    var messageTemplate = settings.message_template || 'This product was added to cart @{{ count }} times';
    var messageClass = settings.message_class || 'embed-add-to-cart-count';
    var minCountToShow = settings.min_count_to_show != null ? Number(settings.min_count_to_show) : 1;
    var countScope = settings.count_scope || 'variant';
    var apiBase = EMBED_BASE;

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
      var input = form.querySelector('input[name="id"]');
      return input ? String(input.value).trim() : null;
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

    function getIds() {
      var productId = getProductId();
      var form = document.querySelector('form[action*="/cart/add"]');
      var variantId = getVariantIdFromForm(form);
      var scope = countScope === 'product' && productId ? 'product' : 'variant';
      var pid = scope === 'product' ? productId : null;
      var vid = scope === 'variant' ? (variantId || productId) : null;
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

    function fetchCountAndShow(anchor, messageEl) {
      var ids = getIds();
      if (ids.scope === 'variant' && !ids.variantId) return;
      if (ids.scope === 'product' && !ids.productId) return;

      fetch(buildCountUrl(ids), { method: 'GET', credentials: 'omit' })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          var count = data && typeof data.count === 'number' ? data.count : 0;
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
          var count = data && typeof data.count === 'number' ? data.count : 0;
          var anchor = document.querySelector(targetSelector);
          var messageEl = anchor ? anchor.parentNode.querySelector('.' + messageClass) : null;
          if (count >= minCountToShow) {
            if (!messageEl && anchor) {
              var newEl = renderMessage(count);
              placeMessage(newEl, anchor);
            } else {
              updateDisplay(count, messageEl);
            }
          }
        })
        .catch(function () {});
    }

    var anchor = document.querySelector(targetSelector);
    if (anchor) {
      fetchCountAndShow(anchor, null);
    }

    var origFetch = window.fetch;
    if (typeof origFetch === 'function') {
      window.fetch = function (url, opts) {
        var req = origFetch.apply(this, arguments);
        var urlStr = typeof url === 'string' ? url : (url && url.url) || '';
        if (urlStr.indexOf('/cart/add.js') >= 0) {
          req.then(function (res) {
            if (res && res.ok && res.clone) {
              res.clone().json().then(function (body) {
                try {
                  var variantId = body.variant_id ? String(body.variant_id) : null;
                  var productId = getProductId();
                  var scope = countScope === 'product' && productId ? 'product' : 'variant';
                  var ids = { scope: scope, productId: productId, variantId: variantId || getVariantIdFromForm(document.querySelector('form[action*="/cart/add"]')) };
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
})();
