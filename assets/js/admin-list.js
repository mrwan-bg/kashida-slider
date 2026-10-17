(function () {
	'use strict';
	var L = window.KS_LIST || {};
	var dlg = null, pendingHref = null, opener = null;

	function toast(msg, bad) {
		var t = document.getElementById('ks-toast');
		if (!t) {
			t = document.createElement('div');
			t.id = 'ks-toast'; t.className = 'ks-toast'; t.setAttribute('role', 'status'); t.setAttribute('aria-live', 'polite');
			document.body.appendChild(t);
		}
		t.className = 'ks-toast is-visible' + (bad ? ' is-bad' : '');
		t.textContent = msg;
		clearTimeout(toast.t);
		toast.t = setTimeout(function () { t.className = 'ks-toast'; }, 2200);
	}

	function copyText(text) {
		if (navigator.clipboard && window.isSecureContext) {
			return navigator.clipboard.writeText(text);
		}
		return new Promise(function (resolve, reject) {
			var ta = document.createElement('textarea');
			ta.value = text; ta.setAttribute('readonly', ''); ta.style.position = 'fixed'; ta.style.insetBlockStart = '-1000px';
			document.body.appendChild(ta); ta.select();
			var ok = false;
			try { ok = document.execCommand('copy'); } catch (e) { ok = false; }
			document.body.removeChild(ta);
			if (ok) { resolve(); } else { reject(); }
		});
	}

	function buildDialog() {
		dlg = document.createElement('dialog');
		dlg.className = 'ks-dialog';
		dlg.setAttribute('aria-labelledby', 'ks-dlg-title');
		var f = document.createElement('form');
		f.method = 'dialog';
		var h = document.createElement('h2'); h.id = 'ks-dlg-title'; h.textContent = L.dlgTitle;
		var p = document.createElement('p'); p.id = 'ks-dlg-body';
		var row = document.createElement('div'); row.className = 'ks-dlg-actions';
		var no = document.createElement('button'); no.type = 'submit'; no.value = 'cancel'; no.className = 'button'; no.textContent = L.dlgNo;
		var ok = document.createElement('button'); ok.type = 'submit'; ok.value = 'ok'; ok.className = 'button ks-btn-danger'; ok.textContent = L.dlgOk;
		row.appendChild(no); row.appendChild(ok);
		f.appendChild(h); f.appendChild(p); f.appendChild(row);
		dlg.appendChild(f);
		document.body.appendChild(dlg);
		dlg.addEventListener('close', function () {
			if (dlg.returnValue === 'ok' && pendingHref) { window.location.href = pendingHref; }
			else if (opener) { opener.focus(); }
			pendingHref = null;
		});
		dlg.addEventListener('click', function (e) { if (e.target === dlg) { dlg.close('cancel'); } });
	}

	document.addEventListener('click', function (e) {
		var del = e.target.closest('.ks-delete');
		if (del) {
			e.preventDefault();
			var name = del.getAttribute('data-name') || '';
			if (typeof HTMLDialogElement !== 'function') {
				if (window.confirm((L.dlgBody || '').replace('%s', name))) { window.location.href = del.href; }
				return;
			}
			if (!dlg) { buildDialog(); }
			dlg.returnValue = '';
			pendingHref = del.href; opener = del;
			dlg.querySelector('#ks-dlg-body').textContent = (L.dlgBody || '').replace('%s', name);
			dlg.showModal();
			return;
		}
		var btn = e.target.closest('.ks-copy');
		if (btn) {
			copyText(btn.getAttribute('data-copy')).then(function () { toast(L.copied); }, function () { toast(L.copyFail, true); });
		}
	});
})();
