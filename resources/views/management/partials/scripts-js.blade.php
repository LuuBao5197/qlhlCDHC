
            const tabsEl = document.getElementById('resourceTabs');
            const resourceBadgeEl = document.getElementById('resourceBadge');
            const tableHeadEl = document.getElementById('tableHead');
            const tableBodyEl = document.getElementById('tableBody');
            const pageInfoEl = document.getElementById('pageInfo');
            const alertBoxEl = document.getElementById('alertBox');
            const editorSectionEl = document.getElementById('editorSection');
            const editorTitleEl = document.getElementById('editorTitle');
            const formFieldsEl = document.getElementById('formFields');
            const searchInputEl = document.getElementById('searchInput');
            const prevBtnEl = document.getElementById('prevBtn');
            const nextBtnEl = document.getElementById('nextBtn');

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
                const headers = {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    ...(options.headers || {}),
                };

                if (method !== 'GET') {
                    headers['Content-Type'] = 'application/json';
                    headers['X-CSRF-TOKEN'] = csrfToken;
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
                    const error = new Error(payload?.message || 'Yeu cau that bai');
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

            function updatePagination() {
                pageInfoEl.textContent = `Trang ${state.page}/${state.lastPage} - Tong ${state.total} ban ghi`;
                prevBtnEl.disabled = state.page <= 1;
                nextBtnEl.disabled = state.page >= state.lastPage;
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

                editorTitleEl.textContent = `${isEditing ? 'Chinh sua' : 'Them moi'} - ${resource.label}`;

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
                                            <option value="">-- Chon --</option>
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

            function collectFormData() {
                const resource = resources[state.currentResource];
                const formData = {};

                for (const field of resource.fields) {
                    const input = formFieldsEl.querySelector(`[name="${field.key}"]`);
                    if (!input) {
                        continue;
                    }

                    let value = input.value;

                    if (field.type === 'number') {
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
                searchInputEl.value = '';
                closeEditor();
                renderTabs();
                await loadRows();
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
                    showAlert('success', `${isEditing ? 'Cap nhat' : 'Tao moi'} thanh cong.`);
                } catch (error) {
                    if (error.status === 422 && error.payload?.errors) {
                        const validationMessage = Object.entries(error.payload.errors)
                            .map(([field, messages]) =>
                                `${escapeHtml(field)}: ${escapeHtml(messages.join(', '))}`)
                            .join('<br>');
                        showAlert('danger', validationMessage);
                        return;
                    }

                    showAlert('danger', escapeHtml(error.message || 'Khong the luu du lieu.'));
                }
            }

            async function editRecord(id) {
                const row = state.rows.find((item) => String(item.id) === String(id));
                if (!row) {
                    showAlert('warning', 'Khong tim thay ban ghi de chinh sua.');
                    return;
                }

                renderEditor(row);
            }

            async function deleteRecord(id) {
                const resource = resources[state.currentResource];
                if (!confirm('Ban co chac chan muon xoa ban ghi nay?')) {
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
                    showAlert('success', 'Xoa thanh cong.');
                } catch (error) {
                    showAlert('danger', escapeHtml(error.message || 'Khong the xoa ban ghi.'));
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
                renderEditor(null);
            });

            document.getElementById('cancelBtn').addEventListener('click', function() {
                closeEditor();
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

            prevBtnEl.addEventListener('click', async function() {
                if (state.page <= 1) {
                    return;
                }
                state.page -= 1;
                await loadRows();
            });

            nextBtnEl.addEventListener('click', async function() {
                if (state.page >= state.lastPage) {
                    return;
                }
                state.page += 1;
                await loadRows();
            });

            document.getElementById('editorForm').addEventListener('submit', saveRecord);

            (async function boot() {
                renderTabs();
                await loadLookups();
                await loadRows();
            })();

