(function () {
    'use strict';

    function initializeFriendsEditor(source) {
        if (source.dataset.editorReady === 'true') return;
        const form = source.closest('form');
        if (!form) return;

        const editor = document.createElement('div');
        editor.className = 'sujian-friends-editor';
        editor.setAttribute('aria-label', '友情链接配置');
        const rows = document.createElement('div');
        rows.className = 'sujian-friends-rows';
        const empty = document.createElement('p');
        empty.className = 'sujian-friends-empty';
        empty.textContent = '暂无友情链接';
        const add = document.createElement('button');
        add.type = 'button';
        add.className = 'btn sujian-friends-add';
        add.textContent = '+ 添加友链';
        const status = document.createElement('span');
        status.className = 'sujian-friends-status';
        status.setAttribute('role', 'status');
        editor.append(rows, empty, add, status);

        const fields = [
            { key: 'name', label: '名字', placeholder: '站点名字', required: true },
            { key: 'avatar', label: '头像地址', placeholder: '留空使用默认头像' },
            { key: 'url', label: '跳转地址', placeholder: 'https://example.com', required: true },
            { key: 'description', label: '简介', placeholder: '站点简介（选填）' }
        ];
        let nextId = 0;
        let dirty = false;

        function normalizeAddress(value) {
            if (!value) return '';
            const url = new URL(value, new URL('../', location.href));
            if (!['http:', 'https:'].includes(url.protocol) || /\s/.test(value) || /^[a-z][a-z0-9+.-]*:(?!\/\/)/i.test(value)) {
                throw new Error('Invalid URL');
            }
            const isAbsolute = /^https?:\/\//i.test(value) || value.startsWith('//');
            return isAbsolute
                ? (url.href === value + '/' ? value : url.href)
                : url.pathname + url.search + url.hash;
        }

        function synchronize() {
            const lines = Array.from(rows.children, function (row) {
                return Array.from(row.querySelectorAll('input'), function (input, index) {
                    const value = input.value.trim();
                    if (index === 1 || index === 2) {
                        try { return normalizeAddress(value); } catch (exception) { return value; }
                    }
                    return value;
                });
            }).filter(function (values) {
                return values.some(Boolean);
            }).map(function (values) {
                return [values[0], values[1], '', values[2], values[3]].join('|');
            }).join('\n');
            if (dirty) source.value = lines;
            empty.hidden = rows.children.length > 0;
            const enabled = form.querySelector('[name="friendsEnabled"]:checked')?.value === '1';
            Array.from(rows.children).forEach(function (row, index) {
                row.querySelector('.sujian-friend-number').textContent = String(index + 1).padStart(2, '0');
                row.querySelector('[data-move="up"]').disabled = !enabled || index === 0;
                row.querySelector('[data-move="down"]').disabled = !enabled || index === rows.children.length - 1;
            });
        }

        function validateRow(row) {
            const inputs = Array.from(row.querySelectorAll('input'));
            const enabled = form.querySelector('[name="friendsEnabled"]:checked')?.value === '1';
            const hasContent = inputs.some(function (input) { return input.value.trim() !== ''; });
            inputs.forEach(function (input, index) {
                const value = input.value.trim();
                let error = '';
                if (!enabled) {
                    input.setCustomValidity('');
                    input.setAttribute('aria-invalid', 'false');
                    return;
                }
                if (hasContent && fields[index].required && value === '') {
                    error = '请填写' + fields[index].label + '。';
                } else if (index < 3 && value.includes('|')) {
                    error = '此项不能包含竖线字符，地址中的竖线请替换为 %7C。';
                } else if (index > 0 && index < 3 && value !== '') {
                    try {
                        normalizeAddress(value);
                    } catch (exception) {
                        error = '请填写有效的 http/https 地址或站内相对路径。';
                    }
                }
                input.setCustomValidity(error);
                input.setAttribute('aria-invalid', error ? 'true' : 'false');
            });
        }

        function appendRow(values, focus) {
            nextId += 1;
            const row = document.createElement('div');
            row.className = 'sujian-friend-row';
            row.setAttribute('role', 'group');
            row.setAttribute('aria-label', '友情链接');
            const number = document.createElement('span');
            number.className = 'sujian-friend-number';
            number.setAttribute('aria-hidden', 'true');
            row.append(number);
            fields.forEach(function (field, index) {
                const wrapper = document.createElement('div');
                wrapper.className = 'sujian-friend-field sujian-friend-' + field.key;
                const title = document.createElement('label');
                title.textContent = field.label + (field.required ? ' *' : '');
                const input = document.createElement('input');
                input.type = 'text';
                input.id = source.id + '-row-' + nextId + '-' + field.key;
                title.htmlFor = input.id;
                input.value = values[index] || '';
                input.placeholder = field.placeholder;
                input.autocomplete = 'off';
                if (index > 0 && index < 3) {
                    input.inputMode = 'url';
                    input.spellcheck = false;
                }
                wrapper.append(title, input);
                row.append(wrapper);
            });
            const actions = document.createElement('div');
            actions.className = 'sujian-friend-actions';
            ['up', 'down'].forEach(function (direction) {
                const move = document.createElement('button');
                move.type = 'button';
                move.className = 'btn sujian-friend-move';
                move.dataset.move = direction;
                move.textContent = direction === 'up' ? '↑' : '↓';
                move.title = direction === 'up' ? '上移友链' : '下移友链';
                move.setAttribute('aria-label', move.title);
                move.addEventListener('click', function () {
                    const neighbor = direction === 'up' ? row.previousElementSibling : row.nextElementSibling;
                    if (move.disabled || !neighbor) return;
                    if (direction === 'up') rows.insertBefore(row, neighbor);
                    else rows.insertBefore(neighbor, row);
                    dirty = true;
                    synchronize();
                    row.querySelector('input').focus();
                    status.textContent = '友链顺序已调整';
                });
                actions.append(move);
            });
            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'btn sujian-friend-remove';
            remove.textContent = '×';
            remove.title = '删除此友链';
            remove.setAttribute('aria-label', '删除此友链');
            remove.addEventListener('click', function () {
                const name = row.querySelector('input').value.trim();
                const target = row.nextElementSibling || row.previousElementSibling;
                row.remove();
                dirty = true;
                synchronize();
                status.textContent = name ? '已删除：' + name : '已删除空白友链';
                (target ? target.querySelector('input') : add).focus();
            });
            actions.append(remove);
            row.append(actions);
            row.addEventListener('input', function () {
                dirty = true;
                validateRow(row);
                synchronize();
            });
            rows.append(row);
            validateRow(row);
            if (focus) {
                dirty = true;
                synchronize();
                status.textContent = '已添加友链';
                row.querySelector('input').focus();
            }
        }

        function loadRows() {
            dirty = false;
            rows.replaceChildren();
            status.textContent = '';
            source.value.split(/\r\n|\r|\n/).forEach(function (line) {
                if (!line.trim()) return;
                const values = line.split('|');
                appendRow([
                    values[0],
                    values[1],
                    (values[3] || '').trim() || values[2],
                    values.slice(4).join('|')
                ], false);
            });
            syncEnabledState();
        }

        function syncEnabledState() {
            const enabled = form.querySelector('[name="friendsEnabled"]:checked')?.value === '1';
            const optionGroup = source.closest('ul');
            if (optionGroup) optionGroup.hidden = !enabled;
            editor.hidden = !enabled;
            Array.from(rows.querySelectorAll('input, button')).forEach(function (input) {
                input.disabled = !enabled;
            });
            Array.from(rows.children).forEach(validateRow);
            synchronize();
            if (!enabled) status.textContent = '友链已关闭，已有数据仍会保留';
        }

        add.addEventListener('click', function () { appendRow([], true); });
        form.addEventListener('submit', synchronize);
        form.addEventListener('formdata', function (event) {
            synchronize();
            event.formData.set(source.name, source.value);
        });
        form.querySelectorAll('[name="friendsEnabled"]').forEach(function (input) {
            input.addEventListener('change', syncEnabledState);
        });
        form.addEventListener('reset', function () { setTimeout(loadRows, 0); });
        loadRows();
        source.insertAdjacentElement('afterend', editor);
        source.hidden = true;
        source.dataset.editorReady = 'true';
        const sourceLabel = form.querySelector('label[for="' + source.id + '"]');
        if (sourceLabel) sourceLabel.removeAttribute('for');
    }

    function initialize() {
        document.querySelectorAll('textarea[data-sujian-friends]').forEach(initializeFriendsEditor);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialize, { once: true });
    } else {
        initialize();
    }
})();