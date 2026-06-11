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
        document.addEventListener('DOMContentLoaded', function() {
            const csrfToken = '{{ csrf_token() }}';
            @include('management.partials.config-js')
            @include('management.partials.scripts-js')
        });
    </script>
@endsection
