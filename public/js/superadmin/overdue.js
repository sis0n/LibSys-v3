document.addEventListener('DOMContentLoaded', () => {
    const tableBody = document.getElementById('overdue-table-body');
    const emptyState = document.getElementById('empty-state');
    const searchInput = document.getElementById('overdue-search');
    const urgencyFilter = document.getElementById('urgency-filter');
    const refreshBtn = document.getElementById('refresh-btn');
    const bulkNotifyBtn = document.getElementById('bulk-notify-btn');

    let masterData = [];
    let notifiedTodayCount = 0;

    async function fetchData() {
        tableBody.innerHTML = '<tr><td colspan="6" class="py-20 text-center"><i class="ph ph-spinner animate-spin text-3xl text-orange-300"></i></td></tr>';
        
        try {
            const search = searchInput.value;
            const urgency = urgencyFilter.value;
            const res = await fetch(`${BASE_URL_JS}/api/superadmin/overdue/getTableData?search=${encodeURIComponent(search)}&urgency=${urgency}`);
            const result = await res.json();

            if (result.success) {
                masterData = result.list;
                notifiedTodayCount = result.stats.notified_today;
                updateStats(result.stats);
                renderTable(result.list);
            }
        } catch (err) {
            console.error("Fetch Error:", err);
        }
    }

    function updateStats(stats) {
        document.getElementById('stat-total').textContent = stats.total;
        document.getElementById('stat-critical').textContent = stats.critical;
        document.getElementById('stat-due-today').textContent = stats.due_today;
        document.getElementById('stat-notified').textContent = notifiedTodayCount;
    }

    function renderTable(list) {
        tableBody.innerHTML = '';
        if (list.length === 0) {
            emptyState.classList.remove('hidden');
            return;
        }
        emptyState.classList.add('hidden');

        list.forEach(item => {
            const days = parseInt(item.days_late);
            let statusText = '';
            
            if (days > 0) {
                // Tunay na Overdue
                if (days > 7) {
                    statusText = `<span class="text-sm font-bold text-red-600">Critical (${days}d late)</span>`;
                } else if (days > 3) {
                    statusText = `<span class="text-sm font-bold text-amber-600">Warning (${days}d late)</span>`;
                } else {
                    statusText = `<span class="text-sm font-bold text-emerald-600">New (${days}d late)</span>`;
                }
            } else {
                // "Forced" Overdue o malapit na mag-due
                const absoluteDays = Math.abs(days);
                statusText = `<span class="text-sm font-bold text-blue-500">Upcoming (Due in ${absoluteDays}d)</span>`;
            }

            const lastNotifiedDate = item.last_notified 
                ? new Date(item.last_notified).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) 
                : 'Never';
            const lastNotifiedTime = item.last_notified 
                ? new Date(item.last_notified).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' }) 
                : '';

            const hasEmail = item.email && item.email !== 'N/A';

            const row = `
                <tr class="hover:bg-orange-50/30 transition-colors">
                    <td class="px-6 py-4">
                        <div class="font-bold text-gray-800 truncate" title="${item.first_name} ${item.last_name}">${item.first_name} ${item.last_name}</div>
                        <div class="text-sm text-gray-500">${item.student_number || 'ID: ' + item.user_id} • ${item.dept_code || 'N/A'}</div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-base font-bold text-gray-700 truncate w-full block" title="${item.item_title}">${item.item_title}</div>
                        <div class="text-xs text-gray-400 font-mono">Acc: ${item.accession_number || item.asset_tag}</div>
                    </td>
                    <td class="px-6 py-4 text-center">
                        ${statusText}
                    </td>
                    <td class="px-6 py-4 text-center">
                        <div class="flex flex-col items-center">
                            <span class="text-base font-bold text-gray-700">${lastNotifiedDate}</span>
                            <span class="text-xs text-gray-500 font-semibold uppercase tracking-tight">${lastNotifiedTime}</span>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-center">
                        ${item.notification_count > 0 ? `
                            <span class="text-blue-600 text-base font-black">
                                ${item.notification_count}x
                            </span>
                        ` : '<span class="text-gray-300 text-sm">-</span>'}
                    </td>
                    <td class="px-6 py-4 text-right">
                        ${hasEmail ? `
                            <button class="notify-btn p-2 text-blue-500 hover:bg-blue-100 rounded-md transition-all" 
                                title="Send Email Reminder"
                                data-id="${item.item_id}" data-user="${item.user_id}" data-email="${item.email}" 
                                data-name="${item.first_name} ${item.last_name}" data-title="${item.item_title}" data-due="${item.due_date}">
                                <i class="ph ph-paper-plane-tilt text-xl"></i>
                            </button>
                        ` : `
                            <span class="text-gray-300 italic text-xs px-2">No email</span>
                        `}
                    </td>
                </tr>
            `;
            tableBody.insertAdjacentHTML('beforeend', row);
        });
    }

    // Individual Notification
    tableBody.addEventListener('click', async (e) => {
        const btn = e.target.closest('.notify-btn');
        if (!btn || btn.disabled) return;

        btn.disabled = true;
        const icon = btn.querySelector('i');
        const originalIcon = icon.className;
        icon.className = 'ph ph-spinner animate-spin text-xl';

        const payload = {
            item_id: btn.dataset.id,
            user_id: btn.dataset.user,
            email: btn.dataset.email,
            name: btn.dataset.name,
            book_title: btn.dataset.title,
            due_date: btn.dataset.due
        };

        try {
            const res = await fetch(`${BASE_URL_JS}/api/superadmin/overdue/sendReminder`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const result = await res.json();

            if (result.success) {
                icon.className = 'ph ph-check-circle text-emerald-500 text-xl';
                notifiedTodayCount++;
                document.getElementById('stat-notified').textContent = notifiedTodayCount;
                if (typeof showProfileToast !== 'undefined') {
                    showProfileToast('ph-check-circle', 'Success', 'Reminder sent successfully.', 'success');
                }
            } else {
                icon.className = originalIcon;
                btn.disabled = false;
                alert(result.message);
            }
        } catch (err) {
            icon.className = originalIcon;
            btn.disabled = false;
        }
    });

    // Bulk Notify
    bulkNotifyBtn.addEventListener('click', async () => {
        const eligible = masterData.filter(i => i.email && i.email !== 'N/A');
        if (eligible.length === 0) {
            if (typeof showProfileToast !== 'undefined') {
                showProfileToast('ph-info', 'Notice', 'No students with valid email found.', 'warning');
            }
            return;
        }

        if (!confirm(`Send email reminders to ${eligible.length} students?`)) return;

        bulkNotifyBtn.disabled = true;
        const originalHtml = bulkNotifyBtn.innerHTML;
        bulkNotifyBtn.innerHTML = '<i class="ph ph-spinner animate-spin text-lg"></i> Processing...';

        let successCount = 0;
        for (const item of eligible) {
            try {
                const res = await fetch(`${BASE_URL_JS}/api/superadmin/overdue/sendReminder`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        item_id: item.item_id,
                        user_id: item.user_id,
                        email: item.email,
                        name: `${item.first_name} ${item.last_name}`,
                        book_title: item.item_title,
                        due_date: item.due_date
                    })
                });
                const result = await res.json();
                if (result.success) {
                    successCount++;
                    document.getElementById('stat-notified').textContent = ++notifiedTodayCount;
                }
            } catch (err) {}
        }

        bulkNotifyBtn.disabled = false;
        bulkNotifyBtn.innerHTML = originalHtml;
        
        if (typeof showProfileToast !== 'undefined') {
            showProfileToast('ph-check-circle', 'Complete', `Sent ${successCount} notifications.`, 'success');
        }
        fetchData();
    });

    searchInput.addEventListener('input', fetchData);
    urgencyFilter.addEventListener('change', fetchData);
    refreshBtn.addEventListener('click', fetchData);

    fetchData();
});
