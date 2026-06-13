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
                            <p class="text-muted mb-2">Them, sua, xoa du lieu lop hoc, giao vien, department, phong hoc va
                                cac danh muc lien quan.</p>
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
                        <button id="importBtn" class="btn btn-success mr-md-2 mb-2 mb-md-0" type="button"
                            style="display:none;">Nhap CSV</button>
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

    <div class="row mt-4" id="importSection" style="display:none;">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3">
                        <div>
                            <h5 class="card-title mb-1">Nhap danh sach hoc vien tu CSV</h5>
                            <p class="text-muted mb-0">Chon lop, sau do tai file CSV toi da 5 MB va 5.000 dong.</p>
                        </div>
                        <a class="btn btn-outline-info btn-sm mt-2 mt-md-0"
                            href="{{ route('management.students.import-template') }}">Tai file mau</a>
                    </div>

                    <form id="importForm" enctype="multipart/form-data" novalidate>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Lop hoc <span class="text-danger">*</span></label>
                                    <select id="importClassId" name="class_id" class="form-control" required>
                                        <option value="">-- Chon lop --</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>File CSV <span class="text-danger">*</span></label>
                                    <input id="importFile" name="import_file" type="file" class="form-control"
                                        accept=".csv,.txt,text/csv,text/plain" required>
                                    <small class="form-text text-muted">
                                        Cot bat buoc: student_code, name. Cot tuy chon: date_of_birth, status.
                                    </small>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap mt-2">
                            <button id="submitImportBtn" type="submit" class="btn btn-success mr-2 mb-2">Nhap du lieu</button>
                            <button id="cancelImportBtn" type="button" class="btn btn-secondary mb-2">Huy</button>
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
        document.addEventListener('DOMContentLoaded', function() {
            const csrfToken = '{{ csrf_token() }}';
            @include('management.partials.config-js')
            @include('management.partials.scripts-js')
        });
    </script>
@endsection
