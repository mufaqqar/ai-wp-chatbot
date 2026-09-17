/* global aiwcAdmin */
(function () {
	'use strict';

	function api(path, options) {
		options = options || {};
		options.headers = Object.assign(
			{ 'X-WP-Nonce': aiwcAdmin.nonce, 'Content-Type': 'application/json' },
			options.headers || {}
		);
		var url = aiwcAdmin.restUrl + path;
		return fetch(url, options).then(function (response) {
			return response.json().catch(function () {
				return { success: false, message: response.status };
			});
		});
	}

	function el(tag, className, html) {
		var node = document.createElement(tag);
		if (className) { node.className = className; }
		if (html !== undefined) { node.innerHTML = html; }
		return node;
	}

	function esc(text) {
		var div = document.createElement('div');
		div.textContent = String(text == null ? '' : text);
		return div.innerHTML;
	}

	function notice(message, type) {
		type = type || 'success';
		var box = el('div', 'notice notice-' + type + ' is-dismissible', '<p>' + esc(message) + '</p>');
		window.setTimeout(function () { box.remove(); }, 5000);
		var first = document.querySelector('.aiwc-wrap');
		if (first) { first.prepend(box); }
	}

	/* ---------------------------- Knowledge Base ---------------------------- */
	function initKnowledge() {
		var listWrap = function () {
			var search = document.getElementById('aiwc_knowledge_search');
			var type = document.getElementById('aiwc_knowledge_type');
			var status = document.getElementById('aiwc_knowledge_status');
			var page = 1;
			api('/admin/knowledge?page=' + page + '&per_page=20&post_type=' + encodeURIComponent(type ? type.value : '') + '&status=' + encodeURIComponent(status ? status.value : '') + '&search=' + encodeURIComponent(search ? search.value : '')).then(function (data) {
				var tbody = document.querySelector('#aiwc_knowledge_table tbody');
				if (!data || !data.items) { return; }

				tbody.innerHTML = '';
				if (!data.items.length) {
					tbody.appendChild(el('tr', 'aiwc-empty', '<td colspan="6">No items.</td>'));
					return;
				}
				data.items.forEach(function (item) {
					var tr = el('tr');
					var statusText = item.status === 'active' ? 'Active' : 'Inactive';
					var title = item.url ? '<a href="' + esc(item.url) + '" target="_blank" rel="noopener">' + esc(item.title) + '</a>' : esc(item.title);
					tr.appendChild(el('td', '', title));
					tr.appendChild(el('td', '', esc(item.post_type)));
					tr.appendChild(el('td', '', esc(item.content_length)));
					tr.appendChild(el('td', '', esc(statusText)));
					tr.appendChild(el('td', '', esc(item.indexed_at)));
					var actions = el('td');
					var toggle = el('button', 'button button-small', item.status === 'active' ? 'Disable' : 'Enable');
					toggle.addEventListener('click', function () {
						var next = item.status === 'active' ? 'inactive' : 'active';
						api('/admin/knowledge/' + item.id + '/toggle', { method: 'POST', body: JSON.stringify({ status: next }) }).then(function () { listWrap(); });
					});
					var del = el('button', 'button button-small', 'Delete');
					del.addEventListener('click', function () {
						if (!window.confirm((aiwcAdmin.messages || {}).confirm_delete || 'Delete?')) { return; }
						api('/admin/knowledge/' + item.id + '/delete', { method: 'POST' }).then(function () { listWrap(); });
					});
					actions.appendChild(toggle);
					actions.appendChild(del);
					tr.appendChild(actions);
					tbody.appendChild(tr);
				});
				renderPagination('aiwc_knowledge_pagination', data.pages, data.total, page, function (p) { page = p; listWrap(); });
			});
		};

		window.aiwcKnowledgeReload = listWrap;

		var startBtn = document.getElementById('aiwc_index_start');
		var stepBtn = document.getElementById('aiwc_index_step');
		var bar = document.getElementById('aiwc_progress_fill');
		var text = document.getElementById('aiwc_progress_text');
		var progressBox = document.getElementById('aiwc_index_progress');

		function showProgress(data) {
			progressBox.hidden = false;
			var total = Math.max(data.total || 1, 1);
			var pct = Math.min(100, Math.round(((data.done || 0) / total) * 100));
			bar.style.width = pct + '%';
			var msg = (aiwcAdmin.messages || {}).index_started || 'Indexing…';
			text.textContent = msg + ' ' + (data.done || 0) + '/' + (data.total || '?') + ' — ' + esc((aiwcAdmin.messages || {}).index_done || '');
			if (data.finished) {
				text.textContent = ((aiwcAdmin.messages || {}).index_done || 'Indexing complete.') + ' Items: ' + (data.done || 0) + (data.failed ? ', failed: ' + data.failed : '');
				stepBtn.disabled = true;
			} else {
				stepBtn.disabled = false;
			}
		}

		if (startBtn) {
			startBtn.addEventListener('click', function () {
				startBtn.disabled = true;
				api('/admin/index/start', { method: 'POST' }).then(function (data) {
					startBtn.disabled = false;
					showProgress(data);
					listWrap();
				});
			});
		}
		if (stepBtn) {
			stepBtn.addEventListener('click', function () {
				stepBtn.disabled = true;
				api('/admin/index/step').then(function (data) {
					stepBtn.disabled = false;
					showProgress(data);
					listWrap();
				});
			});
		}

		if (document.getElementById('aiwc_knowledge_type')) {
			var typeSelect = document.getElementById('aiwc_knowledge_type');
			if (!typeSelect.options.length) {
				typeSelect.appendChild(el('option', '', '<option value="page">page</option><option value="post">post</option><option value="product">product</option><option value="faq">faq</option>'));
			}
			['aiwc_knowledge_refresh', 'aiwc_knowledge_search'].forEach(function (id) {
				var node = document.getElementById(id);
				if (!node) { return; }
				var event = node.tagName === 'INPUT' ? 'change' : 'click';
				node.addEventListener(event, function () { listWrap(); });
			});
			document.getElementById('aiwc_knowledge_search').addEventListener('keydown', function (e) { if (e.key === 'Enter') { listWrap(); } });
			['aiwc_knowledge_type', 'aiwc_knowledge_status'].forEach(function (id) {
				document.getElementById(id).addEventListener('change', function () { listWrap(); });
			});
		}

		listWrap();
	}

	/* ------------------------------- FAQs ------------------------------- */
	function initFaqs() {
		var editingId = null;

		function listWrap() {
			api('/admin/faqs?page=1&per_page=100').then(function (data) {
				var tbody = document.querySelector('#aiwc_faq_table tbody');
				tbody.innerHTML = '';
				if (!data.items || !data.items.length) {
					tbody.appendChild(el('tr', '', '<td colspan="5">No FAQs yet.</td>'));
					return;
				}
				data.items.forEach(function (faq) {
					var tr = el('tr');
					tr.appendChild(el('td', '', esc(faq.question)));
					tr.appendChild(el('td', '', esc(faq.category)));
					tr.appendChild(el('td', '', esc(faq.status)));
					tr.appendChild(el('td', '', esc(faq.sort_order)));
					var actions = el('td');
					var edit = el('button', 'button button-small', 'Edit');
					edit.addEventListener('click', function () {
						editingId = faq.id;
						setForm(faq);
					});
					var del = el('button', 'button button-small', 'Delete');
					del.addEventListener('click', function () {
						if (!window.confirm((aiwcAdmin.messages || {}).confirm_delete || 'Delete?')) { return; }
						api('/admin/faqs/' + faq.id + '/delete', { method: 'POST' }).then(function () { listWrap(); });
					});
					actions.appendChild(edit);
					actions.appendChild(del);
					tr.appendChild(actions);
					tbody.appendChild(tr);
				});
			});
		}

		function setForm(faq) {
			document.getElementById('aiwc_faq_question').value = faq.question || '';
			document.getElementById('aiwc_faq_answer').value = faq.answer || '';
			document.getElementById('aiwc_faq_category').value = faq.category || '';
			document.getElementById('aiwc_faq_status').value = faq.status || 'active';
			document.getElementById('aiwc_faq_form_title').textContent = 'Edit FAQ #' + faq.id;
			document.getElementById('aiwc_faq_cancel').hidden = false;
			window.scrollTo({ top: 0, behavior: 'smooth' });
		}

		document.getElementById('aiwc_faq_save').addEventListener('click', function () {
			var payload = {
				question: document.getElementById('aiwc_faq_question').value,
				answer: document.getElementById('aiwc_faq_answer').value,
				category: document.getElementById('aiwc_faq_category').value,
				status: document.getElementById('aiwc_faq_status').value,
				sort_order: 0
			};
			if (!payload.question || !payload.answer) {
				notice('Question and answer are required.', 'error');
				return;
			}
			var req = editingId
				? api('/admin/faqs/' + editingId, { method: 'POST', body: JSON.stringify(payload) })
				: api('/admin/faqs/create', { method: 'POST', body: JSON.stringify(payload) });
			req.then(function (res) {
				if (!res || res.success === false) { notice((aiwcAdmin.messages || {}).error || 'Error', 'error'); return; }
				editingId = null;
				document.getElementById('aiwc_faq_question').value = '';
				document.getElementById('aiwc_faq_answer').value = '';
				document.getElementById('aiwc_faq_category').value = '';
				document.getElementById('aiwc_faq_status').value = 'active';
				document.getElementById('aiwc_faq_form_title').textContent = (document.getElementById('aiwc_faq_form_title').textContent.indexOf('Edit') === 0) ? 'Add FAQ' : 'Add FAQ';
				document.getElementById('aiwc_faq_cancel').hidden = true;
				listWrap();
			});
		});

		var cancel = document.getElementById('aiwc_faq_cancel');
		if (cancel) {
			cancel.addEventListener('click', function () {
				editingId = null;
				setForm({ question: '', answer: '', category: '', status: 'active' });
				document.getElementById('aiwc_faq_form_title').textContent = 'Add FAQ';
				cancel.hidden = true;
			});
		}

		listWrap();
	}

	/* --------------------------- Conversations --------------------------- */
	function initConversations() {
		var page = 1;
		function listWrap() {
			var search = document.getElementById('aiwc_conv_search');
			api('/admin/conversations?page=' + page + '&per_page=10&search=' + encodeURIComponent(search ? search.value : '')).then(function (data) {
				var list = document.getElementById('aiwc_conversation_list');
				list.innerHTML = '';
				if (!data.items || !data.items.length) {
					list.appendChild(el('p', 'description', 'No conversations found.'));
					return;
				}
				data.items.forEach(function (conv) {
					var box = el('div', 'aiwc-conversation');
					var head = el('div', 'aiwc-conversation-header');
					var meta = el('span', '', '<strong>#' + esc(conv.id) + '</strong> &middot; ' + esc(conv.conversation_id) + ' &middot; ' + esc(conv.message_count) + ' messages &middot; ' + esc(conv.status) + ' &middot; ' + esc(conv.last_activity_at));
					var del = el('button', 'button button-small', 'Delete');
					del.addEventListener('click', function () {
						if (!window.confirm((aiwcAdmin.messages || {}).confirm_delete || 'Delete?')) { return; }
						api('/admin/conversations/' + conv.id + '/delete', { method: 'POST' }).then(function () { listWrap(); });
					});
					head.appendChild(meta);
					head.appendChild(del);
					var msgs = el('div', 'aiwc-conversation-messages');
					if (conv.messages && conv.messages.length) {
						conv.messages.forEach(function (m) {
							var wrap = el('div', 'aiwc-conv-msg ' + (m.role === 'user' ? 'user' : 'assistant'));
							wrap.appendChild(el('div', 'aiwc-conv-role', esc(m.role)));
							wrap.appendChild(el('div', 'aiwc-conv-body', esc(m.message)));
							msgs.appendChild(wrap);
						});
					} else {
						msgs.appendChild(el('p', 'description', 'No messages stored.'));
					}
					box.appendChild(head);
					box.appendChild(msgs);
					list.appendChild(box);
				});
				renderPagination('aiwc_conv_pagination', data.pages, data.total, page, function (p) { page = p; listWrap(); });
			});
		}

		var refresh = document.getElementById('aiwc_conv_refresh');
		if (refresh) { refresh.addEventListener('click', function () { page = 1; listWrap(); }); }
		var search = document.getElementById('aiwc_conv_search');
		if (search) { search.addEventListener('keydown', function (e) { if (e.key === 'Enter') { listWrap(); } }); }
		listWrap();
	}

	/* -------------------------------- Leads -------------------------------- */
	function initLeads() {
		var page = 1;
		var exportLink = document.getElementById('aiwc_lead_export');
		if (exportLink) { exportLink.setAttribute('href', aiwcAdmin.exportUrl); }

		function listWrap() {
			var search = document.getElementById('aiwc_lead_search');
			var status = document.getElementById('aiwc_lead_status');
			api('/admin/leads?page=' + page + '&per_page=20&status=' + encodeURIComponent(status ? status.value : '') + '&search=' + encodeURIComponent(search ? search.value : '')).then(function (data) {
				var tbody = document.querySelector('#aiwc_lead_table tbody');
				tbody.innerHTML = '';
				if (!data.items || !data.items.length) {
					tbody.appendChild(el('tr', '', '<td colspan="7">No leads yet.</td>'));
					return;
				}
				data.items.forEach(function (lead) {
					var tr = el('tr');
					tr.appendChild(el('td', '', esc(lead.name)));
					tr.appendChild(el('td', '', '<a href="mailto:' + esc(lead.email) + '">' + esc(lead.email) + '</a>'));
					tr.appendChild(el('td', '', esc(lead.phone)));
					tr.appendChild(el('td', '', esc((lead.message || '').substring(0, 120))));
					var statusCell = el('td');
					var select = el('select', '', '');
					['new', 'contacted', 'qualified', 'converted', 'closed'].forEach(function (s) {
						var opt = el('option', '', s);
						if (s === lead.status) { opt.selected = true; }
						select.appendChild(opt);
					});
					select.addEventListener('change', function () {
						api('/admin/leads/' + lead.id + '/status', { method: 'POST', body: JSON.stringify({ status: select.value }) });
					});
					statusCell.appendChild(select);
					tr.appendChild(statusCell);
					tr.appendChild(el('td', '', esc(lead.created_at)));
					var actions = el('td');
					var del = el('button', 'button button-small', 'Delete');
					del.addEventListener('click', function () {
						if (!window.confirm((aiwcAdmin.messages || {}).confirm_delete || 'Delete?')) { return; }
						api('/admin/leads/' + lead.id + '/delete', { method: 'POST' }).then(function () { listWrap(); });
					});
					actions.appendChild(del);
					tr.appendChild(actions);
					tbody.appendChild(tr);
				});
				renderPagination('aiwc_lead_pagination', data.pages, data.total, page, function (p) { page = p; listWrap(); });
			});
		}

		var refresh = document.getElementById('aiwc_lead_refresh');
		if (refresh) { refresh.addEventListener('click', function () { page = 1; listWrap(); }); }
		var status = document.getElementById('aiwc_lead_status');
		if (status) { status.addEventListener('change', function () { page = 1; listWrap(); }); }
		var search = document.getElementById('aiwc_lead_search');
		if (search) { search.addEventListener('keydown', function (e) { if (e.key === 'Enter') { listWrap(); } }); }
		listWrap();
	}

	/* ------------------------------ Analytics ------------------------------ */
	function initAnalytics() {
		api('/admin/stats').then(function (stats) {
			var cards = document.getElementById('aiwc_analytics_cards');
			cards.innerHTML = '';
			var items = [
				[stats.total_conversations, 'Total conversations'],
				[stats.conversations_today, 'Today'],
				[stats.total_messages, 'Messages'],
				[stats.leads, 'Leads'],
				[stats.answered, 'Answered'],
				[stats.failed, 'Unanswered'],
				[stats.avg_length, 'Avg length'],
				[stats.avg_response_time + 's', 'Avg response'],
				[stats.tokens, 'Tokens used'],
				['$' + stats.estimated_cost, 'Estimated cost']
			];
			items.forEach(function (pair) {
				var card = el('div', 'aiwc-card');
				card.appendChild(el('span', 'aiwc-card-value', esc(pair[0])));
				card.appendChild(el('span', 'aiwc-card-label', esc(pair[1])));
				cards.appendChild(card);
			});

			if (stats.daily && stats.daily.length) {
				var chart = el('div', 'aiwc-chart');
				var max = Math.max.apply(null, stats.daily.map(function (d) { return Math.max(d.conversations, d.messages, 1); }));
				stats.daily.forEach(function (d) {
					var col = el('div', 'aiwc-chart-col');
					var bar = el('div', 'aiwc-chart-bar', '');
					bar.style.height = Math.max(3, Math.round((d.messages / max) * 160)) + 'px';
					bar.title = d.date + ' — ' + d.conversations + ' conversations, ' + d.messages + ' messages';
					col.appendChild(bar);
					col.appendChild(el('span', '', d.date.substring(5)));
					chart.appendChild(col);
				});
				document.getElementById('aiwc_chart').innerHTML = '';
				document.getElementById('aiwc_chart').appendChild(chart);
			}

			var popTbody = document.querySelector('#aiwc_popular_table tbody');
			popTbody.innerHTML = '';
			(stats.popular || []).slice(0, 8).forEach(function (row) {
				var tr = el('tr');
				tr.appendChild(el('td', '', esc(row.message)));
				tr.appendChild(el('td', '', esc(row.total)));
				popTbody.appendChild(tr);
			});

			var failTbody = document.querySelector('#aiwc_failed_table tbody');
			failTbody.innerHTML = '';
			(stats.failed_questions || []).slice(0, 8).forEach(function (row) {
				var tr = el('tr');
				tr.appendChild(el('td', '', esc(row.question)));
				tr.appendChild(el('td', '', esc(row.total)));
				failTbody.appendChild(tr);
			});
		});
	}

	/* ------------------------------ Help page ------------------------------ */
	function initHelp() {
		var healthBtn = document.getElementById('aiwc_health_run');
		if (healthBtn) {
			healthBtn.addEventListener('click', function () {
				api('/admin/health').then(function (data) {
					var box = document.getElementById('aiwc_health_results');
					box.innerHTML = '';
					if (!data.checks) { return; }
					Object.keys(data.checks).forEach(function (key) {
						var c = data.checks[key];
						var row = el('div', 'aiwc-health ' + esc(c.status), '<strong>' + esc(c.label) + ':</strong> ' + esc(c.detail));
						box.appendChild(row);
					});
				});
			});
		}

		var loadBtn = document.getElementById('aiwc_logs_load');
		if (loadBtn) {
			loadBtn.addEventListener('click', function () {
				api('/admin/logs').then(function (data) {
					var box = document.getElementById('aiwc_logs_container');
					box.innerHTML = '';
					if (!data.items || !data.items.length) {
						box.appendChild(el('div', '', 'No logs.'));
						return;
					}
					data.items.forEach(function (log) {
						var line = document.createElement('div');
						line.innerHTML = '<span class="aiwc-log-level">' + esc(log.level) + '</span> [' + esc(log.event) + '] ' + esc(log.message) + ' — ' + esc(log.created_at);
						box.appendChild(line);
					});
				});
			});
		}

		var clearBtn = document.getElementById('aiwc_logs_clear');
		if (clearBtn) {
			clearBtn.addEventListener('click', function () {
				api('/admin/logs/clear', { method: 'POST' }).then(function () {
					document.getElementById('aiwc_logs_container').innerHTML = '';
					notice((aiwcAdmin.messages || {}).saved || 'Saved');
				});
			});
		}
	}

	/* ------------------------------ AI test ------------------------------ */
	function initAiTest() {
		var run = document.getElementById('aiwc_test_run');
		if (!run) { return; }
		run.addEventListener('click', function () {
			var input = document.getElementById('aiwc_test_message');
			var box = document.getElementById('aiwc_test_result');
			box.hidden = false;
			box.textContent = 'Sending…';
			api('/admin/test', { method: 'POST', body: JSON.stringify({ message: input.value }) }).then(function (data) {
				if (!data || data.success === false) {
					box.textContent = (data && data.message) || 'Error';
					return;
				}
				var html = '<strong>Response:</strong>\n' + esc(data.message);
				if (data.sources && data.sources.length) {
					html += '\n\n<div class="aiwc-sources"><strong>Sources:</strong>\n' + data.sources.map(function (src) {
						return '• ' + esc(src.title) + (src.url ? ' (' + esc(src.url) + ')' : '');
					}).join('\n') + '</div>';
				}
				html += '\n\n<em>Response time: ' + esc(data.response_time) + 's</em>';
				box.innerHTML = html;
			});
		});
	}

	/* ------------------------------ Pagination ------------------------------ */
	function renderPagination(id, pages, total, current, onClick) {
		var wrap = document.getElementById(id);
		if (!wrap) { return; }
		wrap.innerHTML = '';
		if (!pages || pages < 2) { return; }
		var label = el('span', 'aiwc-pagination-label', total + ' items');
		wrap.appendChild(label);
		for (var i = 1; i <= pages && i <= 10; i++) {
			var btn = el('button', 'button button-small' + (i === current ? ' button-primary' : ''), String(i));
			btn.addEventListener('click', function () { onClick(parseInt(this.textContent, 10)); });
			wrap.appendChild(btn);
		}
	}

	/* --------------------- Content types selector --------------------- */
	function initContentTypes() {
		var grid = document.getElementById('aiwc_content_types');
		var save = document.getElementById('aiwc_types_save');
		if (!grid || !save) { return; }

		var boxes = Array.prototype.slice.call(grid.querySelectorAll('input[data-type]'));
		var metaBoxes = Array.prototype.slice.call(grid.querySelectorAll('input[data-meta-type]'));
		var meta = document.getElementById('aiwc_index_meta');

		function selectedTypes() {
			return boxes.filter(function (box) { return box.checked; }).map(function (box) { return box.getAttribute('data-type'); });
		}

		function selectedMeta() {
			return metaBoxes.filter(function (box) { return box.checked; }).map(function (box) { return box.getAttribute('data-meta-type'); });
		}

		function applyMetaState() {
			var on = meta ? meta.checked : true;
			metaBoxes.forEach(function (box) { box.disabled = !on; });
		}

		function markDirty() {
			save.disabled = false;
		}

		boxes.forEach(function (box) { box.addEventListener('change', markDirty); });
		metaBoxes.forEach(function (box) { box.addEventListener('change', markDirty); });
		if (meta) { meta.addEventListener('change', function () { applyMetaState(); markDirty(); }); }
		applyMetaState();

		save.addEventListener('click', function () {
			save.disabled = true;
			var payload = { knowledge: { content_types: selectedTypes(), meta_types: selectedMeta() } };
			if (meta) { payload.knowledge.index_custom_fields = meta.checked; }
			api('/admin/settings', {
				method: 'POST',
				body: JSON.stringify(payload)
			}).then(function (data) {
				if (!data || data.success === false) {
					notice((data && data.message) || (aiwcAdmin.messages || {}).error || 'Error', 'error');
					save.disabled = false;
					return;
				}
				notice((aiwcAdmin.messages || {}).saved || 'Settings saved.');
				window.aiwcKnowledgeReload && window.aiwcKnowledgeReload();
				if (document.getElementById('aiwc_index_step')) { document.getElementById('aiwc_index_step').disabled = false; }
			});
		});
	}

	function init() {
		if (document.getElementById('aiwc_index_start') || document.getElementById('aiwc_knowledge_table')) {
			initKnowledge();
		}
		initContentTypes();
		if (document.getElementById('aiwc_faq_save')) { initFaqs(); }
		if (document.getElementById('aiwc_conversation_list')) { initConversations(); }
		if (document.getElementById('aiwc_lead_table')) { initLeads(); }
		if (document.getElementById('aiwc_analytics_cards')) { initAnalytics(); }
		if (document.getElementById('aiwc_health_run') || document.getElementById('aiwc_logs_load')) { initHelp(); }
		if (document.getElementById('aiwc_test_run')) { initAiTest(); }
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();