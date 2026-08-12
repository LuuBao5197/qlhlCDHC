@extends('layouts.dashboard')

@section('title', 'Quản trị danh mục')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-3">
                        <div>
                            <h4 class="card-title mb-1">Quản trị danh mục đào tạo</h4>
                            <p class="text-muted mb-2">Thêm, sửa, xóa dữ liệu lớp học, giáo viên, department, phòng học và
                                các danh mục liên quan.</p>
                        </div>
                        <div class="mt-2 mt-lg-0">
                            <span class="badge badge-info" id="resourceBadge">Department</span>
                        </div>
                    </div>

                    <div id="resourceTabs" class="resource-tabs mb-3"></div>

                    <div class="toolbar d-flex flex-column flex-md-row align-items-stretch align-items-md-center mb-3">
                        <div class="input-group mr-md-2 mb-2 mb-md-0">
                            <input id="searchInput" type="text" class="form-control" placeholder="Tìm kiếm...">
                            <div class="input-group-append">
                                <button id="searchBtn" class="btn btn-outline-secondary" type="button">Tìm</button>
                            </div>
                        </div>
                        <button id="addBtn" class="btn btn-primary mr-md-2 mb-2 mb-md-0" type="button">Thêm mới</button>
                        <button id="importBtn" class="btn btn-success mr-md-2 mb-2 mb-md-0" type="button"
                            style="display:none;">Nhập CSV</button>
                        <button id="reloadBtn" class="btn btn-dark" type="button">Tải lại</button>
                    </div>

                    <div id="filterBar" class="filter-bar d-flex flex-wrap align-items-center mb-3"></div>

                    <div id="alertBox" class="mb-3" style="display:none;"></div>

                    <div class="table-responsive">
                        <table class="table table-striped table-bordered mb-0">
                            <thead id="tableHead"></thead>
                            <tbody id="tableBody"></tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap">
                        <div id="pageSummary" class="text-muted mb-2">Tổng 0 bản ghi</div>
                        <nav class="mb-2">
                            <ul class="pagination pagination-sm mb-0" id="pageNumbers"></ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4" id="editorSection" style="display:none;">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title" id="editorTitle">Thêm mới</h5>
                    <form id="editorForm" novalidate>
                        <div class="row" id="formFields"></div>
                        <div class="d-flex flex-wrap mt-2">
                            <button type="submit" class="btn btn-success mr-2 mb-2">Lưu</button>
                            <button type="button" id="cancelBtn" class="btn btn-secondary mb-2">Hủy</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4" id="importSection" style="display:none;">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3">
                        <div>
                            <h5 class="card-title mb-1" id="importTitle">Nhập dữ liệu từ CSV</h5>
                            <p class="text-muted mb-0" id="importDesc">Tải file CSV tối đa 5 MB.</p>
                        </div>
                        <a class="btn btn-outline-info btn-sm mt-2 mt-md-0" id="importTemplateLink" href="#">Tải file
                            mẫu</a>
                    </div>

                    <form id="importForm" enctype="multipart/form-data" novalidate>
                        <div class="row">
                            <div class="col-md-6" id="importClassField" style="display:none;">
                                <div class="form-group">
                                    <label>Lớp học <span class="text-danger">*</span></label>
                                    <select id="importClassId" name="class_id" class="form-control">
                                        <option value="">-- Chọn lớp --</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>File CSV <span class="text-danger">*</span></label>
                                    <input id="importFile" name="import_file" type="file" class="form-control"
                                        accept=".csv,.txt,text/csv,text/plain" required>
                                    <small class="form-text text-muted" id="importHint">
                                        Cột bắt buộc: code, name.
                                    </small>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap mt-2">
                            <button id="submitImportBtn" type="submit" class="btn btn-success mr-2 mb-2">Nhập dữ liệu</button>
                            <button id="cancelImportBtn" type="button" class="btn btn-secondary mb-2">Hủy</button>
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
            border: 1px solid #e2e8f0;
            color: #4a5568;
            background: #f8f9fa;
            border-radius: 999px;
            padding: 6px 12px;
            font-size: 13px;
            cursor: pointer;
        }

        .resource-tab:hover {
            background: #eef1f4;
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

        .filter-bar {
            gap: 8px;
            background: #f8f9fa;
            border: 1px solid #e2e8f0;
            border-radius: var(--qlhl-radius, 0.5rem);
            padding: 10px 12px;
        }

        .filter-bar:empty {
            display: none;
            padding: 0;
            border: none;
        }

        .filter-bar .filter-field {
            min-width: 180px;
        }

        .filter-bar .filter-field label {
            font-size: 12px;
            color: #8b93a7;
            margin-bottom: 2px;
        }

        .filter-bar .filter-clear {
            align-self: flex-end;
        }

        /* Data table: light surface, dark text, soft zebra striping — matches the
           military-green navbar instead of the old dark/navy table styling. */
        .table-responsive {
            background: #ffffff;
            border: 1px solid var(--qlhl-border, #D9E0D3);
            border-radius: var(--qlhl-radius, 0.5rem);
            overflow: hidden;
        }

        .table {
            background: #ffffff;
            color: #212529;
            margin-bottom: 0;
        }

        .table thead th {
            background: #F4F6F3;
            color: #2F3B2C;
            font-weight: 600;
            border-bottom: 2px solid var(--qlhl-border, #D9E0D3);
        }

        .table td,
        .table th,
        .table-responsive .table tbody td {
            color: #2d3748;
            border-color: #E7ECE4;
            vertical-align: middle;
            white-space: nowrap;
        }

        .table-striped tbody tr:nth-of-type(odd) {
            background-color: #FAFBF9;
        }

        .table-striped tbody tr:hover {
            background-color: #F1F5EF;
        }

        .table td.wrap {
            white-space: normal;
            min-width: 180px;
        }

        /* Action buttons keep their colors; just soften the border for the light bg */
        .table td [data-action="edit"] {
            border-color: rgba(0, 0, 0, 0.08);
        }

        .table td [data-action="delete"] {
            border-color: rgba(0, 0, 0, 0.08);
        }

        /* Filter selects sit right above the table — keep them light for consistency */
        .filter-bar select,
        .filter-bar input {
            background: #ffffff;
            color: #212529;
            border: 1px solid var(--qlhl-border, #D9E0D3);
        }

        /* Pagination */
        .pagination .page-link {
            background: #ffffff;
            color: var(--qlhl-primary-dark, #4B5E43);
            border-color: var(--qlhl-border, #D9E0D3);
        }

        .pagination .page-item.active .page-link {
            background: var(--qlhl-primary, #5A7255);
            border-color: var(--qlhl-primary, #5A7255);
            color: #ffffff;
        }

        .pagination .page-item.disabled .page-link {
            background: #F4F6F3;
            color: #B7C2B2;
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
        document.addEventListener('DOMContentLoaded', function() {
            const csrfToken = '{{ csrf_token() }}';
            @include('management.partials.config-js')
            @include('management.partials.scripts-js')
        });
    </script>
@endsection
