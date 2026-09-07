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
        add.textContent = '+ 添加友联';
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

        function synchronize() {
            source.value = Array.from(rows.children, function (row) {
                return Array.from(row.querySelectorAll('input'), function (input) {
                    return input.value.trim();
                });
            }).filter(function (values) {
                return values.some(Boolean);
            }).map(function (values) {
                return [values[0], values[1], '', values[2], values[3]].join('|');
            }).join('\n');
            empty.hidden = rows.children.length > 0;
            Array.from(rows.children).forEach(function (row, index) {
                row.querySelector('.sujian-friend-number').textContent = String(index + 1).padStart(2, '0');
            });
        }

        function validateRow(row) {
            const inputs = Array.from(row.querySelectorAll('input'));
            const hasContent = inputs.some(function (input) { return input.value.trim() !== ''; });
            inputs.forEach(function (input, index) {
                const value = input.value.trim();
                let error = '';
                if (hasContent && fields[index].required && value === '') {
                    error = '请填写' + fields[index].label + '。';
                } else if (index < 3 && value.includes('|')) {
                    error = '此项不能包含竖线字符，地址中的竖线请替换为 %7C。';
                } else if (index > 0 && index < 3 && value !== '') {
                    try {
                        const url = new URL(value, 'https://example.com/');
                        if (!['http:', 'https:'].includes(url.protocol) || /\s/.test(value) || /^[a-z][a-z0-9+.-]*:(?!\/\/)/i.test(value)) {
                            error = '请填写有效的 http/https 地址或站内相对路径。';
                        }
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
                const label = document.createElement('label');
                label.className = 'sujian-friend-field sujian-friend-' + field.key;
                const title = document.createElement('span');
                title.textContent = field.label + (field.required ? ' *' : '');
                const input = document.createElement('input');
                input.type = 'text';
                input.id = source.id + '-row-' + nextId + '-' + field.key;
                label.htmlFor = input.id;
                input.value = values[index] || '';
                input.placeholder = field.placeholder;
                input.autocomplete = 'off';
                if (index > 0 && index < 3) {
                    input.inputMode = 'url';
                    input.spellcheck = false;
                }
                label.append(title, input);
                row.append(label);
            });
            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'btn sujian-friend-remove';
            remove.textContent = '×';
            remove.title = '删除此友联';
            remove.setAttribute('aria-label', '删除此友联');
            remove.addEventListener('click', function () {
                const name = row.querySelector('input').value.trim();
                const target = row.nextElementSibling || row.previousElementSibling;
                row.remove();
                synchronize();
                status.textContent = name ? '已删除：' + name : '已删除空白友联';
                (target ? target.querySelector('input') : add).focus();
            });
            row.append(remove);
            row.addEventListener('input', function () {
                validateRow(row);
                synchronize();
            });
            rows.append(row);
            validateRow(row);
            if (focus) {
                synchronize();
                status.textContent = '已添加友联';
                row.querySelector('input').focus();
            }
        }

        function loadRows() {
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
            empty.hidden = rows.children.length > 0;
            Array.from(rows.children).forEach(function (row, index) {
                row.querySelector('.sujian-friend-number').textContent = String(index + 1).padStart(2, '0');
            });
        }

        add.addEventListener('click', function () { appendRow([], true); });
        form.addEventListener('submit', synchronize);
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