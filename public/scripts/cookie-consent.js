(function () {
  const CONSENT_KEY = "promedico_cookie_consent";

  const GOOGLE_TAG_ID = "G-XXXXXXXXXX";
  const GOOGLE_ADS_ID = "AW-XXXXXXXXXX";
  const BING_UET_ID = "XXXXXXXXX";

  const banner = document.querySelector("[data-cookie-banner]");
  const acceptButton = document.querySelector("[data-cookie-accept]");
  const rejectButton = document.querySelector("[data-cookie-reject]");

  function loadScript(src, id) {
    if (id && document.getElementById(id)) return;

    const script = document.createElement("script");
    script.src = src;
    script.async = true;

    if (id) {
      script.id = id;
    }

    document.head.appendChild(script);
  }

  function loadGoogleTags() {
    if (!GOOGLE_TAG_ID || GOOGLE_TAG_ID === "G-XXXXXXXXXX") return;

    window.dataLayer = window.dataLayer || [];
    window.gtag = function gtag() {
      window.dataLayer.push(arguments);
    };

    window.gtag("js", new Date());

    window.gtag("config", GOOGLE_TAG_ID, {
      anonymize_ip: true,
    });

    if (GOOGLE_ADS_ID && GOOGLE_ADS_ID !== "AW-XXXXXXXXXX") {
      window.gtag("config", GOOGLE_ADS_ID);
    }

    loadScript(
      `https://www.googletagmanager.com/gtag/js?id=${GOOGLE_TAG_ID}`,
      "google-gtag",
    );
  }

  function loadBingUet() {
    if (!BING_UET_ID || BING_UET_ID === "XXXXXXXXX") return;

    window.uetq = window.uetq || [];

    (function (w, d, t, r, u) {
      let f;
      let n;
      let i;

      w[u] = w[u] || [];
      f = function () {
        const o = { ti: BING_UET_ID, enableAutoSpaTracking: false };
        o.q = w[u];
        w[u] = new UET(o);
        w[u].push("pageLoad");
      };

      n = d.createElement(t);
      n.src = r;
      n.async = 1;
      n.onload = n.onreadystatechange = function () {
        const s = this.readyState;
        if (!s || s === "loaded" || s === "complete") {
          f();
          n.onload = n.onreadystatechange = null;
        }
      };

      i = d.getElementsByTagName(t)[0];
      i.parentNode.insertBefore(n, i);
    })(window, document, "script", "//bat.bing.com/bat.js", "uetq");
  }

  function loadTracking() {
    loadGoogleTags();
    loadBingUet();
  }

  function showBannerIfNeeded() {
    const consent = localStorage.getItem(CONSENT_KEY);

    if (!consent && banner) {
      banner.hidden = false;
    }

    if (consent === "accepted") {
      loadTracking();
    }
  }

  acceptButton?.addEventListener("click", function () {
    localStorage.setItem(CONSENT_KEY, "accepted");

    if (banner) {
      banner.hidden = true;
    }

    loadTracking();
  });

  rejectButton?.addEventListener("click", function () {
    localStorage.setItem(CONSENT_KEY, "rejected");

    if (banner) {
      banner.hidden = true;
    }
  });

  window.PromedicoCookieConsent = {
    reset() {
      localStorage.removeItem(CONSENT_KEY);
      if (banner) {
        banner.hidden = false;
      }
    },
  };

  showBannerIfNeeded();
})();
