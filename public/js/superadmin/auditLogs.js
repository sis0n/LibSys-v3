document.addEventListener("DOMContentLoaded", () => {
    let currentSearch = "";
    let currentPage = 1;
    const limit = 50;
    let isLoading = false;

    const logTableBody = document.getElementById("logTableBody");
    const resultsIndicator = document.getElementById("resultsIndicator");
    const searchInput = document.getElementById("logSearchInput");
    const refreshBtn = document.getElementById("refreshLogsBtn");
    const paginationControls = document.getElementById("paginationControls");
    const paginationList = document.getElementById("paginationList");
    const pageIndicator = document.getElementById("pageIndicator");

    function getActionClass(action) {
        action = action.toUpperCase();
        if (action.includes('CREATE') || action.includes('BORROW')) return 'bg-green-100 text-green-700 border-green-200';
        if (action.includes('UPDATE') || action.includes('RETURN')) return 'bg-blue-100 text-blue-700 border-blue-200';
        if (action.includes('DELETE') || action.includes('DEACTIVATE')) return 'bg-red-100 text-red-700 border-red-200';
        if (action.includes('LOGIN')) return 'bg-orange-100 text-orange-700 border-orange-200';
        return 'bg-gray-100 text-gray-700 border-gray-200';
    }

    async function fetchLogs(page = 1) {
        if (isLoading) return;
        isLoading = true;
        currentPage = page;

        const params = new URLSearchParams({
            search: currentSearch,
            page: page,
            limit: limit
        });

        try {
            const res = await fetch(`api/superadmin/auditLogs/fetch?${params}`);
            const data = await res.json();

            if (data.success) {
                renderLogs(data.logs);
                renderPagination(data.totalPages, data.totalCount);
            }
        } catch (err) {
            console.error("Failed to fetch logs:", err);
            logTableBody.innerHTML = `<tr><td colspan="5" class="py-10 text-center text-red-500 font-medium">Error loading activity logs.</td></tr>`;
        } finally {
            isLoading = false;
        }
    }

    function renderLogs(logs) {
        logTableBody.innerHTML = "";
        if (logs.length === 0) {
            logTableBody.innerHTML = `<tr><td colspan="5" class="py-20 text-center text-gray-500">No activity records found.</td></tr>`;
            return;
        }

        logs.forEach(log => {
            const date = new Date(log.created_at);
            const formattedDate = date.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
            const formattedTime = date.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit', second: '2-digit' });

            const row = document.createElement("tr");
            row.className = "hover:bg-orange-50/30 transition-colors";
            row.innerHTML = `
                <td class="px-6 py-4">
                    <div class="flex flex-col">
                        <span class="font-bold text-gray-800">${formattedDate}</span>
                        <span class="text-[11px] text-gray-500 font-medium">${formattedTime}</span>
                    </div>
                </td>
                <td class="px-6 py-4">
                    <div class="flex flex-col">
                        <span class="font-bold text-gray-900">${log.full_name || 'System/Unknown'}</span>
                        <span class="text-[11px] uppercase text-orange-600 font-bold tracking-tight">${log.role || 'N/A'}</span>
                    </div>
                </td>
                <td class="px-6 py-4">
                    <span class="px-2.5 py-1 rounded-md text-[10px] font-black uppercase border ${getActionClass(log.action)}">
                        ${log.action}
                    </span>
                </td>
                <td class="px-6 py-4 font-mono text-[11px] text-gray-600 font-bold">
                    ${log.resource || '—'}
                </td>
                <td class="px-6 py-4 text-gray-700 text-xs leading-relaxed max-w-[400px]">
                    ${log.details || 'No additional details provided.'}
                </td>
            `;
            logTableBody.appendChild(row);
        });
    }

    function renderPagination(totalPages, totalCount) {
        paginationList.innerHTML = "";
        if (totalPages <= 1) {
            paginationControls.classList.add("hidden");
            resultsIndicator.innerHTML = `Total Records: <span class="font-bold text-gray-800">${totalCount}</span>`;
            pageIndicator.textContent = "";
            return;
        }

        paginationControls.classList.remove("hidden");
        const start = (currentPage - 1) * limit + 1;
        const end = Math.min(currentPage * limit, totalCount);
        resultsIndicator.innerHTML = `Showing <span class="font-bold text-gray-800">${start}-${end}</span> of <span class="font-bold text-gray-800">${totalCount}</span> records`;
        pageIndicator.textContent = `Page ${currentPage} of ${totalPages}`;

        const createBtn = (text, targetPage, active = false, disabled = false) => {
            const li = document.createElement("li");
            const btn = document.createElement("button");
            btn.className = `w-9 h-9 rounded-lg text-xs font-bold transition-all border ${active ? 'bg-orange-600 text-white border-orange-600 shadow-md' : 'bg-white text-gray-600 border-gray-200 hover:bg-orange-50 hover:text-orange-600'}`;
            if (disabled) btn.classList.add("opacity-50", "cursor-not-allowed");
            btn.innerHTML = text;
            btn.onclick = () => !disabled && fetchLogs(targetPage);
            li.appendChild(btn);
            paginationList.appendChild(li);
        };

        createBtn('<i class="ph ph-caret-left"></i>', currentPage - 1, false, currentPage === 1);
        
        let startPage = Math.max(1, currentPage - 2);
        let endPage = Math.min(totalPages, startPage + 4);
        if (endPage - startPage < 4) startPage = Math.max(1, endPage - 4);

        for (let i = startPage; i <= endPage; i++) {
            createBtn(i, i, i === currentPage);
        }

        createBtn('<i class="ph ph-caret-right"></i>', currentPage + 1, false, currentPage === totalPages);
    }

    let debounceTimer;
    searchInput.addEventListener("input", (e) => {
        currentSearch = e.target.value;
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => fetchLogs(1), 500);
    });

    refreshBtn.onclick = () => fetchLogs(currentPage);

    fetchLogs(1);
});
