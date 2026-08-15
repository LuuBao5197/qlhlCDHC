<div id="schedulePreviewOverlay" class="schedule-preview-overlay" style="display:none;">
    <div class="schedule-preview-dialog">
        <div class="schedule-preview-header">
            <strong id="schedulePreviewTitle">Xem truoc lich tong quat</strong>
            <button type="button" id="schedulePreviewClose" class="subject-picker-close" aria-label="Dong">&times;</button>
        </div>
        <div class="schedule-preview-body">
            <div id="schedulePreviewStatus" class="text-muted small mb-2"></div>
            <div id="schedulePreviewWarnings" class="schedule-preview-warnings mb-2" style="display:none;"></div>
            <div class="schedule-preview-legend small mb-2">
                <span class="schedule-preview-legend-swatch schedule-preview-cell-filled"></span> Co rule/su kien
                <span class="schedule-preview-legend-swatch schedule-preview-cell-empty ml-3"></span> Chua co du lieu
            </div>
            <div class="schedule-preview-table-wrap">
                <table class="schedule-preview-table" id="schedulePreviewTable"></table>
            </div>
        </div>
    </div>
</div>

<style>
    .schedule-preview-overlay {
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, .5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1300;
        padding: 16px;
    }

    .schedule-preview-dialog {
        background: #fff;
        border-radius: 12px;
        width: min(1200px, 100%);
        max-height: 88vh;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        box-shadow: 0 20px 60px rgba(0, 0, 0, .25);
    }

    .schedule-preview-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 14px 18px;
        border-bottom: 1px solid #e5e9f0;
        flex: 0 0 auto;
    }

    .schedule-preview-header strong {
        color: #0f4c81;
    }

    .schedule-preview-body {
        padding: 14px 18px;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        gap: 8px;
        min-height: 0;
    }

    .schedule-preview-legend-swatch {
        display: inline-block;
        width: 14px;
        height: 14px;
        border: 1px solid #d5dbe3;
        border-radius: 3px;
        vertical-align: middle;
        margin-right: 4px;
    }

    .schedule-preview-table-wrap {
        overflow: auto;
        border: 1px solid #e5e9f0;
        border-radius: 8px;
        flex: 1 1 auto;
        min-height: 0;
    }

    .schedule-preview-table {
        border-collapse: collapse;
        width: 100%;
        font-size: .78rem;
    }

    .schedule-preview-table th,
    .schedule-preview-table td {
        border: 1px solid #e5e9f0;
        padding: 5px 7px;
        text-align: center;
        white-space: nowrap;
    }

    .schedule-preview-table thead th {
        background: #0f4c81;
        color: #fff;
        position: sticky;
        top: 0;
        z-index: 2;
    }

    .schedule-preview-table .schedule-preview-row-label {
        text-align: left;
        background: #f4f7fb;
        font-weight: 600;
        white-space: nowrap;
        position: sticky;
        left: 0;
        z-index: 1;
    }

    .schedule-preview-table thead th.schedule-preview-row-label {
        z-index: 3;
    }

    .schedule-preview-cell-empty {
        background: #fff4e0;
    }

    .schedule-preview-cell-filled {
        background: #e6f4ea;
        font-weight: 600;
    }

    .schedule-preview-warnings {
        background: #fff7ed;
        border: 1px solid #fdba74;
        border-radius: 8px;
        padding: 10px 12px;
        color: #9a3412;
        font-size: .8rem;
        max-height: 160px;
        overflow-y: auto;
    }

    .schedule-preview-warnings strong {
        display: block;
        margin-bottom: 4px;
    }

    .schedule-preview-warnings ul {
        margin: 0;
        padding-left: 18px;
    }
</style>

<script>
    (function() {
        const weekdayLabels = {
            2: 'Thu 2',
            3: 'Thu 3',
            4: 'Thu 4',
            5: 'Thu 5',
            6: 'Thu 6',
            7: 'Thu 7',
            8: 'Chu nhat',
        };

        const overlay = document.getElementById('schedulePreviewOverlay');
        const closeBtn = document.getElementById('schedulePreviewClose');
        const titleEl = document.getElementById('schedulePreviewTitle');
        const statusEl = document.getElementById('schedulePreviewStatus');
        const warningsEl = document.getElementById('schedulePreviewWarnings');
        const tableEl = document.getElementById('schedulePreviewTable');

        let config = {
            url: '',
            csrfToken: ''
        };

        const esc = (v) => String(v ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#039;');

        const close = () => {
            if (overlay) overlay.style.display = 'none';
        };

        const formatWeekHeader = (week) => {
            const date = new Date(`${week.start_date}T00:00:00`);
            const dd = String(date.getDate()).padStart(2, '0');
            const mm = String(date.getMonth() + 1).padStart(2, '0');
            return `Tuan ${week.index}<br><small>${dd}/${mm}</small>`;
        };

        const renderTable = (data) => {
            const weeks = Array.isArray(data.weeks) ? data.weeks : [];
            const rows = Array.isArray(data.rows) ? data.rows : [];

            if (weeks.length === 0) {
                tableEl.innerHTML =
                    '<tbody><tr><td class="p-3 text-muted">Khong co du lieu tuan trong khoang thoi gian hoc ky.</td></tr></tbody>';
                return;
            }

            const thead =
                `<thead><tr><th class="schedule-preview-row-label">Thu / Tiet</th>${weeks.map((w) => `<th>${formatWeekHeader(w)}</th>`).join('')}</tr></thead>`;

            const tbody = `<tbody>${rows.map((row) => {
                const rowLabel = `${weekdayLabels[row.day_of_week] || ('Thu ' + row.day_of_week)}, tiet ${row.period_from}-${row.period_to}`;
                const cells = (row.cells || []).map((cell) => {
                    const label = cell.label;
                    const cls = label ? 'schedule-preview-cell-filled' : 'schedule-preview-cell-empty';
                    const colspan = cell.colspan && cell.colspan > 1 ? ` colspan="${cell.colspan}"` : '';
                    return `<td class="${cls}"${colspan}>${label ? esc(label) : ''}</td>`;
                }).join('');
                return `<tr><td class="schedule-preview-row-label">${esc(rowLabel)}</td>${cells}</tr>`;
            }).join('')}</tbody>`;

            tableEl.innerHTML = thead + tbody;
        };

        const renderWarnings = (warnings) => {
            if (!warningsEl) return;

            if (!Array.isArray(warnings) || warnings.length === 0) {
                warningsEl.style.display = 'none';
                warningsEl.innerHTML = '';
                return;
            }

            warningsEl.style.display = 'block';
            warningsEl.innerHTML = `<strong>File import co ${warnings.length} dong/loi khong hop le (da bo qua):</strong>` +
                `<ul>${warnings.map((w) => `<li>${esc(w)}</li>`).join('')}</ul>`;
        };

        const extractErrorMessage = (errorData) => {
            if (!errorData) return 'Khong the tao du lieu xem truoc.';
            if (errorData.message) return errorData.message;
            if (errorData.errors) {
                const messages = Object.values(errorData.errors).flat();
                if (messages.length > 0) return messages.join(' ');
            }
            return 'Khong the tao du lieu xem truoc.';
        };

        const open = async (payload) => {
            if (!overlay) return;

            titleEl.textContent = payload.title || 'Xem truoc lich tong quat';
            statusEl.textContent = 'Dang tai du lieu xem truoc...';
            tableEl.innerHTML = '';
            renderWarnings([]);
            overlay.style.display = 'flex';

            try {
                const formData = new FormData();
                formData.append('start_date', payload.startDate || '');
                formData.append('end_date', payload.endDate || '');
                formData.append('rules', JSON.stringify(payload.rules || []));
                formData.append('class_events', JSON.stringify(payload.classEvents || []));
                formData.append('global_events', JSON.stringify(payload.globalEvents || []));

                if (payload.classCode) {
                    formData.append('class_code', payload.classCode);
                }

                if (payload.importFile instanceof File) {
                    formData.append('import_file', payload.importFile);
                    statusEl.textContent = 'Dang doc file import va tao du lieu xem truoc...';
                }

                const response = await fetch(config.url, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': config.csrfToken,
                    },
                    body: formData,
                });

                if (!response.ok) {
                    const errorData = await response.json().catch(() => null);
                    statusEl.textContent = extractErrorMessage(errorData);
                    return;
                }

                const data = await response.json();
                statusEl.textContent = 'O mau vang la khoang trong chua co rule/su kien che phu.';
                renderWarnings(data.warnings);
                renderTable(data);
            } catch (error) {
                statusEl.textContent = 'Loi ket noi khi tao du lieu xem truoc.';
            }
        };

        closeBtn?.addEventListener('click', close);
        overlay?.addEventListener('click', (event) => {
            if (event.target === overlay) close();
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && overlay && overlay.style.display !== 'none') close();
        });

        window.ScheduleSemesterPreview = {
            init(options) {
                config = { ...config, ...options };
            },
            open,
            close,
        };
    })();
</script>
