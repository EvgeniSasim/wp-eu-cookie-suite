/**
 * Privaro consent unblock / re-block bootstrap.
 * Loaded as a static asset so HTML processors cannot corrupt inline `type="…"`.
 */
(function () {
	'use strict';

	var config = window.wpeuCsBlocker || {};
	var allCategorySlugs = Array.isArray(config.categories) ? config.categories : [];
	var reloadOnWithdraw = !!config.reloadOnWithdraw;
	var activatedScriptCategories = Object.create(null);
	var activating = false;

	function consentAllows(consentData, category) {
		return !!(consentData && consentData[category]);
	}

	function activateScripts(consentData) {
		document.querySelectorAll('script[type="text/plain"][data-category]').forEach(function (script) {
			var category = script.getAttribute('data-category');
			if (!consentAllows(consentData, category)) {
				return;
			}
			if (script.getAttribute('data-wpeu-activated') === '1') {
				return;
			}

			var newScript = document.createElement('script');
			Array.from(script.attributes).forEach(function (attr) {
				if (attr.name === 'type' || attr.name === 'data-category' || attr.name === 'data-wpeu-original-type') {
					return;
				}
				newScript.setAttribute(attr.name, attr.value);
			});

			var originalType = script.getAttribute('data-wpeu-original-type');
			newScript.type = originalType && originalType !== '' ? originalType : 'text/javascript';
			newScript.text = script.text;
			newScript.setAttribute('data-wpeu-activated', '1');
			newScript.setAttribute('data-category', category);

			script.parentNode.insertBefore(newScript, script);
			script.parentNode.removeChild(script);
			activatedScriptCategories[category] = true;
		});
	}

	function activateLinks(consentData) {
		document.querySelectorAll('link[data-category][data-wpeu-href]').forEach(function (link) {
			var category = link.getAttribute('data-category');
			if (!consentAllows(consentData, category)) {
				return;
			}
			if (link.getAttribute('data-wpeu-activated') === '1') {
				return;
			}

			var newLink = document.createElement('link');
			var href = link.getAttribute('data-wpeu-href') || '';
			var rel = link.getAttribute('data-wpeu-original-rel') || '';
			var originalType = link.getAttribute('data-wpeu-original-type');

			Array.from(link.attributes).forEach(function (attr) {
				if (
					attr.name === 'type' ||
					attr.name === 'href' ||
					attr.name === 'rel' ||
					attr.name === 'data-category' ||
					attr.name === 'data-wpeu-href' ||
					attr.name === 'data-wpeu-original-rel' ||
					attr.name === 'data-wpeu-original-type'
				) {
					return;
				}
				newLink.setAttribute(attr.name, attr.value);
			});

			if (href) {
				newLink.setAttribute('href', href);
			}
			if (rel) {
				newLink.setAttribute('rel', rel);
			}
			if (originalType) {
				newLink.type = originalType;
			}
			newLink.setAttribute('data-wpeu-activated', '1');
			newLink.setAttribute('data-category', category);

			link.parentNode.insertBefore(newLink, link);
			link.parentNode.removeChild(link);
		});
	}

	function activateIframes(consentData) {
		document.querySelectorAll('.wpeu-cs-iframe-placeholder[data-category]').forEach(function (placeholder) {
			var category = placeholder.getAttribute('data-category');
			if (!consentAllows(consentData, category)) {
				return;
			}
			var hiddenIframe = placeholder.querySelector('.wpeu-cs-hidden-iframe');
			if (!hiddenIframe) {
				return;
			}
			var originalTag = atob(hiddenIframe.textContent);
			var container = document.createElement('div');
			container.innerHTML = originalTag;
			var iframe = container.firstChild;
			if (!iframe) {
				return;
			}
			iframe.setAttribute('data-wpeu-activated', '1');
			iframe.setAttribute('data-category', category);
			iframe.setAttribute('data-wpeu-original-iframe', hiddenIframe.textContent);
			placeholder.parentNode.insertBefore(iframe, placeholder);
			placeholder.parentNode.removeChild(placeholder);
		});
	}

	function deactivateIframes(consentData) {
		document.querySelectorAll('iframe[data-wpeu-activated="1"][data-category]').forEach(function (iframe) {
			var category = iframe.getAttribute('data-category');
			if (consentAllows(consentData, category)) {
				return;
			}
			var encoded = iframe.getAttribute('data-wpeu-original-iframe');
			if (!encoded) {
				iframe.parentNode.removeChild(iframe);
				return;
			}
			var service = iframe.getAttribute('data-service') || '';
			var placeholder = document.createElement('div');
			placeholder.className = 'wpeu-cs-iframe-placeholder';
			placeholder.setAttribute('data-category', category);
			if (service) {
				placeholder.setAttribute('data-service', service);
			}
			placeholder.innerHTML =
				'<div class="wpeu-cs-iframe-placeholder-content"></div>' +
				'<div class="wpeu-cs-hidden-iframe" style="display:none;">' +
				encoded +
				'</div>';
			iframe.parentNode.insertBefore(placeholder, iframe);
			iframe.parentNode.removeChild(iframe);
		});
	}

	function deactivateLinks(consentData) {
		document.querySelectorAll('link[data-wpeu-activated="1"][data-category]').forEach(function (link) {
			var category = link.getAttribute('data-category');
			if (consentAllows(consentData, category)) {
				return;
			}
			var href = link.getAttribute('href') || '';
			var rel = link.getAttribute('rel') || '';
			var type = link.getAttribute('type') || '';
			link.setAttribute('data-wpeu-href', href);
			link.setAttribute('data-wpeu-original-rel', rel);
			if (type) {
				link.setAttribute('data-wpeu-original-type', type);
			}
			link.setAttribute('rel', 'wpeu-blocked');
			link.setAttribute('type', 'text/plain');
			link.removeAttribute('href');
			link.removeAttribute('data-wpeu-activated');
		});
	}

	function maybeReloadOnScriptWithdraw(consentData) {
		if (!reloadOnWithdraw) {
			return;
		}
		var withdrawn = Object.keys(activatedScriptCategories).some(function (cat) {
			return !consentAllows(consentData, cat);
		});
		if (withdrawn) {
			window.location.reload();
		}
	}

	function applyConsent(consentData) {
		if (activating) {
			return;
		}
		activating = true;
		try {
			consentData = consentData || {};
			deactivateIframes(consentData);
			deactivateLinks(consentData);
			maybeReloadOnScriptWithdraw(consentData);
			activateScripts(consentData);
			activateLinks(consentData);
			activateIframes(consentData);
		} finally {
			activating = false;
		}
	}

	document.addEventListener('wpeu-consent-updated', function (e) {
		applyConsent(e.detail || {});
	});

	document.addEventListener('wpeu-consent-revoked', function () {
		var denied = {};
		allCategorySlugs.forEach(function (cat) {
			denied[cat] = false;
		});
		applyConsent(denied);
	});

	document.addEventListener('click', function (e) {
		var target = e.target;
		if (!target || !target.classList || !target.classList.contains('wpeu-cs-accept-category')) {
			return;
		}
		var category = target.getAttribute('data-category');
		if (category && window.CookieConsent && typeof window.CookieConsent.acceptCategory === 'function') {
			window.CookieConsent.acceptCategory(category);
		}
	});

	window.addEventListener('load', function () {
		if (!window.CookieConsent || typeof window.CookieConsent.acceptedCategory !== 'function') {
			return;
		}
		if (typeof window.CookieConsent.validConsent === 'function' && !window.CookieConsent.validConsent()) {
			return;
		}
		var consentData = {};
		allCategorySlugs.forEach(function (cat) {
			consentData[cat] = window.CookieConsent.acceptedCategory(cat);
		});
		applyConsent(consentData);
	});
})();
