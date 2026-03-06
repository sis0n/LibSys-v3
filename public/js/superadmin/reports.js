// --- SweetAlert Helper Functions (Global Declarations) ---

function showLoadingModal(message = "Processing request...", subMessage = "Please wait.") {
    if (typeof Swal == "undefined") return;
    Swal.fire({
        background: "transparent",
        html: `
            <div class="flex flex-col items-center justify-center gap-2">
                <div class="animate-spin rounded-full h-10 w-10 border-4 border-orange-200 border-t-orange-600"></div>
                <p class="text-gray-700 text-[14px]">${message}<br><span class="text-sm text-gray-500">${subMessage}</span></p>
            </div>
        `,
        allowOutsideClick: false,
        showConfirmButton: false,
        customClass: {
            popup: "!rounded-xl !shadow-md !border-2 !border-orange-400 !p-6 !bg-gradient-to-b !from-[#fffdfb] !to-[#fff6ef] shadow-[0_0_8px_#ffb34770]",
        },
    });
}

document.addEventListener('DOMContentLoaded', function () {
    const customDateModal = document.getElementById('customDateModal');
    const confirmDateRangeBtn = document.getElementById('confirmDateRange');
    const cancelDateRangeBtn = document.getElementById('cancelDateRange');
    const startDateInput = document.getElementById('startDate');
    const endDateInput = document.getElementById('endDate');
    const downloadReportBtn = document.getElementById('download-report-btn');
    const globalFilter = document.getElementById('global-report-filter');

    const startTime = Date.now();
    if (typeof showLoadingModal !== 'undefined') {
        showLoadingModal("Loading Reports Dashboard...", "Fetching all reports and charts.");
    }

    async function populateCirculatedBooks(filter = 'month') {
        const tbody = document.getElementById('circulated-books-tbody');
        if (!tbody) return;
        tbody.innerHTML = '<tr><td colspan="5" class="text-center p-4"><i class="ph ph-spinner animate-spin text-lg mr-2"></i>Loading...</td></tr>';
        try {
            const response = await fetch(`${BASE_URL}/api/superadmin/reports/circulated-books?filter=${filter}`);
            const result = await response.json();
            tbody.innerHTML = '';
            if (result.data && result.data.length > 0) {
                result.data.forEach(row => {
                    const isTotalRow = row.category === 'TOTAL';
                    const tr = document.createElement('tr');
                    if (isTotalRow) tr.classList.add('bg-orange-50', 'font-bold');
                    else tr.classList.add('border-b', 'border-orange-100');
                    
                    const count = row.filtered_count !== undefined ? row.filtered_count : (filter === 'day' ? row.today : (filter === 'year' ? row.year : row.month));

                    tr.innerHTML = `
                        <td class="px-4 py-2 text-left ${isTotalRow ? 'font-bold' : 'font-medium text-gray-700'}">${row.category}</td>
                        <td class="px-4 py-2 text-center">${row.today || 0}</td>
                        <td class="px-4 py-2 text-center">${row.week || 0}</td>
                        <td class="px-4 py-2 text-center">${row.month || 0}</td>
                        <td class="px-4 py-2 text-center">${row.year || 0}</td>
                    `;
                    tbody.appendChild(tr);
                });
            } else {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center p-4 text-gray-500 italic">No data available for this timeframe.</td></tr>';
            }
            return true;
        } catch (error) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center p-4 text-red-500">Failed to load report.</td></tr>';
            return false;
        }
    }

    async function populateCirculatedEquipments(filter = 'month') {
        const tbody = document.getElementById('circulated-equipments-tbody');
        if (!tbody) return;
        tbody.innerHTML = '<tr><td colspan="5" class="text-center p-4"><i class="ph ph-spinner animate-spin text-lg mr-2"></i>Loading...</td></tr>';
        try {
            const response = await fetch(`${BASE_URL}/api/superadmin/reports/circulated-equipments?filter=${filter}`);
            const result = await response.json();
            tbody.innerHTML = '';
            if (result.data && result.data.length > 0) {
                result.data.forEach(row => {
                    const isTotalRow = row.category === 'TOTAL';
                    const tr = document.createElement('tr');
                    if (isTotalRow) tr.classList.add('bg-orange-50', 'font-bold');
                    else tr.classList.add('border-b', 'border-orange-100');
                    tr.innerHTML = `
                        <td class="px-4 py-2 text-left ${isTotalRow ? 'font-bold' : 'font-medium text-gray-700'}">${row.category}</td>
                        <td class="px-4 py-2 text-center">${row.today || 0}</td>
                        <td class="px-4 py-2 text-center">${row.week || 0}</td>
                        <td class="px-4 py-2 text-center">${row.month || 0}</td>
                        <td class="px-4 py-2 text-center">${row.year || 0}</td>
                    `;
                    tbody.appendChild(tr);
                });
            } else {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center p-4 text-gray-500 italic">No data available for this timeframe.</td></tr>';
            }
            return true;
        } catch (error) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center p-4 text-red-500">Failed to load report.</td></tr>';
            return false;
        }
    }

    async function populateDeletedBooks(filter = 'month') {
        const tbody = document.getElementById('deleted-books-tbody');
        if (!tbody) return;
        tbody.innerHTML = '<tr><td colspan="4" class="text-center p-4"><i class="ph ph-spinner animate-spin text-lg mr-2"></i>Loading...</td></tr>';
        try {
            const response = await fetch(`${BASE_URL}/api/superadmin/reports/deleted-books?filter=${filter}`);
            const result = await response.json();
            tbody.innerHTML = '';
            if (result.success && result.data && result.data.length > 0) {
                result.data.forEach(row => {
                    const isTotalRow = row.year === 'TOTAL';
                    const tr = document.createElement('tr');
                    if (isTotalRow) tr.classList.add('bg-orange-50', 'font-bold');
                    else tr.classList.add('border-b', 'border-orange-100');
                    tr.innerHTML = `
                        <td class="px-4 py-2 text-left">${row.year}</td>
                        <td class="px-4 py-2 text-center">${row.month}</td>
                        <td class="px-4 py-2 text-center">${row.today}</td>
                        <td class="px-4 py-2 text-center font-medium text-gray-700">${row.count}</td>
                    `;
                    tbody.appendChild(tr);
                });
            } else {
                tbody.innerHTML = '<tr><td colspan="4" class="text-center p-4 text-gray-500 italic">No data available for this timeframe.</td></tr>';
            }
            return true;
        } catch (error) {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center p-4 text-red-500">Failed to load report.</td></tr>';
            return false;
        }
    }

    async function populateLibraryVisitByDepartment(filter = 'month') {
        const tbody = document.getElementById('library-visit-tbody');
        if (!tbody) return;
        tbody.innerHTML = '<tr><td colspan="5" class="text-center p-4"><i class="ph ph-spinner animate-spin text-lg mr-2"></i>Loading...</td></tr>';
        try {
            const response = await fetch(`${BASE_URL}/api/superadmin/reports/library-visits-department?filter=${filter}`);
            const result = await response.json();
            tbody.innerHTML = '';
            if (result.success && result.data && result.data.length > 0) {
                result.data.forEach(row => {
                    const isTotalRow = row.department === 'TOTAL';
                    const tr = document.createElement('tr');
                    if (isTotalRow) tr.classList.add('bg-orange-50', 'font-bold');
                    else tr.classList.add('border-b', 'border-orange-100');
                    tr.innerHTML = `
                        <td class="px-4 py-2 text-left ${isTotalRow ? 'font-bold' : 'font-medium text-gray-700'}">${row.department}</td>
                        <td class="px-4 py-2 text-center">${row.today || 0}</td>
                        <td class="px-4 py-2 text-center">${row.week || 0}</td>
                        <td class="px-4 py-2 text-center">${row.month || 0}</td>
                        <td class="px-4 py-2 text-center">${row.year || 0}</td>
                    `;
                    tbody.appendChild(tr);
                });
            } else {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center p-4 text-gray-500 italic">No data available for this timeframe.</td></tr>';
            }
            return true;
        } catch (error) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center p-4 text-red-500">Failed to load report.</td></tr>';
            return false;
        }
    }

    async function populateTopVisitors(filter = 'month') {
        const tbody = document.getElementById('top-visitors-tbody');
        if (!tbody) return;
        tbody.innerHTML = '<tr><td colspan="5" class="text-center p-4"><i class="ph ph-spinner animate-spin text-lg mr-2"></i>Loading...</td></tr>';
        try {
            const response = await fetch(`${BASE_URL}/api/superadmin/reports/top-visitors?filter=${filter}`);
            const result = await response.json();
            tbody.innerHTML = '';
            if (result.success && result.data && result.data.length > 0) {
                result.data.forEach((visitor, index) => {
                    const rank = index + 1;
                    const tr = document.createElement('tr');
                    tr.classList.add('border-b', 'border-orange-100');
                    let rankColor = 'text-black font-normal';
                    if (rank === 1) rankColor = 'text-yellow-400 font-bold';
                    else if (rank === 2) rankColor = 'text-gray-400 font-bold';
                    else if (rank === 3) rankColor = 'text-orange-500 font-bold';
                    tr.innerHTML = `
                        <td class="px-4 py-2 text-left"><span class="${rankColor}">${rank}</span></td>
                        <td class="px-4 py-2 text-left font-medium text-gray-700">${visitor.full_name}</td>
                        <td class="px-4 py-2 text-center">${visitor.student_number}</td>
                        <td class="px-4 py-2 text-center">${visitor.course}</td>
                        <td class="px-4 py-2 text-center">${visitor.visits}</td>
                    `;
                    tbody.appendChild(tr);
                });
            } else {
                tbody.innerHTML = `<tr><td colspan="5" class="text-center p-4 text-gray-500 italic">No visitor data available for this timeframe.</td></tr>`;
            }
            return true;
        } catch (error) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center p-4 text-red-500">Failed to load report.</td></tr>';
            return false;
        }
    }

    async function populateTopBorrowers(filter = 'month') {
        const tbody = document.getElementById('top-borrowers-tbody');
        if (!tbody) return;
        tbody.innerHTML = '<tr><td colspan="5" class="text-center p-4"><i class="ph ph-spinner animate-spin text-lg mr-2"></i>Loading...</td></tr>';
        try {
            const response = await fetch(`${BASE_URL}/api/superadmin/reports/top-borrowers?filter=${filter}`);
            const result = await response.json();
            tbody.innerHTML = '';
            if (result.success && result.data && result.data.length > 0) {
                result.data.forEach((borrower, index) => {
                    const rank = index + 1;
                    const tr = document.createElement('tr');
                    tr.classList.add('border-b', 'border-orange-100');
                    tr.innerHTML = `
                        <td class="px-4 py-2 text-left font-bold text-gray-400">${rank}</td>
                        <td class="px-4 py-2 text-left font-medium text-gray-700">${borrower.full_name}</td>
                        <td class="px-4 py-2 text-center">${borrower.identifier}</td>
                        <td class="px-4 py-2 text-center uppercase text-[10px] font-bold"><span class="bg-gray-100 px-2 py-1 rounded">${borrower.role}</span></td>
                        <td class="px-4 py-2 text-center font-bold text-orange-600">${borrower.borrow_count}</td>
                    `;
                    tbody.appendChild(tr);
                });
            } else {
                tbody.innerHTML = `<tr><td colspan="5" class="text-center p-4 text-gray-500 italic">No borrower data available for this timeframe.</td></tr>`;
            }
            return true;
        } catch (error) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center p-4 text-red-500">Failed to load report.</td></tr>';
            return false;
        }
    }

    async function populateMostBorrowedBooks(filter = 'month') {
        const tbody = document.getElementById('most-borrowed-books-tbody');
        if (!tbody) return;
        tbody.innerHTML = '<tr><td colspan="4" class="text-center p-4"><i class="ph ph-spinner animate-spin text-lg mr-2"></i>Loading...</td></tr>';
        try {
            const response = await fetch(`${BASE_URL}/api/superadmin/reports/most-borrowed-books?filter=${filter}`);
            const result = await response.json();
            tbody.innerHTML = '';
            if (result.success && result.data && result.data.length > 0) {
                result.data.forEach((book, index) => {
                    const rank = index + 1;
                    const tr = document.createElement('tr');
                    tr.classList.add('border-b', 'border-orange-100');
                    tr.innerHTML = `
                        <td class="px-4 py-2 text-left font-bold text-gray-400">${rank}</td>
                        <td class="px-4 py-2 text-left">
                            <p class="font-medium text-gray-700 line-clamp-1">${book.title}</p>
                            <p class="text-[10px] text-gray-500">${book.author}</p>
                        </td>
                        <td class="px-4 py-2 text-center font-mono text-xs">${book.accession_number}</td>
                        <td class="px-4 py-2 text-center font-bold text-blue-600">${book.borrow_count}</td>
                    `;
                    tbody.appendChild(tr);
                });
            } else {
                tbody.innerHTML = '<tr><td colspan="4" class="text-center p-4 text-gray-500 italic">No data available for this timeframe.</td></tr>';
            }
            return true;
        } catch (error) {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center p-4 text-red-500">Failed to load report.</td></tr>';
            return false;
        }
    }

    async function initializeCharts(filter = 'month') {
        const breakdownTbody = document.getElementById('department-breakdown-tbody');
        const weeklyCtx = document.getElementById('weeklyActivityChart')?.getContext('2d');
        try {
            const res = await fetch(`${BASE_URL}/api/superadmin/reports/getActivityReport?filter=${filter}`);
            const result = await res.json();
            if (!result.success) return false;

            // Update Activity Chart
            if (weeklyCtx && result.activityData) {
                if (window.activityChartInstance) window.activityChartInstance.destroy();
                window.activityChartInstance = new Chart(weeklyCtx, {
                    type: "line",
                    data: {
                        labels: result.activityData.map(w => w.label),
                        datasets: [
                            { label: "Visitors", data: result.activityData.map(w => w.visitors), borderColor: "#3b82f6", backgroundColor: "rgba(59,130,246,0.1)", tension: 0.4, fill: true },
                            { label: "Borrows", data: result.activityData.map(w => w.borrows), borderColor: "#f59e0b", backgroundColor: "rgba(245,158,11,0.1)", tension: 0.4, fill: true }
                        ]
                    },
                    options: { 
                        responsive: true, 
                        maintainAspectRatio: false,
                        scales: { y: { beginAtZero: true } } 
                    }
                });
            }

            // Update Department Breakdown Table
            if (breakdownTbody && result.visitorBreakdown && result.visitorBreakdown.byDepartment) {
                breakdownTbody.innerHTML = '';
                result.visitorBreakdown.byDepartment.forEach((dept, index) => {
                    const tr = document.createElement('tr');
                    tr.classList.add('hover:bg-orange-50/30', 'transition-colors');
                    tr.innerHTML = `
                        <td class="px-4 py-3 text-left font-black text-orange-600">${index + 1}</td>
                        <td class="px-4 py-3 text-left font-bold text-gray-700 uppercase tracking-tight text-[11px]">${dept.department || "N/A"}</td>
                        <td class="px-4 py-3 text-right font-black text-gray-800">${dept.count}</td>
                    `;
                    breakdownTbody.appendChild(tr);
                });
                if (result.visitorBreakdown.byDepartment.length === 0) {
                    breakdownTbody.innerHTML = '<tr><td colspan="3" class="py-10 text-center text-gray-400 italic text-xs uppercase font-bold">No records found</td></tr>';
                }
            }

            return true;
        } catch (err) {
            return false;
        }
    }

    async function initReports(filter = 'month') {
        const results = await Promise.all([
            populateCirculatedBooks(filter),
            populateCirculatedEquipments(filter),
            populateDeletedBooks(filter),
            populateLibraryVisitByDepartment(), // No filter passed here
            populateTopVisitors(filter),
            populateTopBorrowers(filter),
            populateMostBorrowedBooks(filter),
            initializeCharts(filter)
        ]);

        const badges = document.querySelectorAll('.timeframe-badge');
        const labels = { 'day': 'Today', 'month': 'This Month', 'year': 'This Year' };
        badges.forEach(b => b.textContent = labels[filter]);

        const criticalFailure = results.some(result => result === false);
        const elapsed = Date.now() - startTime;
        const minDelay = 1000;
        if (elapsed < minDelay) await new Promise(r => setTimeout(r, minDelay - elapsed));
        if (typeof Swal !== 'undefined') Swal.close();
    }

    initReports('month');

    globalFilter?.addEventListener('change', (e) => {
        initReports(e.target.value);
    });

    if (downloadReportBtn) {
        downloadReportBtn.addEventListener('click', () => customDateModal?.classList.remove('hidden'));
    }
    if (customDateModal) {
        cancelDateRangeBtn.addEventListener('click', () => customDateModal.classList.add('hidden'));
        confirmDateRangeBtn.addEventListener('click', () => {
            const start = startDateInput.value;
            const end = endDateInput.value;
            if (start && end) {
                customDateModal.classList.add('hidden');
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = `${BASE_URL}/api/superadmin/reports/generate-report`;
                form.target = '_blank';
                [['start_date', start], ['end_date', end]].forEach(([n, v]) => {
                    const i = document.createElement('input');
                    i.type = 'hidden'; i.name = n; i.value = v;
                    form.appendChild(i);
                });
                document.body.appendChild(form);
                form.submit();
                document.body.removeChild(form);
            } else alert('Please select both dates.');
        });
    }
});