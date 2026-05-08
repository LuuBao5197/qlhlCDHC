@extends('layouts.dashboard')

@section('title', 'Quan tri danh muc')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-3">
                        <div>
                            <h4 class="card-title mb-1">Quan tri danh muc dao tao</h4>
                            <p class="text-muted mb-2">Them, sua, xoa du lieu lop hoc, giao vien, department, phong hoc va cac danh muc lien quan.</p>
                        </div>
                        <div class="mt-2 mt-lg-0">
                            <span class="badge badge-info" id="resourceBadge">Department</span>
                        </div>
                    </div>

                    <div id="resourceTabs" class="resource-tabs mb-3"></div>

                    <div class="toolbar d-flex flex-column flex-md-row align-items-stretch align-items-md-center mb-3">
                        <div class="input-group mr-md-2 mb-2 mb-md-0">
                            <input id="searchInput" type="text" class="form-control" placeholder="Tim kiem...">
                            <div class="input-group-append">
                                <button id="searchBtn" class="btn btn-outline-secondary" type="button">Tim</button>
                            </div>
                        </div>
                        <button id="addBtn" class="btn btn-primary mr-md-2 mb-2 mb-md-0" type="button">Them moi</button>
                        <button id="reloadBtn" class="btn btn-dark" type="button">Tai lai</button>
                    </div>

                    <div id="alertBox" class="mb-3" style="display:none;"></div>

                    <div class="table-responsive">
                        <table class="table table-striped table-bordered mb-0">
                            <thead id="tableHead"></thead>
                            <tbody id="tableBody"></tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap">
                        <button id="prevBtn" class="btn btn-outline-light btn-sm mb-2" type="button">Trang truoc</button>
                        <div id="pageInfo" class="text-muted mb-2">Trang 1/1</div>
                        <button id="nextBtn" class="btn btn-outline-light btn-sm mb-2" type="button">Trang sau</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4" id="editorSection" style="display:none;">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title" id="editorTitle">Them moi</h5>
                    <form id="editorForm" novalidate>
                        <div class="row" id="formFields"></div>
                        <div class="d-flex flex-wrap mt-2">
                            <button type="submit" class="btn btn-success mr-2 mb-2">Luu</button>
                            <button type="button" id="cancelBtn" class="btn btn-secondary mb-2">Huy</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <style>
        .resource-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .resource-tab {
            border: 1px solid #3f4551;
            color: #c3cad9;
            background: #1e2230;
            border-radius: 999px;
            padding: 6px 12px;
            font-size: 13px;
            cursor: pointer;
        }

        .resource-tab.active {
            background: #0090e7;
            border-color: #0090e7;
            color: #fff;
        }

        .toolbar .input-group {
            max-width: 520px;
            width: 100%;
        }

        .table td,
        .table th {
            vertical-align: middle;
            white-space: nowrap;
        }

        .table td.wrap {
            white-space: normal;
            min-width: 180px;
        }

        @media (max-width: 767px) {
            .resource-tab {
                flex: 1 1 calc(50% - 8px);
                text-align: center;
            }

            .toolbar .btn {
                width: 100%;
            }

            .table td,
            .table th {
                font-size: 12px;
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const csrfToken = '{{ csrf_token() }}';

            const resources = {
                departments: {
                    label: 'Department',
                    endpoint: '{{ url('/management/departments') }}',
                    columns: [
                        { key: 'code', label: 'Ma' },
                        { key: 'name', label: 'Ten' },
                        { key: 'description', label: 'Mo ta', wrap: true },
                        { key: 'status', label: 'Trang thai' }
                    ],
                    fields: [
                        { key: 'code', label: 'Ma department', type: 'text', required: true },
                        { key: 'name', label: 'Ten department', type: 'text', required: true },
                        { key: 'description', label: 'Mo ta', type: 'textarea' },
                        {
                            key: 'status',
                            label: 'Trang thai',
                            type: 'select',
                            required: true,
                            options: [
                                { value: 'active', label: 'active' },
                                { value: 'inactive', label: 'inactive' }
                            ]
                        }
                    ]
                },
                trainingClasses: {
                    label: 'Lop hoc',
                    endpoint: '{{ url('/management/training-classes') }}',
                    columns: [
                        { key: 'code', label: 'Ma lop' },
                        { key: 'name', label: 'Ten lop' },
                        { key: 'course_year', label: 'Khoa' },
                        { key: 'status', label: 'Trang thai' }
                    ],
                    fields: [
                        { key: 'code', label: 'Ma lop', type: 'text', required: true },
                        { key: 'name', label: 'Ten lop', type: 'text', required: true },
                        { key: 'course_year', label: 'Khoa', type: 'number' },
                        {
                            key: 'status',
                            label: 'Trang thai',
                            type: 'select',
                            required: true,
                            options: [
                                { value: 'active', label: 'active' },
                                { value: 'inactive', label: 'inactive' },
                                { value: 'archived', label: 'archived' }
                            ]
                        }
                    ]
                },
                teachers: {
                    label: 'Giao vien',
                    endpoint: '{{ url('/management/teachers') }}',
                    columns: [
                        { key: 'teacher_code', label: 'Ma GV' },
                        { key: 'name', label: 'Ten giao vien' },
                        { key: 'department.name', label: 'Department' },
                        { key: 'status', label: 'Trang thai' }
                    ],
                    fields: [
                        { key: 'teacher_code', label: 'Ma giao vien', type: 'text', required: true },
                        { key: 'name', label: 'Ten giao vien', type: 'text', required: true },
                        { key: 'department_id', label: 'Department', type: 'select', lookup: 'departments' },
                        {
                            key: 'status',
                            label: 'Trang thai',
                            type: 'select',
                            required: true,
                            options: [
                                { value: 'active', label: 'active' },
                                { value: 'inactive', label: 'inactive' }
                            ]
                        }
                    ]
                },
                rooms: {
                    label: 'Phong hoc',
                    endpoint: '{{ url('/management/rooms') }}',
                    columns: [
                        { key: 'code', label: 'Ma phong' },
                        { key: 'name', label: 'Ten phong' },
                        { key: 'capacity', label: 'Suc chua' },
                        { key: 'room_type', label: 'Loai phong' },
                        { key: 'status', label: 'Trang thai' }
                    ],
                    fields: [
                        { key: 'code', label: 'Ma phong', type: 'text', required: true },
                        { key: 'name', label: 'Ten phong', type: 'text', required: true },
                        { key: 'capacity', label: 'Suc chua', type: 'number' },
                        { key: 'room_type', label: 'Loai phong', type: 'text' },
                        {
                            key: 'status',
                            label: 'Trang thai',
                            type: 'select',
                            required: true,
                            options: [
                                { value: 'active', label: 'active' },
                                { value: 'inactive', label: 'inactive' },
                                { value: 'maintenance', label: 'maintenance' }
                            ]
                        }
                    ]
                },
                subjects: {
                    label: 'Mon hoc',
                    endpoint: '{{ url('/management/subjects') }}',
                    columns: [
                        { key: 'code', label: 'Ma mon' },
                        { key: 'name', label: 'Ten mon' },
                        { key: 'department.name', label: 'Department' },
                        { key: 'total_periods', label: 'Tong tiet' },
                        { key: 'status', label: 'Trang thai' }
                    ],
                    fields: [
                        { key: 'department_id', label: 'Department', type: 'select', lookup: 'departments' },
                        { key: 'code', label: 'Ma mon', type: 'text', required: true },
                        { key: 'name', label: 'Ten mon', type: 'text', required: true },
                        { key: 'total_periods', label: 'Tong tiet', type: 'number' },
                        {
                            key: 'status',
                            label: 'Trang thai',
                            type: 'select',
                            required: true,
                            options: [
                                { value: 'active', label: 'active' },
                                { value: 'inactive', label: 'inactive' }
                            ]
                        }
                    ]
                },
                subjectLessons: {
                    label: 'Bai hoc',
                    endpoint: '{{ url('/management/subject-lessons') }}',
                    columns: [
                        { key: 'subject.code', label: 'Ma mon' },
                        { key: 'lesson_no', label: 'So bai' },
                        { key: 'title', label: 'Tieu de', wrap: true },
                        { key: 'expected_periods', label: 'Tiet du kien' }
                    ],
                    fields: [
                        { key: 'subject_id', label: 'Mon hoc', type: 'select', lookup: 'subjects', required: true },
                        { key: 'lesson_no', label: 'So bai', type: 'number', required: true },
                        { key: 'title', label: 'Tieu de', type: 'text', required: true },
                        { key: 'expected_periods', label: 'Tiet du kien', type: 'number' },
                        { key: 'note', label: 'Ghi chu', type: 'textarea' }
                    ]
                },
                students: {
                    label: 'Hoc vien',
                    endpoint: '{{ url('/management/students') }}',
                    columns: [
                        { key: 'student_code', label: 'Ma hoc vien' },
                        { key: 'name', label: 'Ten hoc vien' },
                        { key: 'training_class.code', label: 'Lop' },
                        { key: 'date_of_birth', label: 'Ngay sinh' },
                        { key: 'status', label: 'Trang thai' }
                    ],
                    fields: [
                        { key: 'class_id', label: 'Lop hoc', type: 'select', lookup: 'trainingClasses' },
                        { key: 'student_code', label: 'Ma hoc vien', type: 'text', required: true },
                        { key: 'name', label: 'Ten hoc vien', type: 'text', required: true },
                        { key: 'date_of_birth', label: 'Ngay sinh', type: 'date' },
                        {
                            key: 'status',
                            label: 'Trang thai',
                            type: 'select',
                            required: true,
                            options: [
                                { value: 'active', label: 'active' },
                                { value: 'suspended', label: 'suspended' },
                                { value: 'graduated', label: 'graduated' }
                            ]
                        }
                    ]
                }
            };

            const lookupLabels = {
                departments: (item) => [item.code, item.name].filter(Boolean).join(' - '),
                trainingClasses: (item) => [item.code, item.name].filter(Boolean).join(' - '),
                subjects: (item) => [item.code, item.name].filter(Boolean).join(' - '),
            };

            const state = {
                currentResource: 'departments',
                rows: [],
                page: 1,
                lastPage: 1,
                perPage: 10,
                total: 0,
                q: '',
                editingId: null,
                lookups: {
                    departments: [],
                    trainingClasses: [],
                    subjects: [],
                }
            };

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
                        payload = { message: text };
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

                tableHeadEl.innerHTML = `
                    <tr>
                        <th style="width:60px">#</th>
                        ${resource.columns.map((column) => `<th>${escapeHtml(column.label)}</th>`).join('')}
                        <th style="width:160px">Tac vu</th>
                    </tr>
                `;

                if (state.rows.length === 0) {
                    tableBodyEl.innerHTML = `
                        <tr>
                            <td colspan="${resource.columns.length + 2}" class="text-center text-muted py-4">Khong co du lieu</td>
                        </tr>
                    `;
                    return;
                }

                tableBodyEl.innerHTML = state.rows
                    .map((row, index) => {
                        const columnsHtml = resource.columns
                            .map((column) => {
                                let value = getValue(row, column.key);
                                if (value === null || value === undefined || value === '') {
                                    value = '-';
                                }
                                const wrapClass = column.wrap ? 'wrap' : '';
                                return `<td class="${wrapClass}">${escapeHtml(value)}</td>`;
                            })
                            .join('');

                        return `
                            <tr>
                                <td>${(state.page - 1) * state.perPage + index + 1}</td>
                                ${columnsHtml}
                                <td>
                                    <button class="btn btn-sm btn-info mr-1" data-action="edit" data-id="${row.id}">Sua</button>
                                    <button class="btn btn-sm btn-danger" data-action="delete" data-id="${row.id}">Xoa</button>
                                </td>
                            </tr>
                        `;
                    })
                    .join('');
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
                    ['departments', resources.departments.endpoint],
                    ['trainingClasses', resources.trainingClasses.endpoint],
                    ['subjects', resources.subjects.endpoint],
                ];

                for (const [key, endpoint] of lookupEntries) {
                    try {
                        const payload = await request(buildUrl(endpoint, { per_page: 200 }));
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
                    label: (lookupLabels[field.lookup] ? lookupLabels[field.lookup](item) : item.name) || item.id,
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
                editorSectionEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
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
                            .map(([field, messages]) => `${escapeHtml(field)}: ${escapeHtml(messages.join(', '))}`)
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
                    await request(`${resource.endpoint}/${id}`, { method: 'DELETE' });

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

            tabsEl.addEventListener('click', async function (event) {
                const button = event.target.closest('[data-resource]');
                if (!button) {
                    return;
                }
                await switchResource(button.dataset.resource);
            });

            tableBodyEl.addEventListener('click', async function (event) {
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

            document.getElementById('addBtn').addEventListener('click', function () {
                renderEditor(null);
            });

            document.getElementById('cancelBtn').addEventListener('click', function () {
                closeEditor();
            });

            document.getElementById('reloadBtn').addEventListener('click', async function () {
                await loadLookups();
                await loadRows();
            });

            document.getElementById('searchBtn').addEventListener('click', async function () {
                state.q = searchInputEl.value.trim();
                state.page = 1;
                await loadRows();
            });

            searchInputEl.addEventListener('keydown', async function (event) {
                if (event.key !== 'Enter') {
                    return;
                }

                event.preventDefault();
                state.q = searchInputEl.value.trim();
                state.page = 1;
                await loadRows();
            });

            prevBtnEl.addEventListener('click', async function () {
                if (state.page <= 1) {
                    return;
                }
                state.page -= 1;
                await loadRows();
            });

            nextBtnEl.addEventListener('click', async function () {
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
        });
    </script>
@endsection
