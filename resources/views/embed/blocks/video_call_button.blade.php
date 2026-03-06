{{--
  Video Call (WhatsApp) Button block runner.
  Injected into embed script; uses blockRegistry, EMBED_BASE, EVENTS_PATH, siteKey from parent scope.
--}}
  /**
   * Video Call Button: business-hours badge (ONLINE/OFFLINE) and WhatsApp button with product context.
   * Tracks clicks via POST /api/public/events (event_name: click, payload: product_title, product_url, product_price).
   */
  function runVideoCallButton(block, siteKey) {
    const settings = block.settings || {};
    const phone = (settings.phone || '').trim();
    const openDaysStr = (settings.open_days || '0,1,2,3,4,5').trim();
    const openTime = (settings.open_time || '10:30').trim();
    const closeTime = (settings.close_time || '18:00').trim();
    const fridayClose = (settings.friday_close || '14:00').trim();
    const tz = (settings.timezone || 'Asia/Jerusalem').trim();
    const buttonText = settings.button_text || 'התחל שיחת וידאו לצפייה במוצר';
    const targetSelector = settings.target_selector || '[data-product-form], form[action*="/cart/add"]';
    const insertPosition = settings.insert_position || 'after';
    const messageTemplate = settings.message_template || '*התחלת שיחת וידאו לצפייה במוצר:*\n*{{product_title}}*\n{{product_price}}\n\n{{product_url}}';
    const apiBase = EMBED_BASE;

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

    function getProductInfo() {
      var out = { product_title: '', product_url: '', product_price: '' };
      try {
        if (typeof location !== 'undefined') out.product_url = location.href || '';
        if (typeof window.ShopifyAnalytics !== 'undefined' && window.ShopifyAnalytics.meta && window.ShopifyAnalytics.meta.product) {
          var p = window.ShopifyAnalytics.meta.product;
          if (p.title) out.product_title = String(p.title);
          if (p.price !== undefined) out.product_price = String(p.price);
        }
        if (typeof window.meta !== 'undefined' && window.meta && window.meta.product) {
          var q = window.meta.product;
          if (q.title) out.product_title = out.product_title || String(q.title);
          if (q.price !== undefined) out.product_price = out.product_price || String(q.price);
        }
      } catch (e) {}
      return out;
    }

    function parseTime(s) {
      var parts = (s || '0:00').split(':');
      return (parseInt(parts[0], 10) || 0) * 60 + (parseInt(parts[1], 10) || 0);
    }

    function computeIsOpen() {
      var openDays = openDaysStr.split(',').map(function(s) { return s.trim(); }).filter(Boolean);
      var now = new Date();
      var jerusalem = new Date(now.toLocaleString('en-US', { timeZone: tz }));
      var day = jerusalem.getDay();
      var time = jerusalem.getHours() * 60 + jerusalem.getMinutes();
      var openMin = parseTime(openTime);
      var closeMin = parseTime(closeTime);
      var friMin = parseTime(fridayClose);
      var isOpen = openDays.indexOf(String(day)) >= 0 && time >= openMin && time <= closeMin;
      if (day === 5 && time >= openMin && time <= friMin) isOpen = true;
      if (day === 6) isOpen = false;
      return isOpen;
    }

    function placeEl(el, anchor) {
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

    function sendClickEvent(payload) {
      try {
        fetch(apiBase + EVENTS_PATH, {
          method: 'POST',
          credentials: 'omit',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            site_key: siteKey,
            block_id: block.id,
            event_name: 'click',
            page_url: typeof location !== 'undefined' ? location.href : '',
            payload: payload
          })
        }).catch(function() {});
      } catch (e) {}
    }

    var anchor = typeof document !== 'undefined' ? document.querySelector(targetSelector) : null;
    if (!anchor || !phone) return;

    var root = document.createElement('div');
    root.className = 'embed-video-call-button';
    root.setAttribute('data-embed-block-id', String(block.id));
    root.style.marginTop = '15px';
    root.style.width = '100%';
    root.style.display = 'block';

    var statusEl = document.createElement('span');
    statusEl.className = 'embed-video-call-button__status';
    statusEl.style.cssText = 'position:absolute;top:-10px;left:-10px;background:#404040;color:#fff;font-weight:bold;padding:0 10px;border-radius:20px;font-size:7px;letter-spacing:2px;line-height:2.5;';

    var btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'embed-video-call-button__btn';
    btn.style.cssText = 'background:#eee;border:none;padding:5px 20px;font-size:16px;cursor:pointer;border-radius:5px;position:relative;width:100%;';
    btn.textContent = buttonText;
    btn.appendChild(statusEl);

    root.appendChild(btn);

    (function updateStatus() {
      var isOpen = computeIsOpen();
      statusEl.textContent = isOpen ? 'ONLINE' : 'OFFLINE';
      statusEl.style.background = isOpen ? '#FBB47B' : '#404040';
      statusEl.style.color = isOpen ? '#000' : '#fff';
      if (isOpen && !statusEl.querySelector('.embed-video-call-button__blink-dot')) {
        var dot = document.createElement('span');
        dot.className = 'embed-video-call-button__blink-dot';
        dot.style.cssText = 'display:inline-block;position:absolute;top:-1px;left:-5px;width:8px;height:8px;background:red;border-radius:50%;margin-left:5px;animation:embedVcBlink 2s infinite;';
        statusEl.appendChild(dot);
      }
    })();

    if (typeof document !== 'undefined' && !document.getElementById('embed-video-call-button-style')) {
      var animStyle = document.createElement('style');
      animStyle.id = 'embed-video-call-button-style';
      animStyle.textContent = '@keyframes embedVcBlink{0%{opacity:1}50%{opacity:0}100%{opacity:1}}';
      document.head.appendChild(animStyle);
    }

    btn.addEventListener('click', function() {
      var info = getProductInfo();
      var msg = messageTemplate
        .replace(/\{\{\s*product_title\s*\}\}/g, info.product_title)
        .replace(/\{\{\s*product_price\s*\}\}/g, info.product_price)
        .replace(/\{\{\s*product_url\s*\}\}/g, info.product_url);
      var waUrl = 'https://wa.me/' + phone + '?text=' + encodeURIComponent(msg);
      if (typeof window !== 'undefined' && window.open) window.open(waUrl, '_blank');
      sendClickEvent({
        product_title: info.product_title,
        product_url: info.product_url,
        product_price: info.product_price
      });
    });

    placeEl(root, anchor);
  }

  blockRegistry.video_call_button = runVideoCallButton;
