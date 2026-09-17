/* global aiwcWidget */
(function () {
	'use strict';

	var CONFIG = (window.aiwcWidget && window.aiwcWidget.config) || {};
	var API = window.aiwcWidget || {};

	var els = {};
	var state = {
		open: false,
		sending: false,
		conversationId: null,
		sessionId: null,
		history: [],
		leadSubmitted: false,
		turns: 0
	};

	function storage(key) {
		try {
			return window.localStorage.getItem('aiwc_' + key);
		} catch (e) {
			return null;
		}
	}

	function store(key, value) {
		try {
			window.localStorage.setItem('aiwc_' + key, value);
		} catch (e) { /* ignore */ }
	}

	function obtainSession() {
		var sid = storage('session_id');
		if (!sid) {
			sid = String(Date.now()).slice(-8) + '-' + Math.random().toString(36).slice(2, 12);
			store('session_id', sid);
		}
		state.sessionId = sid;
		state.conversationId = storage('conversation_id') || null;
		state.leadSubmitted = !!storage('lead_submitted');
	}

	function apiUrl(endpoint) {
		return (API.restUrl || '').replace(/\/$/, '') + '/' + endpoint;
	}

	function createEl(tag, className, text) {
		var node = document.createElement(tag);
		if (className) { node.className = className; }
		if (text !== undefined && text !== null) {
			node.textContent = text;
		}
		return node;
	}

	var icons = {
		feedbackUp: '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"></path></svg>',
		feedbackDown: '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 15v4a3 3 0 0 0 3 3l4-9V2H5.72a2 2 0 0 0-2 1.7l-1.38 9a2 2 0 0 0 2 2.3zM17 2h3a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2h-3"></path></svg>'
	};

	function applyTheme() {
		var root = els.widget;
		root.style.setProperty('--aiwc-primary-color', CONFIG.primaryColor || '#2563eb');
		root.style.setProperty('--aiwc-button-color', CONFIG.buttonColor || CONFIG.primaryColor || '#2563eb');
		root.style.setProperty('--aiwc-text-color', CONFIG.textColor || '#ffffff');
		root.style.setProperty('--aiwc-width', (CONFIG.width || 380) + 'px');
		root.style.setProperty('--aiwc-height', (CONFIG.height || 560) + 'px');
		root.style.setProperty('--aiwc-radius', (CONFIG.borderRadius || 12) + 'px');
		if (CONFIG.position === 'bottom-left') {
			root.classList.add('aiwc-position-left');
		}
		if (CONFIG.botName) {
			els.botName.textContent = CONFIG.botName;
		}
	}

	function escapeHtml(text) {
		return String(text)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;')
			.replace(/'/g, '&#39;');
	}

	function renderInline(text) {
		return escapeHtml(text)
			.replace(/`([^`]+)`/g, '<code>$1</code>')
			.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>')
			.replace(/(^|[^*])\*([^*\n]+)\*(?!\*)/g, '$1<em>$2</em>')
			.replace(/\[([^\]]+)\]\((https?:\/\/[^)\s]+)\)/g, '<a href="$2" target="_blank" rel="noopener noreferrer">$1</a>');
	}

	function closeMarkdownLists(html, inList, listType) {
		if (inList) {
			html += '</' + listType + '>';
		}
		return html;
	}

	function renderMarkdown(text) {
		var lines = String(text).replace(/\r\n/g, '\n').split('\n');
		var html = '';
		var inList = false;
		var listType = '';
		var inCode = false;
		var codeLines = [];

		function closeLists() {
			if (inList) {
				html += '</' + listType + '>';
				inList = false;
				listType = '';
			}
		}

		lines.forEach(function (line) {
			var trimmed = line.trim();

			if (/^\s*```/.test(trimmed)) {
				closeLists();
				if (!inCode) {
					inCode = true;
					codeLines = [];
				} else {
					inCode = false;
					html += '<pre><code>' + escapeHtml(codeLines.join('\n')) + '</code></pre>';
				}
				return;
			}
			if (inCode) {
				codeLines.push(line);
				return;
			}

			var h = trimmed.match(/^(#{1,4})\s+(.*)$/);
			if (h) {
				closeLists();
				var lvl = h[1].length;
				html += '<h' + lvl + ' class="aiwc-md-h">' + renderInline(h[2]) + '</h' + lvl + '>';
				return;
			}

			var ul = trimmed.match(/^([-*+])\s+(.*)$/);
			if (ul) {
				if (!inList || listType !== 'ul') {
					closeLists();
					html += '<ul>';
					inList = true;
					listType = 'ul';
				}
				html += '<li>' + renderInline(ul[2]) + '</li>';
				return;
			}

			var ol = trimmed.match(/^(\d+)[.)]\s+(.*)$/);
			if (ol) {
				if (!inList || listType !== 'ol') {
					closeLists();
					html += '<ol>';
					inList = true;
					listType = 'ol';
				}
				html += '<li>' + renderInline(ol[2]) + '</li>';
				return;
			}

			closeLists();

			if (trimmed === '') {
				html += '<div class="aiwc-md-gap"></div>';
				return;
			}

			if (trimmed.indexOf('> ') === 0 || trimmed === '>') {
				html += '<blockquote>' + renderInline(trimmed.replace(/^>\s?/, '')) + '</blockquote>';
				return;
			}

			html += '<p>' + renderInline(trimmed) + '</p>';
		});

		if (inCode) {
			html += '<pre><code>' + escapeHtml(codeLines.join('\n')) + '</code></pre>';
		}
		return closeMarkdownLists(html, inList, listType);
	}

	function buildFeedback(messageId) {
		var feedback = createEl('span', 'aiwc-feedback');
		var up = createEl('button', '', '');
		up.innerHTML = icons.feedbackUp;
		up.title = 'Helpful';
		up.addEventListener('click', function () { sendFeedback('positive', messageId); });
		var down = createEl('button', '', '');
		down.innerHTML = icons.feedbackDown;
		down.title = 'Not helpful';
		down.addEventListener('click', function () { sendFeedback('negative', messageId); });
		feedback.appendChild(up);
		feedback.appendChild(down);
		return feedback;
	}

	function addMessage(role, content, opts) {
		opts = opts || {};
		var wrap = createEl('div', 'aiwc-msg ' + (role === 'user' ? 'aiwc-user' : 'aiwc-bot'));

		if (opts.raw) {
			wrap.innerHTML = content;
			return wrap;
		}

		var bubble = createEl('div', 'aiwc-msg-bubble');
		if (role === 'assistant') {
			bubble.innerHTML = renderMarkdown(content || '');
		} else {
			bubble.textContent = content || '';
		}
		wrap.appendChild(bubble);

		if (role === 'assistant') {
			var meta = createEl('div', 'aiwc-msg-meta');
			var hasSources = CONFIG.showSources && opts.sources && opts.sources.length;
			if (hasSources) {
				var sources = createEl('div', 'aiwc-sources');
				var title = createEl('div', 'aiwc-sources-title');
				title.textContent = 'Sources (' + opts.sources.length + ')';
				sources.appendChild(title);
				var listWrap = createEl('div', 'aiwc-source-list');
				opts.sources.forEach(function (src, i) {
					var item = createEl('div', 'aiwc-source-item');
					var index = createEl('span', 'aiwc-source-index', String(i + 1));
					var label = src.title || ('Source ' + (i + 1));
					var link = createEl('a', 'aiwc-source-link', label);
					item.appendChild(index);
					if (src.url) {
						link.setAttribute('href', src.url);
						link.setAttribute('target', '_blank');
						link.setAttribute('rel', 'noopener noreferrer');
						try {
							var host = new URL(src.url).hostname.replace(/^www\./, '');
							if (host) {
								var domain = createEl('span', 'aiwc-source-domain', host);
								link.appendChild(domain);
							}
						} catch (e) { /* ignore */ }
					} else {
						link.classList.add('aiwc-source-plain');
					}
					item.appendChild(link);
					listWrap.appendChild(item);
				});
				sources.appendChild(listWrap);
				meta.appendChild(sources);
			}
			if (hasSources) {
				meta.appendChild(buildFeedback(opts.messageId));
				wrap.appendChild(meta);
			}
		}

		return wrap;
	}

	function addHandoffOptions() {
		var handoff = CONFIG.handoff || {};
		if (!handoff.enabled) { return null; }

		var wrap = createEl('div', 'aiwc-msg aiwc-bot');
		var bubble = createEl('div', 'aiwc-msg-bubble');
		bubble.textContent = 'Would you like to talk to a member of our team?';
		var links = createEl('div', 'aiwc-handoff-links');
		if (handoff.support_phone) {
			var tel = createEl('a', 'aiwc-source-link', handoff.support_phone);
			tel.setAttribute('href', 'tel:' + encodeURIComponent(handoff.support_phone.replace(/[^+\d]/g, '')));
			links.appendChild(tel);
		}
		if (handoff.support_email) {
			var mail = createEl('a', 'aiwc-source-link', handoff.support_email);
			mail.setAttribute('href', 'mailto:' + encodeURIComponent(handoff.support_email));
			links.appendChild(mail);
		}
		if (handoff.whatsapp_link) {
			var wa = createEl('a', 'aiwc-source-link', 'WhatsApp');
			wa.setAttribute('href', handoff.whatsapp_link);
			wa.setAttribute('target', '_blank');
			wa.setAttribute('rel', 'noopener noreferrer');
			links.appendChild(wa);
		}
		var btnWrap = createEl('div', 'aiwc-error-actions');
		var talk = createEl('button', '', 'Talk to a human');
		talk.addEventListener('click', function () { showHandoffPanel(); });
		btnWrap.appendChild(talk);
		bubble.appendChild(links);
		bubble.appendChild(btnWrap);
		wrap.appendChild(bubble);
		return wrap;
	}

	function showHandoffPanel() {
		var handoff = CONFIG.handoff || {};
		var panel = createEl('div', 'aiwc-handoff-panel');
		panel.textContent = handoff.support_phone ? 'Call us: ' + handoff.support_phone : '';
		if (handoff.support_phone) {
			var tel = createEl('a', 'aiwc-source-link', 'Call now');
			tel.setAttribute('href', 'tel:' + encodeURIComponent(handoff.support_phone.replace(/[^+\d]/g, '')));
			panel.appendChild(tel);
		}
		if (handoff.contact_form) {
			buildLeadForm(panel, true);
		}
		els.messages.appendChild(panel);
		scrollToBottom();
	}

	function addLeadFormBubble() {
		var wrap = createEl('div', 'aiwc-msg aiwc-bot');
		var bubble = createEl('div', 'aiwc-lead-form');
		bubble.appendChild(createEl('p', '', CONFIG.leadPrompt || 'Would you like to leave your details?'));
		var name = createEl('input', '', '');
		name.type = 'text';
		name.placeholder = 'Your name';
		var email = createEl('input', '', '');
		email.type = 'email';
		email.placeholder = 'Email address';
		var phone = createEl('input', '', '');
		phone.type = 'tel';
		phone.placeholder = 'Phone (optional)';
		var note = createEl('textarea', '', '');
		note.placeholder = 'Message (optional)';
		note.rows = 2;
		var submit = createEl('button', '', 'Send my details');
		submit.addEventListener('click', function () {
			if (!name.value.trim() || !email.value.trim()) {
				email.style.borderColor = '#ef4444';
				name.style.borderColor = '#ef4444';
				return;
			}
			submit.disabled = true;
			submit.textContent = 'Sending…';
			var payload = {
				name: name.value.trim(),
				email: email.value.trim(),
				phone: phone.value.trim(),
				message: note.value.trim(),
				conversation_id: state.conversationId || ''
			};
			fetch(apiUrl('lead'), {
				method: 'POST',
				headers: { 'Content-Type': 'application/json', 'X-AIWC-Nonce': API.nonce },
				body: JSON.stringify(payload)
			}).then(function (res) { return res.json(); }).then(function (json) {
				submit.textContent = '✓ Thank you!';
				state.leadSubmitted = true;
				store('lead_submitted', '1');
				name.disabled = true;
				email.disabled = true;
				phone.disabled = true;
				note.disabled = true;
				window.setTimeout(function () {
					els.messages.appendChild(addMessage('assistant', 'Thank you! A member of our team will be in touch shortly.'));
				}, 600);
			}).catch(function () {
				submit.disabled = false;
				submit.textContent = 'Try again';
			});
		});
		bubble.appendChild(name);
		bubble.appendChild(email);
		bubble.appendChild(phone);
		bubble.appendChild(note);
		bubble.appendChild(submit);
		wrap.appendChild(bubble);
		return wrap;
	}

	function buildLeadForm(container, hidePrompt) {
		if (!hidePrompt && CONFIG.leadPrompt) {
			container.appendChild(createEl('p', '', CONFIG.leadPrompt));
		}
		var name = createEl('input', '', '');
		name.type = 'text';
		name.placeholder = 'Your name';
		var email = createEl('input', '', '');
		email.type = 'email';
		email.placeholder = 'Email address';
		var phone = createEl('input', '', '');
		phone.type = 'tel';
		phone.placeholder = 'Phone (optional)';
		var note = createEl('textarea', '', '');
		note.placeholder = 'Message (optional)';
		note.rows = 2;
		var submit = createEl('button', '', 'Send');
		submit.addEventListener('click', function () {
			if (!name.value.trim() || !email.value.trim()) { return; }
			fetch(apiUrl('lead'), {
				method: 'POST',
				headers: { 'Content-Type': 'application/json', 'X-AIWC-Nonce': API.nonce },
				body: JSON.stringify({ name: name.value.trim(), email: email.value.trim(), phone: phone.value.trim(), message: note.value.trim(), conversation_id: state.conversationId || '' })
			}).then(function (r) { return r.json(); }).then(function () {
				submit.textContent = '✓ Sent';
				submit.disabled = true;
			});
		});
		container.appendChild(name);
		container.appendChild(email);
		container.appendChild(phone);
		container.appendChild(note);
		container.appendChild(submit);
		return container;
	}

	function showTyping() {
		var wrap = createEl('div', 'aiwc-msg aiwc-bot');
		var box = createEl('div', 'aiwc-typing', '');
		box.appendChild(createEl('span'));
		box.appendChild(createEl('span'));
		box.appendChild(createEl('span'));
		wrap.appendChild(box);
		wrap.dataset.aiwcTyping = '1';
		els.messages.appendChild(wrap);
		scrollToBottom();
		return wrap;
	}

	function hideTyping() {
		els.messages.querySelectorAll('[data-aiwc-typing="1"]').forEach(function (n) { n.remove(); });
	}

	function scrollToBottom() {
		if (els.messages) {
			els.messages.scrollTop = els.messages.scrollHeight;
		}
	}

	function writeWelcome() {
		if (els.messages.querySelector('.aiwc-msg')) { return; }
		els.messages.appendChild(addMessage('assistant', CONFIG.welcome || 'Hi! 👋 How can I help you?'));
		var leadPrompt = CONFIG.leadPrompt;
		if (CONFIG.collectLeads && !state.leadSubmitted && leadPrompt) {
			els.messages.appendChild(addLeadFormBubble());
		}
	}

	function sendFeedback(rating, messageId) {
		var payload = { rating: rating, conversation_id: state.conversationId || '' };
		if (messageId) { payload.message_id = messageId; }
		fetch(apiUrl('feedback'), {
			method: 'POST',
			headers: { 'Content-Type': 'application/json', 'X-AIWC-Nonce': API.nonce },
			body: JSON.stringify(payload)
		}).catch(function () { /* silent */ });
	}

	function sendMessage(text) {
		if (state.sending || !text.trim()) { return; }

		var trimmed = text.trim();
		els.input.value = '';
		autoResize();

		els.messages.appendChild(addMessage('user', trimmed));
		els.messages.querySelectorAll('[data-aiwc-form="1"]').forEach(function (n) { n.remove(); });

		var typing = showTyping();
		state.sending = true;

		var payload = {
			message: trimmed,
			conversation_id: state.conversationId || '',
			session_id: state.sessionId || ''
		};
		if (!CONFIG.storeConversations) {
			payload.history = state.history.slice(-CONFIG.maxHistory || 12);
		}
		state.history.push({ role: 'user', message: trimmed });

		fetch(apiUrl('chat'), {
			method: 'POST',
			headers: { 'Content-Type': 'application/json', 'X-AIWC-Nonce': API.nonce },
			body: JSON.stringify(payload)
		}).then(function (res) {
			return res.json().then(function (json) { return { status: res.status, json: json }; });
		}).then(function (result) {
			hideTyping();
			state.sending = false;
			var json = result.json || {};
			typing.remove();

			if (result.status !== 200 || !json.success) {
				var code = json.code || '';
				if (typeof console !== 'undefined' && console.error) {
					console.error('[aiwc] chat error', { status: result.status, code: code, message: json.message || '', json: json });
				}
				var msg = (code === 'aiwc_rate_limited' || code === 'aiwc_daily_limit' || code === 'aiwc_api_rate_limit')
					? json.message
					: 'Sorry, something went wrong. Please try again shortly.';
				var err = addMessage('assistant', msg, { messageId: 0 });
				err.classList.add('aiwc-error');
				var actions = createEl('div', 'aiwc-error-actions');
				var retry = createEl('button', '', 'Retry');
				retry.addEventListener('click', function () {
					err.remove();
					sendMessage(trimmed);
				});
				actions.appendChild(retry);
				err.querySelector('.aiwc-msg-meta, .aiwc-msg-bubble').appendChild(actions);
				els.messages.appendChild(err);
				scrollToBottom();
				return;
			}

			if (json.conversation_id) {
				state.conversationId = json.conversation_id;
				store('conversation_id', json.conversation_id);
			}
			state.history.push({ role: 'assistant', message: json.message });
			state.turns++;

			els.messages.appendChild(addMessage('assistant', json.message, {
				sources: json.sources || [],
				messageId: json.message_id || 0
			}));

			if (json.had_fallback) {
				var handoff = addHandoffOptions();
				if (handoff) { els.messages.appendChild(handoff); }
			} else if (CONFIG.collectLeads && state.turns === 2 && !state.leadSubmitted) {
				var form = addLeadFormBubble();
				form.dataset.aiwcForm = '1';
				els.messages.appendChild(form);
			}
			scrollToBottom();
		}).catch(function () {
			hideTyping();
			state.sending = false;
			typing.remove();
			var err = addMessage('assistant', 'Sorry, we could not reach the server. Please try again.');
			err.classList.add('aiwc-error');
			els.messages.appendChild(err);
			scrollToBottom();
		});
	}

	function autoResize() {
		els.input.style.height = 'auto';
		els.input.style.height = Math.min(120, els.input.scrollHeight) + 'px';
	}

	function clearConversation() {
		state.conversationId = null;
		state.history = [];
		state.turns = 0;
		state.leadSubmitted = !!storage('lead_submitted');
		store('conversation_id', '');
		els.messages.innerHTML = '';
		writeWelcome();
		scrollToBottom();
	}

	function toggle(forceOpen) {
		var shouldOpen = typeof forceOpen === 'boolean' ? forceOpen : !state.open;
		state.open = shouldOpen;
		els.window.hidden = !shouldOpen;
		if (shouldOpen) {
			els.button.style.display = 'none';
			els.input.focus();
			writeWelcome();
		} else {
			els.button.style.display = 'inline-flex';
		}
	}

	function bindEvents() {
		els.button.addEventListener('click', function () { toggle(); });
		els.minimize.addEventListener('click', function () { toggle(false); });
		els.clear.addEventListener('click', function () { clearConversation(); });
		els.send.addEventListener('click', function () { sendMessage(els.input.value); });
		els.input.addEventListener('keydown', function (e) {
			if (e.key === 'Enter' && !e.shiftKey) {
				e.preventDefault();
				sendMessage(els.input.value);
			}
		});
		els.input.addEventListener('input', autoResize);
		els.input.addEventListener('focus', scrollToBottom);
	}

	function init() {
		els.widget = document.getElementById('aiwc-widget');
		els.window = document.querySelector('.aiwc-window', els.widget);
		els.button = document.querySelector('.aiwc-button', els.widget);
		els.botName = document.querySelector('.aiwc-bot-name', els.widget);
		els.messages = document.querySelector('.aiwc-messages', els.widget);
		els.input = document.querySelector('.aiwc-input', els.widget);
		els.send = document.querySelector('.aiwc-send', els.widget);
		els.clear = document.querySelector('[data-aiwc-clear]', els.widget);
		els.minimize = document.querySelector('[data-aiwc-minimize]', els.widget);

		if (!els.widget || !els.window) { return; }

		obtainSession();
		applyTheme();
		bindEvents();

		var online = document.querySelector('.aiwc-online-text', els.widget);
		if (online) { online.textContent = 'Online'; }
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();