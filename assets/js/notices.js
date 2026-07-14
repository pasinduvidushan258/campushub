document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('postNoticeForm');
    const input = document.getElementById('noticeInput');
    const categoryInput = document.getElementById('noticeCategory');
    const priorityInput = document.getElementById('noticePriority');
    const expiryInput = document.getElementById('noticeExpiry');
    const counter = document.getElementById('charCounter');
    const postBtn = document.getElementById('postBtn');
    const feed = document.getElementById('noticesFeed');
    const MAX_LEN = 500;

    if (!form || !input || !counter || !postBtn || !feed || !categoryInput || !priorityInput || !expiryInput) {
        return;
    }

    const updateUiState = () => {
        if (input.value.length > MAX_LEN) {
            input.value = input.value.slice(0, MAX_LEN);
        }

        const len = input.value.length;
        counter.textContent = `${len} / ${MAX_LEN}`;
        postBtn.disabled = input.value.trim().length === 0;
    };

    const buildNoticeCard = (data) => {
        const card = document.createElement('div');
        card.className = 'notice-card';

        const avatarWrap = document.createElement('div');
        avatarWrap.className = 'notice-avatar';

        if (data.avatar_path && data.avatar_path !== 'assets/images/default_avatar.png') {
            const img = document.createElement('img');
            img.src = data.avatar_path;
            img.alt = 'Avatar';
            img.className = 'notice-avatar';
            avatarWrap.replaceWith(img);
            card.appendChild(img);
        } else {
            const icon = document.createElement('i');
            icon.className = `fas ${data.author_type === 'admin' ? 'fa-user-shield' : 'fa-users'}`;
            avatarWrap.appendChild(icon);
            card.appendChild(avatarWrap);
        }

        const content = document.createElement('div');
        content.className = 'notice-content';
        const safePriority = ['low', 'normal', 'high', 'urgent'].includes(data.priority) ? data.priority : 'normal';
        const categoryText = data.category ? (data.category.charAt(0).toUpperCase() + data.category.slice(1)) : 'General';
        const priorityText = safePriority.charAt(0).toUpperCase() + safePriority.slice(1);

        let expiryMarkup = '';
        if (data.expiry_date) {
            const parsed = new Date(data.expiry_date.replace(' ', 'T'));
            if (!Number.isNaN(parsed.getTime())) {
                const now = new Date();
                const isExpired = parsed < now;
                const pretty = parsed.toLocaleString();
                expiryMarkup = `<span class="notice-pill ${isExpired ? 'notice-expired' : 'notice-expiry'}">${isExpired ? 'Expired: ' : 'Expires: '}${pretty}</span>`;
            }
        }

        content.innerHTML = `
            <div class="notice-header">
                <h4 class="notice-author"></h4>
                <span class="notice-author-type ${data.author_type === 'admin' ? 'type-badge-admin' : 'type-badge-society'}"></span>
                <span class="notice-time">Just now</span>
            </div>
            <div class="notice-meta-row">
                <span class="notice-pill notice-category">${categoryText}</span>
                <span class="notice-pill notice-priority-${safePriority}">${priorityText}</span>
                ${expiryMarkup}
            </div>
            <p class="notice-text"></p>
        `;

        const author = content.querySelector('.notice-author');
        const badge = content.querySelector('.notice-author-type');
        const text = content.querySelector('.notice-text');

        author.textContent = data.author_name;
        badge.textContent = data.author_label || (data.author_type === 'admin' ? 'Admin' : 'Society');
        text.textContent = data.content;

        card.appendChild(content);
        return card;
    };

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const content = input.value.trim();
        if (!content) {
            return;
        }

        postBtn.disabled = true;

        try {
            const body = new FormData();
            body.set('content', content);
            body.set('category', categoryInput.value);
            body.set('priority', priorityInput.value);
            body.set('expiry_date', expiryInput.value);

            const res = await fetch('post_notice.php', {
                method: 'POST',
                body,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const json = await res.json();
            if (!json.success) {
                throw new Error(json.message || 'Failed to post notice');
            }

            const empty = feed.querySelector('.notices-empty');
            if (empty) {
                empty.remove();
            }

            feed.prepend(buildNoticeCard(json.notice));
            input.value = '';
            categoryInput.value = 'general';
            priorityInput.value = 'normal';
            expiryInput.value = '';
            updateUiState();
        } catch (err) {
            console.error('[notices] post failed:', err);
            alert(err.message || 'Unable to post notice right now.');
            updateUiState();
        }
    });

    input.addEventListener('input', updateUiState);
    updateUiState();
});
