/* ============================================================
   SIM PUSKESMAS AKELAMO — Front-end interaksi
   Vanilla JS, tanpa framework, kompatibel CSP ketat (script-src 'self')
   ============================================================ */
'use strict';

/* ---------- Util ---------- */
const $ = (sel, root = document) => root.querySelector(sel);
const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));

const esc = (v) => String(v === undefined || v === null ? '' : v)
    .replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

const fmtN = (n) => new Intl.NumberFormat('id-ID').format(Number(n) || 0);
const rupiah = (n) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(Number(n) || 0);
const fmtDate = (x) => (x ? String(x).slice(0, 10) : '–');

const toast = (msg, error = false) => {
    const box = $('#toast');
    if (!box) return;
    box.innerHTML = `<div class="notification ${error ? 'error' : ''}">${esc(msg)}</div>`;
    setTimeout(() => { box.innerHTML = ''; }, 4200);
};

/* ---------- API fetch dengan CSRF ---------- */
async function api(url, method = 'GET', body) {
    const headers = {};
    if (body) headers['Content-Type'] = 'application/json';
    if (!['GET', 'HEAD'].includes(method)) {
        const token = $('meta[name="csrf-token"]')?.content;
        if (token) headers['X-CSRF-TOKEN'] = token;
    }
    const res = await fetch(url, {
        method,
        credentials: 'same-origin',
        headers,
        body: body ? JSON.stringify(body) : undefined,
    });
    let data = {};
    try { data = await res.json(); } catch { /* kosong */ }
    if (res.status === 401 && !url.includes('/login')) {
        window.location.href = '/login';
        throw new Error('Sesi sudah habis, silakan masuk kembali');
    }
    if (res.status === 419) {
        throw new Error('Sesi kedaluwarsa. Muat ulang halaman lalu coba lagi.');
    }
    if (!res.ok) throw new Error(data.error || data.message || `Gagal (${res.status})`);
    return data;
}

const confirmDialog = (msg) => window.confirm(msg);

/* ============================================================
   1) INTIP PASSWORD — berlaku untuk SEMUA input password
   ============================================================ */
function initPasswordPeek() {
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-toggle-password]');
        if (!btn) return;
        const input = document.getElementById(btn.dataset.togglePassword);
        if (!input) return;
        const showing = input.type === 'text';
        input.type = showing ? 'password' : 'text';
        btn.classList.toggle('showing', !showing);
        btn.setAttribute('aria-pressed', String(!showing));
        btn.setAttribute('aria-label', showing ? 'Intip kata sandi' : 'Sembunyikan kata sandi');
        input.focus();
    });
}

/* ============================================================
   2) CAPTCHA — muat ulang gambar saat diklik
   ============================================================ */
function initCaptcha() {
    const img = $('#captcha-img');
    const refresh = $('#captcha-refresh');
    const reload = () => { if (img) img.src = `/captcha?ts=${Date.now()}`; };
    if (img) {
        img.addEventListener('click', reload);
        img.style.cursor = 'pointer';
        img.title = 'Klik untuk ganti kode';
    }
    refresh?.addEventListener('click', reload);

    // Bersihkan field captcha setelah gagal login (halaman dimuat ulang dengan error).
    if ($('.form-error-box')) {
        const captchaInput = $('#captcha');
        if (captchaInput) captchaInput.value = '';
    }
}

/* ============================================================
   3) ANIMASI COUNT-UP dashboard
   ============================================================ */
function animateCountUps() {
    const els = $$('.count-up');
    if (!els.length) return;
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    els.forEach((el) => {
        const target = parseFloat(el.dataset.count || '0');
        const format = el.dataset.format || 'number';

        if (reduceMotion || target === 0) {
            el.textContent = format === 'currency' ? rupiah(target) : (format === 'rating' ? `${fmtN(target)} / 5` : fmtN(target));
            return;
        }

        const duration = 1100;
        const start = performance.now();
        const step = (now) => {
            const p = Math.min((now - start) / duration, 1);
            const eased = 1 - Math.pow(1 - p, 3); // easeOutCubic
            const val = target * eased;
            el.textContent = format === 'currency'
                ? rupiah(val)
                : (format === 'rating' ? `${fmtN(val.toFixed(2))} / 5` : fmtN(Math.round(val)));
            if (p < 1) requestAnimationFrame(step);
        };
        requestAnimationFrame(step);
    });
}

/* ============================================================
   4) MENU MOBILE
   ============================================================ */
function initMobileMenu() {
    $('#mobile-toggle')?.addEventListener('click', () => document.body.classList.toggle('mobile-open'));
    $$('.nav-item').forEach((n) => n.addEventListener('click', () => document.body.classList.remove('mobile-open')));
}

/* ============================================================
   5) CRUD MODUL DATA (tabel + modal + pencarian + CSV)
   ============================================================ */
function cellValue(module, key, v, row) {
    if (v === null || v === undefined || v === '') return '–';
    if (key.includes('date')) return esc(fmtDate(v));
    if (key === 'amount') return esc(rupiah(v));
    if (key === 'stock') return `<span class="pill ${Number(v) <= Number(row.min_stock ?? 0) ? 'orange' : ''}">${fmtN(v)}</span>`;
    if (key === 'type') return `<span class="pill ${v === 'Pengeluaran' ? 'orange' : ''}">${esc(v)}</span>`;
    if (key === 'time_start' || key === 'time_end') return esc(String(v).slice(0, 5));
    return esc(v);
}

function initCrud() {
    const root = $('#crud-root');
    if (!root) return;

    const module = root.dataset.module;
    const title = root.dataset.title;
    const canEdit = root.dataset.canEdit === '1';
    const columns = JSON.parse(root.dataset.columns || '[]');
    const fields = JSON.parse(root.dataset.fields || '[]');
    const doctors = JSON.parse(root.dataset.doctors || '[]');
    let rows = JSON.parse(root.dataset.rows || '[]');
    let search = '';

    const filtered = () => rows.filter((r) => !search
        || Object.values(r).some((v) => String(v ?? '').toLowerCase().includes(search.toLowerCase())));

    function render() {
        const data = filtered();
        root.innerHTML = `<table class="data-table"><thead><tr>${columns.map((c) => `<th>${esc(c[1])}</th>`).join('')}${canEdit ? '<th>Aksi</th>' : ''}</tr></thead>
        <tbody>${data.length ? data.map((r, i) => `<tr style="animation-delay:${Math.min(i * 0.03, 0.4)}s">${columns.map(([key]) => `<td title="${esc(r[key] ?? '')}">${cellValue(module, key, r[key], r)}</td>`).join('')}
        ${canEdit ? `<td><div class="row-actions"><button class="btn tiny secondary" data-edit="${r.id}">Edit</button><button class="btn tiny danger" data-delete="${r.id}">Hapus</button></div></td>` : ''}</tr>`).join('')
            : `<tr><td class="empty" colspan="${columns.length + 1}">Belum ada data untuk ditampilkan.</td></tr>`}</tbody></table>`;

        $$('[data-edit]', root).forEach((b) => b.addEventListener('click', () => openForm(rows.find((r) => String(r.id) === b.dataset.edit))));
        $$('[data-delete]', root).forEach((b) => b.addEventListener('click', () => removeRow(b.dataset.delete)));
    }

    /* --- Modal --- */
    function modal(modalTitle, content) {
        $('.modal-backdrop')?.remove();
        const node = document.createElement('div');
        node.className = 'modal-backdrop';
        node.innerHTML = `<div class="modal" role="dialog" aria-modal="true" aria-label="${esc(modalTitle)}">
            <div class="modal-head"><h2>${esc(modalTitle)}</h2><button class="close" type="button" aria-label="Tutup">✕</button></div>${content}</div>`;
        document.body.append(node);
        $('.close', node).addEventListener('click', () => node.remove());
        node.addEventListener('click', (e) => { if (e.target === node) node.remove(); });
        return node;
    }

    function pwFieldHTML(id, label, hint) {
        return `<div class="field wide"><label for="${id}">${esc(label)}</label>
            <div class="pw-wrap"><input id="${id}" name="password" type="password" autocomplete="new-password" minlength="12" required>
            <button type="button" class="pw-toggle" data-toggle-password="${id}" aria-label="Intip kata sandi" aria-pressed="false" title="Intip kata sandi">
                <svg class="eye-open" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                <svg class="eye-closed" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
            </button></div>${hint ? `<small class="muted">${esc(hint)}</small>` : ''}</div>`;
    }

    function inputFor([key, label, type, required, options], row = {}) {
        let value = row[key] ?? '';
        if (type === 'date') value = fmtDate(value) === '–' ? '' : fmtDate(value);
        if (type === 'time') value = String(value).slice(0, 5);
        if (type === 'number' && !row.id && key === 'min_stock') value = '10';
        const req = required ? ' required' : '';

        if (['text', 'number', 'date', 'time'].includes(type)) {
            return `<div class="field"><label for="f_${key}">${esc(label)}${required ? ' *' : ''}</label>
                <input id="f_${key}" name="${key}" type="${type}" value="${esc(value)}"${req} ${type === 'number' ? 'min="0" step="any"' : ''}></div>`;
        }
        if (type === 'textarea') {
            return `<div class="field wide"><label for="f_${key}">${esc(label)}</label>
                <textarea name="${key}" id="f_${key}">${esc(value)}</textarea></div>`;
        }
        const opts = type === 'doctor' ? doctors.map((d) => [d.id, d.name]) : (options || []).map((o) => [o, o]);
        return `<div class="field"><label for="f_${key}">${esc(label)}${required ? ' *' : ''}</label>
            <select name="${key}" id="f_${key}"${req}><option value="">Pilih...</option>
            ${opts.map(([v, l]) => `<option value="${esc(v)}" ${String(value) === String(v) ? 'selected' : ''}>${esc(l)}</option>`).join('')}</select></div>`;
    }

    function openForm(row) {
        const isEdit = !!row;
        const m = modal(`${isEdit ? 'Edit' : 'Tambah'} ${title}`, `
            <form id="crud-form"><div class="form-grid">${fields.map((f) => inputFor(f, row || {})).join('')}</div>
            <p id="modal-error" class="form-error"></p>
            <div class="modal-actions">
                <button type="button" class="btn secondary" id="cancel">Batal</button>
                <button type="submit" class="btn">Simpan Data</button>
            </div></form>`);

        $('#cancel', m).addEventListener('click', () => m.remove());
        $('#crud-form', m).addEventListener('submit', async (e) => {
            e.preventDefault();
            const form = e.currentTarget;
            const submit = $('[type=submit]', form);
            submit.disabled = true;
            const values = {};
            for (const [key, , type] of fields) {
                const v = form.elements[key].value;
                values[key] = (type === 'number' || type === 'doctor') ? (v === '' ? null : Number(v)) : v;
            }
            try {
                await api(`/modul/${module}${isEdit ? `/${row.id}` : ''}`, isEdit ? 'PUT' : 'POST', values);
                m.remove();
                toast('Data berhasil disimpan');
                window.location.reload();
            } catch (err) {
                $('#modal-error', m).textContent = err.message;
            } finally {
                submit.disabled = false;
            }
        });
    }

    async function removeRow(id) {
        if (!confirmDialog('Hapus data ini secara permanen? Tindakan ini dicatat dalam log audit.')) return;
        try {
            await api(`/modul/${module}/${id}`, 'DELETE');
            toast('Data telah dihapus');
            rows = rows.filter((r) => String(r.id) !== String(id));
            render();
        } catch (err) {
            toast(err.message, true);
        }
    }

    function exportCSV() {
        const data = filtered();
        const csv = [
            columns.map((c) => `"${c[1]}"`).join(','),
            ...data.map((r) => columns.map(([k]) => `"${String(r[k] ?? '').replace(/"/g, '""')}"`).join(',')),
        ].join('\r\n');
        const blob = new Blob(['\ufeff' + csv], { type: 'text/csv;charset=utf-8' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `akelamo_${module}_${new Date().toISOString().slice(0, 10)}.csv`;
        a.click();
        URL.revokeObjectURL(url);
        toast('File CSV berhasil dibuat. Simpan dengan aman.');
    }

    $('#search')?.addEventListener('input', (e) => { search = e.target.value; render(); });
    $('#export')?.addEventListener('click', exportCSV);
    $('#add')?.addEventListener('click', () => openForm(null));

    render();
}

/* ============================================================
   6) HALAMAN SURVEI STAF
   ============================================================ */
function initSurveys() {
    $$('[data-survey-delete]').forEach((b) => b.addEventListener('click', async () => {
        if (!confirmDialog('Hapus respon survei ini?')) return;
        try {
            await api(`/survei/${b.dataset.surveyDelete}`, 'DELETE');
            toast('Respon survei dihapus');
            window.location.reload();
        } catch (err) {
            toast(err.message, true);
        }
    }));

    $('#copy-link')?.addEventListener('click', async (e) => {
        const link = e.currentTarget.dataset.link || '';
        try {
            await navigator.clipboard.writeText(link);
            toast('Tautan tersalin');
        } catch {
            toast('Tidak dapat menyalin tautan', true);
        }
    });
}

/* ============================================================
   7) HALAMAN PENGGUNA (admin)
   ============================================================ */
function initUsers() {
    const modal = (t, content) => {
        $('.modal-backdrop')?.remove();
        const node = document.createElement('div');
        node.className = 'modal-backdrop';
        node.innerHTML = `<div class="modal" role="dialog" aria-modal="true"><div class="modal-head"><h2>${esc(t)}</h2><button class="close" type="button" aria-label="Tutup">✕</button></div>${content}</div>`;
        document.body.append(node);
        $('.close', node).addEventListener('click', () => node.remove());
        node.addEventListener('click', (e) => { if (e.target === node) node.remove(); });
        return node;
    };

    // Ikon mata dipakai ulang untuk semua input password modal.
    const eyeSVG = `<svg class="eye-open" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
        <svg class="eye-closed" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>`;
    const pwInput = (id) => `<div class="pw-wrap"><input id="${id}" name="password" type="password" autocomplete="new-password" minlength="12" required>
        <button type="button" class="pw-toggle" data-toggle-password="${id}" aria-label="Intip kata sandi" aria-pressed="false" title="Intip kata sandi">${eyeSVG}</button></div>`;

    $('#add-user')?.addEventListener('click', () => {
        const m = modal('Tambah Pengguna', `
            <form id="user-form"><div class="form-grid">
                <div class="field"><label for="u_name">Nama Lengkap</label><input id="u_name" name="full_name" required maxlength="150"></div>
                <div class="field"><label for="u_username">Username</label><input id="u_username" name="username" required minlength="3" maxlength="80" pattern="[A-Za-z0-9_.\\-]+"></div>
                <div class="field"><label for="u_role">Peran</label><select id="u_role" name="role" required>
                    <option value="admin">Admin</option><option value="dokter">Dokter</option>
                    <option value="petugas">Petugas</option><option value="keuangan">Keuangan</option></select></div>
                <div class="field"><label for="u_pass">Password (min. 12 karakter)</label>${pwInput('u_pass')}</div>
            </div><p class="form-error" id="modal-error"></p>
            <div class="modal-actions"><button class="btn" type="submit">Buat Pengguna</button></div></form>`);

        $('#user-form', m).addEventListener('submit', async (e) => {
            e.preventDefault();
            const form = e.currentTarget;
            try {
                await api('/pengguna', 'POST', {
                    full_name: form.full_name.value.trim(),
                    username: form.username.value.trim(),
                    role: form.role.value,
                    password: form.password.value,
                });
                m.remove();
                toast('Akun berhasil dibuat');
                window.location.reload();
            } catch (err) {
                $('#modal-error', m).textContent = err.message;
            }
        });
    });

    $$('[data-reset]').forEach((b) => b.addEventListener('click', () => {
        const m = modal('Reset Kata Sandi', `
            <form id="pass-form"><p class="muted">Sesi akun yang direset akan segera diputus.</p>
            <div class="field"><label for="r_pass">Kata sandi baru (minimal 12 karakter)</label>${pwInput('r_pass')}</div>
            <p class="form-error" id="modal-error"></p>
            <div class="modal-actions"><button class="btn" type="submit">Simpan Password Baru</button></div></form>`);

        $('#pass-form', m).addEventListener('submit', async (e) => {
            e.preventDefault();
            try {
                await api(`/pengguna/${b.dataset.reset}/reset-password`, 'POST', { password: $('#r_pass', m).value });
                m.remove();
                toast('Password berhasil direset');
            } catch (err) {
                $('#modal-error', m).textContent = err.message;
            }
        });
    }));

    $$('[data-active]').forEach((b) => b.addEventListener('click', async () => {
        const target = b.dataset.status === '1' ? false : true;
        if (!confirmDialog('Ubah status akun ini?')) return;
        try {
            await api(`/pengguna/${b.dataset.active}`, 'PATCH', { is_active: target });
            window.location.reload();
        } catch (err) {
            toast(err.message, true);
        }
    }));
}

/* ============================================================
   8) SURVEI PUBLIK (tanpa login)
   ============================================================ */
function initPublicSurvey() {
    const form = $('#survey-form');
    if (!form) return;

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const button = $('button[type="submit"]', form);
        const feedback = $('#feedback');
        if (feedback) feedback.textContent = '';
        const selected = $('input[name="rating"]:checked', form);
        if (!selected) {
            if (feedback) feedback.textContent = 'Pilih nilai pelayanan terlebih dahulu.';
            return;
        }
        button.disabled = true;
        try {
            await api('/survey', 'POST', {
                service: $('#service').value,
                rating: Number(selected.value),
                suggestion: $('#suggestion').value,
                website: $('#website').value,
            });
            $('#survey-area').innerHTML = `<div style="text-align:center;padding:35px 0">
                <div style="font-size:58px;animation:notifIn .5s ease both">✅</div>
                <h1>Terima kasih!</h1>
                <p>Penilaian Anda berhasil diterima dan akan menjadi bahan peningkatan layanan Puskesmas Akelamo.</p>
                <a class="btn" style="display:inline-block;margin-top:14px" href="/survey">Kirim penilaian lain</a></div>`;
        } catch (err) {
            if (feedback) feedback.textContent = err.message;
        } finally {
            button.disabled = false;
        }
    });
}

/* ============================================================
   8b) TERMINAL SUPER ADMIN (whitelist artisan)
   ============================================================ */
function initTerminal() {
    const root = $('#terminal-root');
    if (!root) return;

    const output = $('#term-output');
    const input = $('#term-input');
    const form = $('#term-form');
    const passRow = $('#term-pass-row');
    const passInput = $('#term-pass');
    const commands = JSON.parse(root.dataset.commands || '{}');
    const history = [];
    let historyIdx = 0;

    const print = (html, cls = '') => {
        const div = document.createElement('div');
        div.className = `term-line ${cls}`;
        div.innerHTML = html;
        output.append(div);
        output.scrollTop = output.scrollHeight;
        return div;
    };
    const printText = (t, cls = '') => print(esc(t), cls);

    // Banner selamat datang
    print('<span class="term-ok">◆ Akelamo Admin Console</span>');
    printText('Ketik perintah artisan (mis. migrate:status) atau gunakan tombol cepat di bawah.', 'term-dim');
    printText('Perintah destruktif menuntut konfirmasi kata sandi akun Anda.', 'term-dim');
    print('&nbsp;');

    const currentName = () => {
        const raw = input.value.trim().replace(/^(sudo\s+)?(php\s+)?artisan\s+/i, '');
        return raw.split(/\s+/)[0]?.toLowerCase() || '';
    };

    const updateDanger = () => {
        const danger = !!commands[currentName()];
        passRow.classList.toggle('show', danger);
        if (!danger && passInput) passInput.value = '';
    };

    input.addEventListener('input', updateDanger);

    // Riwayat perintah: panah atas/bawah
    input.addEventListener('keydown', (e) => {
        if (e.key === 'ArrowUp' && historyIdx > 0) {
            historyIdx -= 1;
            input.value = history[historyIdx];
            updateDanger();
            e.preventDefault();
        } else if (e.key === 'ArrowDown') {
            if (historyIdx < history.length - 1) {
                historyIdx += 1;
                input.value = history[historyIdx];
            } else {
                historyIdx = history.length;
                input.value = '';
            }
            updateDanger();
            e.preventDefault();
        }
    });

    $$('.term-btn', root).forEach((btn) => btn.addEventListener('click', () => {
        input.value = btn.dataset.cmd;
        updateDanger();
        input.focus();
        if (commands[btn.dataset.cmd]) passInput?.focus();
    }));

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const raw = input.value.trim();
        if (!raw) return;

        history.push(raw);
        historyIdx = history.length;

        print(`<span class="term-prompt">akelamo@puskesmas:~$</span> ${esc(raw)}`);
        input.value = '';
        updateDanger();

        const button = $('#term-run');
        button.disabled = true;
        const spinner = print('<span class="term-dim">menjalankan…</span> <span class="cursor">▊</span>');

        try {
            const res = await api('/terminal/run', 'POST', {
                command: raw,
                password: passInput?.value || null,
            });
            spinner.remove();
            printText(res.output || '(tanpa output)', res.ok ? '' : 'term-err');
            print(`<span class="${res.ok ? 'term-ok' : 'term-err'}">${res.ok ? '✓' : '✗'} selesai dalam ${res.duration_ms} ms</span>`);
            if (res.notice) {
                print(`<span class="term-warn-line">⚠ ${esc(res.notice)}</span>`);
                setTimeout(() => { window.location.href = '/login'; }, 2600);
            }
            print('&nbsp;');
        } catch (err) {
            spinner.remove();
            printText(err.message, 'term-err');
            print('&nbsp;');
            // Bila server meminta sandi, tampilkan baris konfirmasi.
            if (/destruktif|kata sandi/i.test(err.message)) {
                passRow.classList.add('show');
                passInput?.focus();
            }
        } finally {
            if (passInput) passInput.value = '';
            button.disabled = false;
            input.focus();
        }
    });
}

/* ============================================================
   3b) BAR CHART ANIMASI + VENN COUNT-UP (dashboard)
   ============================================================ */
function initCharts() {
    // --- Bar chart: batang tumbuh dari 0 + label nilai muncul ---
    $$('.bar-chart').forEach((chart) => {
        const labels = JSON.parse(chart.dataset.labels || '[]');
        const series = JSON.parse(chart.dataset.series || '[]');
        const format = chart.dataset.format || 'number';
        const dual = series.length > 1;
        const fmt = (v) => (format === 'currency'
            ? new Intl.NumberFormat('id-ID', { notation: 'compact', maximumFractionDigits: 1 }).format(v)
            : new Intl.NumberFormat('id-ID').format(v));
        const max = Math.max(1, ...series.flat());

        labels.forEach((label, i) => {
            const col = document.createElement('div');
            col.className = 'bar-col';
            const group = document.createElement('div');
            group.className = 'bar-group';

            series.forEach((data, s) => {
                const bar = document.createElement('div');
                bar.className = 'bar' + (dual && s === 1 ? ' bar-out' : '');
                const val = Number(data[i]) || 0;
                bar.title = `${label} · ${dual ? (s === 0 ? 'Pemasukan' : 'Pengeluaran') : 'Jumlah'}: ${format === 'currency' ? rupiah(val) : fmtN(val)}`;
                bar.innerHTML = `<span class="bar-val">${esc(fmt(val))}</span>`;
                // delay stagger per kolom & seri
                bar.style.transitionDelay = `${0.05 + i * 0.07 + s * 0.04}s`;
                group.append(bar);
                requestAnimationFrame(() => {
                    requestAnimationFrame(() => {
                        bar.style.height = `${Math.max(3, (val / max) * 100)}%`;
                        setTimeout(() => bar.classList.add('loaded'), 900);
                    });
                });
            });

            const lbl = document.createElement('div');
            lbl.className = 'bar-lbl';
            lbl.textContent = label;
            col.append(group, lbl);
            chart.append(col);
        });
    });

    // --- Venn: angka count-up pada teks SVG ---
    $$('.venn-num[data-count]').forEach((el) => {
        const target = parseFloat(el.dataset.count || '0');
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches || target === 0) {
            el.textContent = fmtN(target);
            return;
        }
        const start = performance.now();
        const dur = 900;
        const tick = (now) => {
            const p = Math.min((now - start) / dur, 1);
            el.textContent = fmtN(Math.round(target * (1 - Math.pow(1 - p, 3))));
            if (p < 1) requestAnimationFrame(tick);
        };
        requestAnimationFrame(tick);
    });
}

/* ============================================================
   3c) HALAMAN LAPORAN (preview + export)
   ============================================================ */
function initReports() {
    const root = $('#report-root');
    if (!root) return;

    const form = $('#report-form');
    const output = $('#report-output');
    const typeSelect = form.elements.report_type;

    const tableHead = (cols) => `<tr>${cols.map((c) => `<th>${esc(c)}</th>`).join('')}</tr>`;

    const render = (data) => {
        const sum = data.summary.map((s, i) => `<div class="report-sum" style="animation-delay:${i * 0.07}s"><small>${esc(s.label)}</small><strong>${esc(s.value)}</strong></div>`).join('');
        const rows = data.rows.length
            ? data.rows.map((r) => `<tr>${data.columns.map((c) => `<td>${esc(r[c] ?? '–')}</td>`).join('')}</tr>`).join('')
            : `<tr><td class="empty" colspan="${data.columns.length}">Tidak ada data pada rentang tanggal ini.</td></tr>`;
        output.innerHTML = `
            <div class="report-summary">${sum}</div>
            <div class="table-wrap"><table class="data-table">
                <thead>${tableHead(data.columns)}</thead>
                <tbody>${rows}</tbody>
            </table></div>
            <p class="footer-note">Pratinjau menampilkan maksimal 200 baris. Export berisi seluruh data pada rentang tanggal.</p>`;
    };

    const load = async () => {
        output.innerHTML = '<p class="empty">Memuat laporan…</p>';
        const params = new URLSearchParams(new FormData(form)).toString();
        try {
            const data = await api(`/laporan/data?${params}`);
            render(data);
            // Set parameter export pada tombol
            $$('.btn-export').forEach((b) => { b.href = `${b.dataset.href}?${params}`; });
        } catch (err) {
            output.innerHTML = `<div class="form-error-box">${esc(err.message)}</div>`;
        }
    };

    form.addEventListener('submit', (e) => { e.preventDefault(); load(); });
    typeSelect.addEventListener('change', () => { load(); });
    load(); // muat awal
}

/* ============================================================
   3d) LONCENG NOTIFIKASI (topbar)
   ============================================================ */
function initNotifBell() {
    const bell = $('#notif-bell');
    const panel = $('#notif-panel');
    if (!bell || !panel) return;

    const render = (data) => {
        panel.innerHTML = `
            <div class="notif-head">
                <b>Notifikasi</b>
                <button type="button" id="notif-read-all" ${data.unread ? '' : 'disabled'}>Tandai dibaca</button>
            </div>
            ${data.items.length ? data.items.map((n) => `
                <a class="notif-item ${n.unread ? 'unread' : ''}" href="${esc(n.url)}">
                    <span class="notif-icon">${n.icon}</span>
                    <span class="notif-text">${esc(n.title)}<small>${esc(n.time)}</small></span>
                </a>`).join('')
            : '<div class="notif-empty">Tidak ada notifikasi</div>'}
            <a class="notif-foot" href="/notifikasi">Lihat semua →</a>`;
        panel.querySelector('#notif-read-all')?.addEventListener('click', async (e) => {
            e.stopPropagation();
            await api('/notifikasi/baca', 'POST');
            load();
            const badge = $('#notif-badge');
            if (badge) badge.remove();
        });
    };

    const load = async () => {
        try { render(await api('/notifikasi/data')); } catch { /* abaikan */ }
    };

    let t;
    bell.addEventListener('click', (e) => {
        e.stopPropagation();
        panel.hidden = !panel.hidden;
        if (!panel.hidden) load();
    });
    document.addEventListener('click', (e) => {
        if (!panel.hidden && !panel.contains(e.target)) panel.hidden = true;
    });
    panel.addEventListener('click', (e) => e.stopPropagation());
    // auto-refresh ringan tiap 60 detik saat tab aktif
    t = setInterval(() => { if (!document.hidden && !panel.hidden) load(); }, 60000);
    return () => clearInterval(t);
}

/* Tombol "Tandai Semua Dibaca" di halaman notifikasi */
function initNotifPage() {
    const btn = $('#mark-read-btn');
    if (!btn) return;
    btn.addEventListener('click', async () => {
        await api('/notifikasi/baca', 'POST');
        location.reload();
    });
}

/* ============================================================
   3e) RESEP & OBAT (dokter tulis / farmasi serahkan)
   ============================================================ */
function initPrescriptions() {
    const form = $('#rx-form');
    if (form) {
        // Baris obat dinamis (maks 8)
        const wrap = $('#rx-items');
        const add = $('#rx-add');
        const tpl = wrap.querySelector('.rx-item');
        const reIndex = () => {
            wrap.querySelectorAll('.rx-item').forEach((row, i) => {
                row.querySelectorAll('[name]').forEach((el) => {
                    el.name = el.name.replace(/items\[\d+\]/, `items[${i}]`);
                });
            });
        };
        add?.addEventListener('click', () => {
            if (wrap.children.length >= 8) { toast('Maksimal 8 obat per resep', 'err'); return; }
            const clone = tpl.cloneNode(true);
            clone.querySelectorAll('input,select').forEach((el) => { el.value = el.tagName === 'SELECT' ? '' : (el.type === 'number' ? '1' : ''); });
            wrap.appendChild(clone);
            reIndex();
            clone.querySelector('select').focus();
        });
        wrap.addEventListener('click', (e) => {
            const del = e.target.closest('.rx-del');
            if (!del) return;
            const row = del.closest('.rx-item');
            if (wrap.children.length <= 1) { toast('Minimal satu obat', 'err'); return; }
            row.remove();
            reIndex();
        });

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const data = new FormData(form);
            // Susun ulang items agar indeks rapi (sudah via reIndex, aman langsung)
            try {
                const res = await fetch(form.dataset.endpoint, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': data.get('_token'), 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
                    body: data,
                });
                const json = await res.json();
                if (!res.ok) throw new Error(Object.values(json.errors || { e: json.message || 'Gagal menyimpan' })[0]);
                toast(`Resep tersimpan — No. RM ${json.rm}`);
                setTimeout(() => location.reload(), 700);
            } catch (err) {
                toast(err.message, 'err');
            }
        });
    }

    // Tombol penyerahan (farmasi)
    $$('.rx-dispense').forEach((f) => {
        f.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (!window.confirm('Tandai resep diserahkan? Stok obat akan berkurang otomatis.')) return;
            const token = f.querySelector('input[name=_token]').value;
            try {
                const res = await fetch(f.dataset.endpoint, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
                });
                const json = await res.json();
                if (!res.ok) throw new Error(json.message || 'Gagal memproses');
                toast('Resep diserahkan — stok diperbarui');
                setTimeout(() => location.reload(), 700);
            } catch (err) {
                toast(err.message, 'err');
            }
        });
    });
}

/* ============================================================
   BOOT
   ============================================================ */
document.addEventListener('DOMContentLoaded', () => {
    initPasswordPeek();
    initCaptcha();
    animateCountUps();
    initMobileMenu();
    initCrud();
    initSurveys();
    initUsers();
    initPublicSurvey();
    initTerminal();
    initCharts();
    initReports();
    initNotifBell();
    initNotifPage();
    initPrescriptions();
});
