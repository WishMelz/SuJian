(function () {
    'use strict';

    function button(text, title, action) {
        const control = document.createElement('button');
        control.type = 'button';
        control.className = 'btn sujian-setting-button';
        control.textContent = text;
        control.title = title;
        control.setAttribute('aria-label', title);
        control.addEventListener('click', action);
        return control;
    }

    function field(row, title, control, id) {
        const wrapper = document.createElement('div');
        wrapper.className = 'sujian-setting-field';
        const label = document.createElement('label');
        label.textContent = title;
        control.id = id;
        label.htmlFor = id;
        wrapper.append(label, control);
        row.append(wrapper);
        return control;
    }

    function address(value) {
        const trimmed = value.trim();
        if (!trimmed || /[\s|]/.test(trimmed) || /^[a-z][a-z0-9+.-]*:(?!\/\/)/i.test(trimmed)) {
            throw new Error('请填写有效的 http/https 地址或站内路径。');
        }
        const url = new URL(trimmed, new URL('../', location.href));
        if (!['http:', 'https:'].includes(url.protocol)) {
            throw new Error('地址仅支持 http/https。');
        }
        return trimmed.startsWith('/') && !trimmed.startsWith('//')
            ? url.pathname + url.search + url.hash : url.href === trimmed + '/' ? trimmed : url.href;
    }

    function initializeNavigation(source) {
        if (source.dataset.editorReady === 'true') return;
        const form = source.form;
        const editor = document.createElement('div');
        editor.className = 'sujian-navigation-editor';
        editor.setAttribute('aria-label', '导航菜单配置');
        const rows = document.createElement('div');
        const empty = document.createElement('p');
        empty.className = 'description';
        empty.textContent = '暂无导航';
        const status = document.createElement('span');
        status.className = 'sujian-setting-status';
        status.setAttribute('role', 'status');
        let nextId = 0;
        let dirty = false;

        function synchronize() {
            const lines = [];
            Array.from(rows.children).forEach(function (row, index) {
                const name = row.querySelector('[data-field="name"]');
                const url = row.querySelector('[data-field="url"]');
                const target = row.querySelector('select');
                const hasContent = name.value.trim() || url.value.trim();
                let normalized = url.value.trim();
                let error = '';
                if (hasContent) {
                    try { normalized = address(url.value); } catch (exception) { error = exception.message; }
                }
                name.setCustomValidity(hasContent && !name.value.trim() ? '请填写名称。' : /[|\r\n]/.test(name.value) ? '名称不能包含竖线或换行。' : '');
                url.setCustomValidity(error);
                [name, url].forEach(function (input) { input.setAttribute('aria-invalid', input.validationMessage ? 'true' : 'false'); });
                row.querySelector('[data-move="up"]').disabled = index === 0;
                row.querySelector('[data-move="down"]').disabled = index === rows.children.length - 1;
                if (hasContent) lines.push([name.value.trim(), normalized, target.value].join('|'));
            });
            empty.hidden = rows.children.length > 0;
            if (dirty) source.value = lines.join('\n');
        }

        function appendRow(values, focus) {
            const row = document.createElement('div');
            row.className = 'sujian-navigation-row';
            row.setAttribute('role', 'group');
            row.setAttribute('aria-label', '导航项');
            const id = source.id + '-item-' + (++nextId);
            ['name', 'url'].forEach(function (key, index) {
                const input = document.createElement('input');
                input.type = 'text';
                input.dataset.field = key;
                input.value = values[index] || '';
                input.placeholder = index ? '/about.html' : '导航名称';
                if (index) input.inputMode = 'url';
                field(row, index ? '地址' : '名称', input, id + '-' + key);
            });
            const target = document.createElement('select');
            target.add(new Option('当前标签页', 'self'));
            target.add(new Option('新标签页', 'blank'));
            target.value = ['blank', '_blank', '新标签', '新窗口'].includes((values[2] || '').trim().toLowerCase()) ? 'blank' : 'self';
            field(row, '打开方式', target, id + '-target');
            const actions = document.createElement('div');
            actions.className = 'sujian-setting-actions';
            ['up', 'down'].forEach(function (direction) {
                const move = button(direction === 'up' ? '↑' : '↓', direction === 'up' ? '上移导航' : '下移导航', function () {
                    const neighbor = direction === 'up' ? row.previousElementSibling : row.nextElementSibling;
                    if (!neighbor) return;
                    if (direction === 'up') rows.insertBefore(row, neighbor);
                    else rows.insertBefore(neighbor, row);
                    dirty = true;
                    synchronize();
                    row.querySelector('input').focus();
                    status.textContent = '导航顺序已调整';
                });
                move.dataset.move = direction;
                actions.append(move);
            });
            actions.append(button('×', '删除导航', function () {
                const neighbor = row.nextElementSibling || row.previousElementSibling;
                row.remove();
                dirty = true;
                synchronize();
                (neighbor ? neighbor.querySelector('input') : add).focus();
                status.textContent = '导航已删除';
            }));
            row.append(actions);
            row.addEventListener('input', function () { dirty = true; synchronize(); });
            row.addEventListener('change', function () { dirty = true; synchronize(); });
            rows.append(row);
            if (focus) {
                dirty = true;
                synchronize();
                row.querySelector('input').focus();
            }
        }

        function load() {
            dirty = false;
            rows.replaceChildren();
            source.value.split(/\r\n|\r|\n/).filter(function (line) { return line.trim(); }).forEach(function (line) {
                appendRow(line.split('|').map(function (value) { return value.trim(); }), false);
            });
            synchronize();
        }

        const add = button('+ 添加导航', '添加导航', function () { appendRow([], true); });
        editor.append(rows, empty, add, status);
        source.after(editor);
        load();
        source.hidden = true;
        source.dataset.editorReady = 'true';
        const label = form.querySelector('label[for="' + source.id + '"]');
        if (label) label.removeAttribute('for');
        const description = source.closest('li').querySelector(':scope > .description');
        if (description) description.hidden = true;
        form.addEventListener('submit', synchronize);
        form.addEventListener('formdata', function (event) { synchronize(); event.formData.set(source.name, source.value); });
        form.addEventListener('reset', function () { setTimeout(load, 0); });
    }

    function initializeGradient(form) {
        const source = form.elements.bannerGradient;
        const mode = form.elements.bannerMode;
        const fallback = '135deg, #7c3aed 0%, #4f46e5 48%, #0ea5e9 100%';
        const editor = document.createElement('div');
        editor.className = 'sujian-gradient-editor';
        const preview = document.createElement('div');
        preview.className = 'sujian-gradient-preview';
        preview.setAttribute('role', 'img');
        preview.setAttribute('aria-label', '渐变预览');
        const controls = document.createElement('div');
        controls.className = 'sujian-gradient-controls';
        const angle = document.createElement('input');
        angle.type = 'number';
        angle.min = '-360';
        angle.max = '360';
        angle.step = 'any';
        angle.required = true;
        field(controls, '角度 (°)', angle, 'sujian-gradient-angle');
        const stops = document.createElement('div');
        stops.className = 'sujian-gradient-stops';
        const advanced = document.createElement('details');
        advanced.className = 'sujian-setting-advanced';
        const summary = document.createElement('summary');
        summary.textContent = '高级 CSS';
        advanced.append(summary);
        source.before(editor);
        advanced.append(source);
        const description = editor.closest('li').querySelector('.description');
        if (description) description.hidden = true;
        let nextId = 0;
        let visual = false;

        function validate() {
            const value = source.value.trim();
            const valid = !value || (/^[#%,.()a-zA-Z0-9\s-]+$/.test(value) && CSS.supports('background-image', 'linear-gradient(' + value + ')'));
            source.setCustomValidity(mode.value === 'gradient' && !valid ? '请输入有效的渐变颜色。' : '');
            source.setAttribute('aria-invalid', source.validationMessage ? 'true' : 'false');
            if (source.validationMessage) advanced.open = true;
            preview.style.backgroundImage = 'linear-gradient(' + (valid && value ? value : fallback) + ')';
            Array.from(controls.querySelectorAll('input, button')).forEach(function (input) {
                input.disabled = mode.value !== 'gradient' || !visual;
            });
            Array.from(stops.children).forEach(function (row) {
                row.querySelector('button').disabled = mode.value !== 'gradient' || stops.children.length <= 2;
            });
        }

        function synchronize() {
            const values = Array.from(stops.children).map(function (row) {
                return { color: row.querySelector('[type="color"]').value, position: row.querySelector('[type="number"]').value };
            });
            values.sort(function (first, second) { return Number(first.position) - Number(second.position); });
            source.value = angle.value + 'deg, ' + values.map(function (stop) { return stop.color + ' ' + stop.position + '%'; }).join(', ');
            validate();
        }

        function appendStop(color, position) {
            const row = document.createElement('div');
            row.className = 'sujian-gradient-stop';
            const id = 'sujian-gradient-stop-' + (++nextId);
            const swatch = document.createElement('input');
            swatch.type = 'color';
            swatch.value = color;
            field(row, '颜色', swatch, id + '-color');
            const offset = document.createElement('input');
            offset.type = 'number';
            offset.min = '0';
            offset.max = '100';
            offset.step = 'any';
            offset.required = true;
            offset.value = position;
            field(row, '位置 (%)', offset, id + '-position');
            row.append(button('×', '删除色标', function () {
                if (stops.children.length <= 2) return;
                const neighbor = row.nextElementSibling || row.previousElementSibling;
                row.remove();
                synchronize();
                neighbor.querySelector('input').focus();
            }));
            row.addEventListener('input', synchronize);
            stops.append(row);
        }

        function load() {
            const value = source.value.trim() || fallback;
            const parts = value.split(',').map(function (part) { return part.trim(); });
            const direction = /^(-?\d+(?:\.\d+)?)deg$/.exec(parts.shift());
            const parsed = parts.map(function (part) { return /^(#[a-f\d]{6}|#[a-f\d]{3})\s+(\d+(?:\.\d+)?)%$/i.exec(part); });
            visual = !!direction && Math.abs(Number(direction[1])) <= 360 && parsed.length >= 2
                && parsed.every(function (stop) { return stop && Number(stop[2]) <= 100; });
            controls.hidden = !visual;
            replace.hidden = visual;
            stops.replaceChildren();
            if (visual) {
                angle.value = direction[1];
                parsed.forEach(function (stop) {
                    const color = stop[1].length === 4 ? '#' + stop[1].slice(1).split('').map(function (digit) { return digit + digit; }).join('') : stop[1];
                    appendStop(color, stop[2]);
                });
            } else {
                advanced.open = true;
            }
            validate();
        }

        const add = button('+ 添加颜色', '添加颜色', function () {
            appendStop('#ffffff', '100');
            synchronize();
            stops.lastElementChild.querySelector('input').focus();
        });
        const replace = button('替换为默认渐变', '替换为默认渐变', function () { source.value = fallback; load(); });
        controls.append(stops, add);
        editor.append(preview, controls, replace, advanced);
        angle.addEventListener('input', synchronize);
        source.addEventListener('input', load);
        mode.addEventListener('change', validate);
        form.addEventListener('reset', function () { setTimeout(load, 0); });
        form.addEventListener('submit', validate);
        load();
    }

    function organize(form) {
        form.classList.add('sujian-settings');
        const groups = [
            ['basic', '基础外观', ['headerLogo', 'accent', 'articleLinkTarget']],
            ['navigation', '导航菜单', ['navigation']],
            ['banner', '首页横幅', ['bannerMode', 'bannerTitle', 'bannerDescription', 'bannerImage', 'bannerGradient']],
            ['friends', '友情链接', ['friendsEnabled', 'friendsData']],
            ['comments', '评论', ['commentsEnabled']],
            ['seo', '搜索与分享', ['seoSiteDescription', 'seoDefaultImage', 'seoLogo']],
            ['footer', '页脚', ['footerText']]
        ];
        const anchor = form.elements.headerLogo.closest('ul');
        const contents = document.createElement('nav');
        contents.className = 'sujian-settings-nav';
        contents.setAttribute('aria-label', '主题设置分组');
        anchor.before(contents);
        let previous = contents;
        groups.forEach(function (group) {
            const section = document.createElement('section');
            section.className = 'sujian-settings-section';
            section.id = 'sujian-settings-' + group[0];
            const heading = document.createElement('h3');
            heading.id = section.id + '-title';
            heading.textContent = group[1];
            section.setAttribute('aria-labelledby', heading.id);
            section.append(heading);
            const link = document.createElement('a');
            link.href = '#' + section.id;
            link.textContent = group[1];
            contents.append(link);
            previous.after(section);
            group[2].forEach(function (name) {
                const input = form.querySelector('[name="' + name + '"]');
                section.append(input.closest('ul'));
            });
            previous = section;
        });
        const mode = form.elements.bannerMode;
        function toggleBanner() {
            form.elements.bannerImage.closest('ul').hidden = mode.value !== 'image';
            form.elements.bannerGradient.closest('ul').hidden = mode.value !== 'gradient';
        }
        mode.addEventListener('change', toggleBanner);
        form.addEventListener('reset', function () { setTimeout(toggleBanner, 0); });
        toggleBanner();

        ['headerLogo', 'seoDefaultImage', 'seoLogo', 'bannerImage'].forEach(function (name) {
            const image = form.elements[name];
            function validateImage() {
                let error = '';
                if ((name !== 'bannerImage' || mode.value === 'image') && image.value.trim()) {
                    try { address(image.value); } catch (exception) { error = exception.message; }
                }
                image.setCustomValidity(error);
                image.setAttribute('aria-invalid', error ? 'true' : 'false');
            }
            function normalizeImage() {
                if (!image.value.trim()) {
                    image.value = '';
                    return;
                }
                try { image.value = address(image.value); } catch (exception) { return; }
            }
            image.addEventListener('input', validateImage);
            image.addEventListener('change', function () { normalizeImage(); validateImage(); });
            if (name === 'bannerImage') mode.addEventListener('change', validateImage);
            form.addEventListener('submit', normalizeImage);
            form.addEventListener('formdata', function (event) {
                normalizeImage();
                event.formData.set(image.name, image.value);
            });
            form.addEventListener('reset', function () { setTimeout(validateImage, 0); });
            validateImage();
        });

        const footer = form.elements.footerText;
        const label = form.querySelector('label[for="' + footer.id + '"]');
        if (label) label.textContent = '页脚内容';
        const toolbar = document.createElement('div');
        toolbar.className = 'sujian-footer-tokens';
        const tokens = document.createElement('select');
        tokens.setAttribute('aria-label', '页脚变量');
        [['year', '当前年份'], ['siteTitle', '站点名称'], ['siteUrl', '站点地址'], ['description', '站点描述'], ['themeName', '主题名称'], ['themeNameEn', '主题英文名称'], ['themeNameZh', '主题中文名称'], ['themeUrl', '主题地址']].forEach(function (item) {
            tokens.add(new Option(item[1], item[0]));
        });
        toolbar.append(tokens, button('+ 插入变量', '插入页脚变量', function () {
            footer.setRangeText('{' + tokens.value + '}', footer.selectionStart, footer.selectionEnd, 'end');
            footer.dispatchEvent(new Event('input', { bubbles: true }));
            footer.focus();
        }));
        footer.before(toolbar);
        const description = footer.closest('li').querySelector('.description');
        if (description) description.textContent = '支持纯文本或 HTML。';
    }

    function initialize() {
        document.querySelectorAll('textarea[data-sujian-navigation]').forEach(function (source) {
            if (source.dataset.editorReady === 'true') return;
            initializeNavigation(source);
            initializeGradient(source.form);
            organize(source.form);
        });
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initialize, { once: true });
    else initialize();
})();