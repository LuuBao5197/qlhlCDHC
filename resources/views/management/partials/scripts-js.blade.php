
            const tabsEl = document.getElementById('resourceTabs');
            const resourceBadgeEl = document.getElementById('resourceBadge');
            const tableHeadEl = document.getElementById('tableHead');
            const tableBodyEl = document.getElementById('tableBody');
            const pageSummaryEl = document.getElementById('pageSummary');
            const pageNumbersEl = document.getElementById('pageNumbers');
            const filterBarEl = document.getElementById('filterBar');
            const alertBoxEl = document.getElementById('alertBox');
            const editorSectionEl = document.getElementById('editorSection');
            const editorTitleEl = document.getElementById('editorTitle');
            const formFieldsEl = document.getElementById('formFields');
            const searchInputEl = document.getElementById('searchInput');
            const importBtnEl = document.getElementById('importBtn');
            const importSectionEl = document.getElementById('importSection');
            const importFormEl = document.getElementById('importForm');
            const importTitleEl = document.getElementById('importTitle');
            const importDescEl = document.getElementById('importDesc');
            const importTemplateLinkEl = document.getElementById('importTemplateLink');
            const importHintEl = document.getElementById('importHint');
            const importClassFieldEl = document.getElementById('importClassField');
            const importClassIdEl = document.getElementById('importClassId');
            const submitImportBtnEl = document.getElementById('submitImportBtn');

            function escapeHtml(value) {
                return String(value ?? '')
                    .replaceAll('&', '&amp;')
                    .replaceAll('<', '&lt;')
                    .replaceAll('>', '&gt;')
                    .replaceAll('"', '&quot;')
                    .replaceAll("'", '&#039;');
            }

            function getValue(obj, path) {
                return path.split('.').reduce((acc, key) => (acc == null ? null : acc[key]), obj);
            }

            function buildUrl(endpoint, params) {
                const url = new URL(endpoint, window.location.origin);
                Object.entries(params || {}).forEach(([key, value]) => {
                    if (value !== null && value !== undefined && value !== '') {
                        url.searchParams.set(key, value);
                    }
                });
                return url.toString();
            }

            async function request(url, options = {}) {
                const method = (options.method || 'GET').toUpperCase();
                const isFormData = options.body instanceof FormData;
                const headers = {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    ...(options.headers || {}),
                };

                if (method !== 'GET') {
                    headers['X-CSRF-TOKEN'] = csrfToken;
                    if (!isFormData) {
                        headers['Content-Type'] = 'application/json';
                    }
                }

                const response = await fetch(url, {
                    credentials: 'same-origin',
                    ...options,
                    method,
                    headers,
                });

                const text = await response.text();
                let payload = null;
                if (text) {
                    try {
                        payload = JSON.parse(text);
                    } catch {
                        payload = {
                            message: text
                        };
                    }
                }

                if (!response.ok) {
                    const error = new Error(payload?.message || 'Yêu cầu thất bại');
                    error.status = response.status;
                    error.payload = payload;
                    throw error;
                }

                return payload;
            }

            function showAlert(type, message) {
                alertBoxEl.className = `alert alert-${type}`;
                alertBoxEl.innerHTML = message;
                alertBoxEl.style.display = 'block';
            }

            function hideAlert() {
                alertBoxEl.style.display = 'none';
                alertBoxEl.innerHTML = '';
            }

            function renderTabs() {
                tabsEl.innerHTML = Object.entries(resources)
                    .map(([key, resource]) => `
                        <button type="button" class="resource-tab ${key === state.currentResource ? 'active' : ''}" data-resource="${key}">
                            ${resource.label}
                        </button>
                    `)
                    .join('');
            }

            function updateResourceActions() {
                const resource = resources[state.currentResource];
                importBtnEl.style.display = resource.import ? '' : 'none';
            }

            function renderFilters() {
                const resource = resources[state.currentResource];
                const filters = resource.filters || [];

                if (filters.length === 0) {
                    filterBarEl.innerHTML = '';
                    filterBarEl.style.display = 'none';
                    return;
                }

                filterBarEl.style.display = '';
                filterBarEl.innerHTML = filters.map((filter) => {
                    const options = selectOptionsFor(filter);
                    const currentValue = state.filters[filter.key] ?? '';
                    return `
                        <div class="filter-field">
                            <label class="d-block">${escapeHtml(filter.label)}</label>
                            <select class="form-control form-control-sm" data-filter-key="${escapeHtml(filter.key)}">
                                <option value="">-- Tất cả --</option>
                                ${options.map((option) => {
                                    const selected = String(option.value) === String(currentValue) ? 'selected' : '';
                                    return `<option value="${escapeHtml(option.value)}" ${selected}>${escapeHtml(option.label)}</option>`;
                                }).join('')}
                            </select>
                        </div>
                    `;
                }).join('') + `
                    <button type="button" id="filterClearBtn" class="btn btn-outline-secondary btn-sm filter-clear">Bỏ lọc</button>
                `;
            }

            function renderTable() {
                const resource = resources[state.currentResource];
                resourceBadgeEl.textContent = resource.label;

                // 1. Tối ưu Table Head bằng cách gán trực tiếp phần tử cụ thể
                tableHeadEl.innerHTML = `
                                <tr>
                                    <th style="width:60px">#</th>
                                    ${resource.columns.map((column) => `<th>${escapeHtml(column.label)}</th>`).join('')}
                                    <th style="width:160px">Tác vụ</th>
                                </tr>`;

                // Clear body nhanh chóng
                tableBodyEl.textContent = '';

                if (state.rows.length === 0) {
                    tableBodyEl.innerHTML =
                        `<tr><td colspan="${resource.columns.length + 2}" class="text-center text-muted py-4">Không có dữ liệu</td></tr>`;
                    return;
                }

                // 2. Tạo một Fragment trong bộ nhớ (Memory) làm bộ đệm
                const fragment = document.createDocumentFragment();

                state.rows.forEach((row, index) => {
                    const tr = document.createElement('tr');

                    // Thêm cột Số thứ tự
                    const tdIndex = document.createElement('td');
                    tdIndex.textContent = (state.page - 1) * state.perPage + index + 1;
                    tr.appendChild(tdIndex);

                    // Thêm các cột dữ liệu dựa trên cấu hình config
                    resource.columns.forEach((column) => {
                        const td = document.createElement('td');
                        let value = getValue(row, column.key);
                        if (column.type === 'date' && value) {
                            value = formatDate(value);
                        }
                        td.textContent = (value === null || value === undefined || value === '') ?
                            '-' : value;
                        if (column.wrap) td.classList.add('wrap');
                        tr.appendChild(td);
                    });

                    // Thêm cột nút Hành động (Sửa, Xóa)
                    const tdActions = document.createElement('td');
                    tdActions.innerHTML = `
                        <button class="btn btn-sm btn-info mr-1" data-action="edit" data-id="${row.id}">Sửa</button>
                        <button class="btn btn-sm btn-danger" data-action="delete" data-id="${row.id}">Xóa</button>
                    `;
                    tr.appendChild(tdActions);

                    // Đẩy dòng tr vào bộ đệm Fragment chứ chưa đẩy lên màn hình
                    fragment.appendChild(tr);
                });

                // 3. Đổ toàn bộ bộ đệm vào DOM thật (Chỉ kích hoạt Repaint 1 lần duy nhất)
                tableBodyEl.appendChild(fragment);
            }

            function buildPageItem(label, page, options = {}) {
                const { active = false, disabled = false, isEllipsis = false } = options;
                const classes = ['page-item'];
                if (active) classes.push('active');
                if (disabled) classes.push('disabled');

                if (isEllipsis) {
                    return `<li class="${classes.join(' ')}"><span class="page-link">${label}</span></li>`;
                }

                return `<li class="${classes.join(' ')}"><a href="#" class="page-link" data-page="${page}">${label}</a></li>`;
            }

            function pageNumberSequence(current, last) {
                const pages = new Set([1, last, current, current - 1, current + 1]);
                return Array.from(pages)
                    .filter((page) => page >= 1 && page <= last)
                    .sort((a, b) => a - b);
            }

            function updatePagination() {
                pageSummaryEl.textContent = `Trang ${state.page}/${state.lastPage} - Tổng ${state.total} bản ghi`;

                const items = [];
                items.push(buildPageItem('&laquo;', state.page - 1, {
                    disabled: state.page <= 1
                }));

                const sequence = pageNumberSequence(state.page, state.lastPage);
                let previousPage = 0;
                for (const page of sequence) {
                    if (previousPage && page - previousPage > 1) {
                        items.push(buildPageItem('...', null, {
                            isEllipsis: true
                        }));
                    }
                    items.push(buildPageItem(String(page), page, {
                        active: page === state.page
                    }));
                    previousPage = page;
                }

                items.push(buildPageItem('&raquo;', state.page + 1, {
                    disabled: state.page >= state.lastPage
                }));

                pageNumbersEl.innerHTML = items.join('');
            }

            function renderLoading() {
                const resource = resources[state.currentResource];
                tableBodyEl.innerHTML = `
                    <tr>
                        <td colspan="${resource.columns.length + 2}" class="text-center text-muted py-4">Dang tai du lieu...</td>
                    </tr>
                `;
            }

            async function loadLookups() {
                const lookupEntries = [
                    ['trainingPrograms', resources.trainingPrograms.endpoint],
                    ['trainingBatches', resources.trainingBatches.endpoint],
                    ['departments', resources.departments.endpoint],
                    ['trainingClasses', resources.trainingClasses.endpoint],
                    ['rooms', resources.rooms.endpoint],
                    ['subjects', resources.subjects.endpoint],
                ];

                for (const [key, endpoint] of lookupEntries) {
                    try {
                        const payload = await request(buildUrl(endpoint, {
                            per_page: 200
                        }));
                        state.lookups[key] = payload?.data || [];
                    } catch {
                        state.lookups[key] = [];
                    }
                }
            }

            function selectOptionsFor(field) {
                if (Array.isArray(field.options)) {
                    return field.options;
                }

                if (!field.lookup) {
                    return [];
                }

                return (state.lookups[field.lookup] || []).map((item) => ({
                    value: item.id,
                    label: (lookupLabels[field.lookup] ? lookupLabels[field.lookup](item) : item
                        .name) || item.id,
                }));
            }

            function formatDate(value) {
                const isoDatePart = String(value).substring(0, 10);
                const [year, month, day] = isoDatePart.split('-');
                if (!year || !month || !day) return value;
                return `${day}/${month}/${year}`;
            }

            function fieldValue(row, field) {
                let value = row?.[field.key] ?? '';
                if (field.type === 'date' && value) {
                    value = String(value).substring(0, 10);
                }
                return value ?? '';
            }

            function renderEditor(row = null) {
                const resource = resources[state.currentResource];
                const isEditing = !!row;
                state.editingId = isEditing ? row.id : null;

                editorTitleEl.textContent = `${isEditing ? 'Chỉnh sửa' : 'Thêm mới'} - ${resource.label}`;

                formFieldsEl.innerHTML = resource.fields
                    .map((field) => {
                        const requiredBadge = field.required ? ' <span class="text-danger">*</span>' : '';
                        const value = fieldValue(row, field);

                        if (field.type === 'textarea') {
                            return `
                                <div class="col-12">
                                    <div class="form-group">
                                        <label>${escapeHtml(field.label)}${requiredBadge}</label>
                                        <textarea class="form-control" name="${field.key}" rows="3">${escapeHtml(value)}</textarea>
                                    </div>
                                </div>
                            `;
                        }

                        if (field.type === 'select') {
                            const options = selectOptionsFor(field);
                            return `
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>${escapeHtml(field.label)}${requiredBadge}</label>
                                        <select class="form-control" name="${field.key}">
                                            <option value="">-- Chọn --</option>
                                            ${options
                                                .map((option) => {
                                                    const selected = String(option.value) === String(value) ? 'selected' : '';
                                                    return `<option value="${escapeHtml(option.value)}" ${selected}>${escapeHtml(option.label)}</option>`;
                                                })
                                                .join('')}
                                        </select>
                                    </div>
                                </div>
                            `;
                        }

                        if (field.type === 'checkbox') {
                            const checked = value === true || value === 1 || value === '1' ? 'checked' : '';
                            return `
                                <div class="col-md-6">
                                    <div class="form-group form-check mt-4">
                                        <input type="checkbox" class="form-check-input" id="field-${field.key}" name="${field.key}" ${checked}>
                                        <label class="form-check-label" for="field-${field.key}">${escapeHtml(field.label)}</label>
                                    </div>
                                </div>
                            `;
                        }

                        const inputType = field.type || 'text';
                        return `
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>${escapeHtml(field.label)}${requiredBadge}</label>
                                    <input type="${escapeHtml(inputType)}" class="form-control" name="${field.key}" value="${escapeHtml(value)}">
                                </div>
                            </div>
                        `;
                    })
                    .join('');

                editorSectionEl.style.display = 'block';
                editorSectionEl.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }

            function closeEditor() {
                state.editingId = null;
                formFieldsEl.innerHTML = '';
                editorSectionEl.style.display = 'none';
            }

            function renderImportClassOptions() {
                const options = (state.lookups.trainingClasses || []).map((item) => ({
                    value: item.id,
                    label: lookupLabels.trainingClasses(item) || item.id,
                }));

                importClassIdEl.innerHTML = `
                    <option value="">-- Chọn lớp --</option>
                    ${options.map((option) =>
                        `<option value="${escapeHtml(option.value)}">${escapeHtml(option.label)}</option>`
                    ).join('')}
                `;
            }

            function openImport() {
                const resource = resources[state.currentResource];
                const importConfig = resource.import;
                if (!importConfig) {
                    return;
                }

                closeEditor();
                hideAlert();

                importTitleEl.textContent = importConfig.title || `Nhập dữ liệu từ CSV - ${resource.label}`;
                importDescEl.textContent = importConfig.description || 'Tải file CSV tối đa 5 MB.';
                importHintEl.textContent = importConfig.hint || '';
                importTemplateLinkEl.href = importConfig.templateUrl || '#';

                if (importConfig.needsClass) {
                    renderImportClassOptions();
                    importClassFieldEl.style.display = '';
                    importClassIdEl.required = true;
                } else {
                    importClassFieldEl.style.display = 'none';
                    importClassIdEl.required = false;
                    importClassIdEl.value = '';
                }

                importSectionEl.style.display = 'block';
                importSectionEl.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }

            function closeImport() {
                importFormEl.reset();
                importSectionEl.style.display = 'none';
                submitImportBtnEl.disabled = false;
                submitImportBtnEl.textContent = 'Nhập dữ liệu';
            }

            function collectFormData() {
                const resource = resources[state.currentResource];
                const formData = {};

                for (const field of resource.fields) {
                    const input = formFieldsEl.querySelector(`[name="${field.key}"]`);
                    if (!input) {
                        continue;
                    }

                    let value = input.value;

                    if (field.type === 'checkbox') {
                        value = input.checked;
                    } else if (field.type === 'number') {
                        value = value === '' ? null : Number(value);
                    } else if (field.type === 'select') {
                        value = value === '' ? null : value;
                        if (value !== null && /^\d+$/.test(value)) {
                            value = Number(value);
                        }
                    } else {
                        value = value === '' ? null : value;
                    }

                    formData[field.key] = value;
                }

                return formData;
            }

            async function loadRows() {
                hideAlert();
                renderLoading();

                const resource = resources[state.currentResource];
                const payload = await request(buildUrl(resource.endpoint, {
                    page: state.page,
                    per_page: state.perPage,
                    q: state.q,
                    ...state.filters,
                }));

                state.rows = payload?.data || [];
                state.page = payload?.current_page || 1;
                state.lastPage = payload?.last_page || 1;
                state.perPage = payload?.per_page || 10;
                state.total = payload?.total || state.rows.length;

                renderTable();
                updatePagination();
            }

            async function switchResource(resourceKey) {
                state.currentResource = resourceKey;
                state.page = 1;
                state.q = '';
                state.filters = {};
                searchInputEl.value = '';
                closeEditor();
                closeImport();
                renderTabs();
                updateResourceActions();
                renderFilters();
                await loadRows();
            }

            function validationMessage(error) {
                if (error.status !== 422 || !error.payload?.errors) {
                    return null;
                }

                return Object.entries(error.payload.errors)
                    .flatMap(([field, messages]) => messages.map((message) =>
                        `${escapeHtml(field)}: ${escapeHtml(message)}`))
                    .join('<br>');
            }

            async function saveRecord(event) {
                event.preventDefault();
                hideAlert();

                const resource = resources[state.currentResource];
                const data = collectFormData();
                const isEditing = state.editingId !== null;
                const url = isEditing ? `${resource.endpoint}/${state.editingId}` : resource.endpoint;
                const method = isEditing ? 'PUT' : 'POST';

                try {
                    await request(url, {
                        method,
                        body: JSON.stringify(data),
                    });

                    await loadLookups();
                    await loadRows();
                    closeEditor();
                    showAlert('success', `${isEditing ? 'Cập nhật' : 'Tạo mới'} thành công.`);
                } catch (error) {
                    const message = validationMessage(error);
                    if (message) {
                        showAlert('danger', message);
                        return;
                    }

                    showAlert('danger', escapeHtml(error.message || 'Không thể lưu dữ liệu.'));
                }
            }

            async function submitImport(event) {
                event.preventDefault();
                hideAlert();

                const resource = resources[state.currentResource];
                const importConfig = resource.import;
                if (!importConfig) {
                    return;
                }

                const formData = new FormData(importFormEl);
                submitImportBtnEl.disabled = true;
                submitImportBtnEl.textContent = 'Đang nhập...';

                try {
                    const payload = await request(importConfig.endpoint, {
                        method: 'POST',
                        body: formData,
                    });

                    state.page = 1;
                    await loadRows();
                    closeImport();
                    showAlert('success',
                        `Đã nhập thành công ${payload?.imported_count || 0} bản ghi (${resource.label}).`);
                } catch (error) {
                    const message = validationMessage(error);
                    showAlert('danger', message || escapeHtml(error.message || 'Không thể nhập file CSV.'));
                } finally {
                    submitImportBtnEl.disabled = false;
                    submitImportBtnEl.textContent = 'Nhập dữ liệu';
                }
            }

            async function editRecord(id) {
                const row = state.rows.find((item) => String(item.id) === String(id));
                if (!row) {
                    showAlert('warning', 'Không tìm thấy bản ghi để chỉnh sửa.');
                    return;
                }

                renderEditor(row);
            }

            async function deleteRecord(id) {
                const resource = resources[state.currentResource];
                if (!confirm('Bạn có chắc chắn muốn xóa bản ghi này?')) {
                    return;
                }

                hideAlert();

                try {
                    await request(`${resource.endpoint}/${id}`, {
                        method: 'DELETE'
                    });

                    if (state.rows.length === 1 && state.page > 1) {
                        state.page -= 1;
                    }

                    await loadRows();
                    await loadLookups();
                    showAlert('success', 'Xóa thành công.');
                } catch (error) {
                    showAlert('danger', escapeHtml(error.message || 'Không thể xóa bản ghi.'));
                }
            }

            tabsEl.addEventListener('click', async function(event) {
                const button = event.target.closest('[data-resource]');
                if (!button) {
                    return;
                }
                await switchResource(button.dataset.resource);
            });

            tableBodyEl.addEventListener('click', async function(event) {
                const button = event.target.closest('[data-action]');
                if (!button) {
                    return;
                }

                const action = button.dataset.action;
                const id = button.dataset.id;

                if (action === 'edit') {
                    await editRecord(id);
                }

                if (action === 'delete') {
                    await deleteRecord(id);
                }
            });

            document.getElementById('addBtn').addEventListener('click', function() {
                closeImport();
                renderEditor(null);
            });

            importBtnEl.addEventListener('click', function() {
                openImport();
            });

            document.getElementById('cancelBtn').addEventListener('click', function() {
                closeEditor();
            });

            document.getElementById('cancelImportBtn').addEventListener('click', function() {
                closeImport();
            });

            document.getElementById('reloadBtn').addEventListener('click', async function() {
                await loadLookups();
                await loadRows();
            });

            document.getElementById('searchBtn').addEventListener('click', async function() {
                state.q = searchInputEl.value.trim();
                state.page = 1;
                await loadRows();
            });

            searchInputEl.addEventListener('keydown', async function(event) {
                if (event.key !== 'Enter') {
                    return;
                }

                event.preventDefault();
                state.q = searchInputEl.value.trim();
                state.page = 1;
                await loadRows();
            });

            pageNumbersEl.addEventListener('click', async function(event) {
                const link = event.target.closest('[data-page]');
                if (!link || link.closest('.disabled')) {
                    return;
                }

                event.preventDefault();
                const page = Number(link.dataset.page);
                if (!page || page === state.page || page < 1 || page > state.lastPage) {
                    return;
                }

                state.page = page;
                await loadRows();
            });

            filterBarEl.addEventListener('change', async function(event) {
                const select = event.target.closest('[data-filter-key]');
                if (!select) {
                    return;
                }

                const key = select.dataset.filterKey;
                if (select.value === '') {
                    delete state.filters[key];
                } else {
                    state.filters[key] = select.value;
                }

                state.page = 1;
                await loadRows();
            });

            filterBarEl.addEventListener('click', async function(event) {
                const button = event.target.closest('#filterClearBtn');
                if (!button) {
                    return;
                }

                state.filters = {};
                state.page = 1;
                renderFilters();
                await loadRows();
            });

            document.getElementById('editorForm').addEventListener('submit', saveRecord);
            importFormEl.addEventListener('submit', submitImport);

            (async function boot() {
                renderTabs();
                updateResourceActions();
                await loadLookups();
                renderFilters();
                await loadRows();
            })();
