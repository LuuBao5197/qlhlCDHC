{{--
    Included inside the page's own <script> closure (needs form, submitButton, tabs, tabId,
    labelOf and activate from the enclosing scope).

    Submits the semester form with fetch() instead of a full page POST so the browser never
    reloads on a validation failure. That keeps every per-class import file selected, and the
    server's complete error list is shown grouped by class with a badge on each failing tab.
--}}
const submitErrorBox = document.createElement('div');
submitErrorBox.id = 'submitErrorSummary';
submitErrorBox.className = 'alert alert-danger d-none';
submitErrorBox.tabIndex = -1;
form.parentNode.insertBefore(submitErrorBox, form);

const clearSubmitErrors = () => {
    submitErrorBox.classList.add('d-none');
    submitErrorBox.textContent = '';
    tabs.querySelectorAll('.tab-error-badge').forEach((badge) => badge.remove());
    tabs.querySelectorAll('[data-key]').forEach((btn) => { btn.style.borderColor = ''; });
};

const appendMessageList = (parent, messages) => {
    const list = document.createElement('ul');
    list.className = 'mb-2 pl-3';
    messages.forEach((message) => {
        const item = document.createElement('li');
        item.textContent = message;
        list.appendChild(item);
    });
    parent.appendChild(list);
};

const renderSubmitErrors = (payload) => {
    clearSubmitErrors();

    const errors = payload.errors || {};
    const classErrors = payload.class_errors || {};
    const classMessages = new Set();
    const generalMessages = [];

    Object.values(classErrors).forEach((messages) => messages.forEach((message) => classMessages.add(message)));
    Object.values(errors).forEach((messages) => {
        (Array.isArray(messages) ? messages : [messages]).forEach((message) => {
            if (!classMessages.has(message) && !generalMessages.includes(message)) {
                generalMessages.push(message);
            }
        });
    });

    const total = classMessages.size + generalMessages.length;
    const title = document.createElement('strong');
    title.textContent = `Có ${total} lỗi cần sửa trước khi lưu (các file đã chọn vẫn được giữ nguyên):`;
    submitErrorBox.appendChild(title);

    if (generalMessages.length) {
        const heading = document.createElement('div');
        heading.className = 'mt-2';
        heading.textContent = 'Lỗi chung';
        submitErrorBox.appendChild(heading);
        appendMessageList(submitErrorBox, generalMessages);
    }

    Object.entries(classErrors).forEach(([classKey, messages]) => {
        const btn = document.getElementById(tabId(classKey));
        const link = document.createElement('a');
        link.href = '#';
        link.className = 'font-weight-bold';
        link.textContent = `${labelOf(classKey)} (${messages.length} lỗi)`;
        link.addEventListener('click', (event) => {
            event.preventDefault();
            activate(classKey);
            document.getElementById(paneId(classKey))?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });

        const heading = document.createElement('div');
        heading.className = 'mt-2';
        heading.appendChild(link);
        submitErrorBox.appendChild(heading);
        appendMessageList(submitErrorBox, messages);

        if (btn) {
            const badge = document.createElement('span');
            badge.className = 'badge badge-danger ml-1 tab-error-badge';
            badge.textContent = String(messages.length);
            btn.appendChild(badge);
            btn.style.borderColor = '#dc3545';
        }
    });

    submitErrorBox.classList.remove('d-none');
    submitErrorBox.scrollIntoView({ behavior: 'smooth', block: 'start' });
    submitErrorBox.focus({ preventScroll: true });
};

const showSubmitFailure = (message) => {
    renderSubmitErrors({ errors: { general: [message] }, class_errors: {} });
};

let isSubmitting = false;
form.addEventListener('submit', async function(e) {
    // The page's own client-side validation already cancelled this submit.
    if (e.defaultPrevented) return;
    e.preventDefault();
    if (isSubmitting) return;

    isSubmitting = true;
    const originalLabel = submitButton.textContent;
    submitButton.disabled = true;
    submitButton.textContent = 'Đang kiểm tra...';
    clearSubmitErrors();

    try {
        const response = await fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        });

        if (response.status === 422) {
            renderSubmitErrors(await response.json());
        } else if (response.ok) {
            const data = await response.json();
            window.location.href = data.redirect;
            return;
        } else if (response.status === 403) {
            showSubmitFailure('Bạn không có quyền lưu thay đổi này, hoặc kế hoạch đã gửi duyệt nên không còn sửa được.');
        } else if (response.status === 419) {
            showSubmitFailure('Phiên làm việc đã hết hạn. Hãy mở lại trang này trong tab mới, đăng nhập lại rồi gửi lại (dữ liệu trong tab này vẫn còn nguyên).');
        } else {
            showSubmitFailure(`Máy chủ gặp lỗi (mã ${response.status}). Dữ liệu và file trên form vẫn được giữ, bạn có thể thử lại.`);
        }
    } catch (error) {
        showSubmitFailure('Không kết nối được máy chủ. Dữ liệu và file trên form vẫn được giữ, bạn có thể thử lại.');
    }

    isSubmitting = false;
    submitButton.disabled = false;
    submitButton.textContent = originalLabel;
});
