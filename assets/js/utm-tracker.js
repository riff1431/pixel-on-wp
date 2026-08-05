(function() {
  try {
    const urlParams = new URLSearchParams(window.location.search);
    const keys = ['utm_id', 'utm_campaign', 'utm_source', 'utm_medium', 'fbclid'];
    let utmData = JSON.parse(localStorage.getItem('pixelonwp_utm_data') || '{}');
    let hasNew = false;

    keys.forEach(key => {
      if (urlParams.has(key)) {
        utmData[key] = urlParams.get(key);
        hasNew = true;
      }
    });

    if (hasNew) {
      utmData.timestamp = Date.now();
      localStorage.setItem('pixelonwp_utm_data', JSON.stringify(utmData));
    }

    // Restore to cookies if cookies are missing but exist in localStorage
    const getCookie = (name) => {
      const value = `; ${document.cookie}`;
      const parts = value.split(`; ${name}=`);
      if (parts.length === 2) return parts.pop().split(';').shift();
    };

    const setCookie = (name, value, days) => {
      const date = new Date();
      date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
      const expires = `; expires=${date.toUTCString()}`;
      document.cookie = `${name}=${value || ''}${expires}; path=/; SameSite=Lax; Secure`;
    };

    keys.forEach(key => {
      if (utmData[key]) {
        const cookieName = 'pixelonwp_' + key;
        if (!getCookie(cookieName)) {
          setCookie(cookieName, utmData[key], 30);
        }
      }
    });
  } catch (e) {
    console.error('PixelOnWP UTM Tracker fallback failed:', e);
  }
})();
