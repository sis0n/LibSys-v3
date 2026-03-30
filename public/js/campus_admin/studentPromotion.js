document.addEventListener("DOMContentLoaded", () => {
    let students = [];
    let selectedIds = new Set();
    let isGlobalSelect = false;
    let totalMatchingCount = 0;
    
    let currentFilters = {
        search: "",
        course_id: "",
        campus_id: "",
        year_level: "",
        status: 1
    };
    let currentPage = 1;
    const limit = 100;
    let isLoading = false;

    const tableBody = document.getElementById("promoTableBody");
    const statsContainer = document.getElementById("statsContainer");
    const resultsIndicator = document.getElementById("resultsIndicator");
    const promoteBtn = document.getElementById("promoteBtn");
    const deactivateBtn = document.getElementById("deactivateBtn");
    const activateBtn = document.getElementById("activateBtn");
    const selectAllCheck = document.getElementById("selectAllStudents");
    const paginationControls = document.getElementById("paginationControls");
    const paginationList = document.getElementById("paginationList");
    
    const selectionBanner = document.getElementById("selectionBanner");
    const selectEveryBtn = document.getElementById("selectEveryMatching");
    const globalSelectedMsg = document.getElementById("globalSelectedMsg");
    const clearGlobalBtn = document.getElementById("clearGlobalSelect");

    async function fetchStudents(page = 1) {
        if (isLoading) return;
        isLoading = true;
        currentPage = page;

        const params = new URLSearchParams({
            ...currentFilters,
            limit: limit,
            offset: (page - 1) * limit
        });

        try {
            const res = await fetch(`api/campus_admin/studentPromotion/fetch?${params}`);
            const data = await res.json();

            if (data.success) {
                students = data.students;
                totalMatchingCount = data.totalCount;
                renderTable();
                renderStats(data.stats);
                updateStudentCounts(students.length, data.totalCount, page, limit);
                renderPagination(data.totalPages, page);
                updateActionButtons();
                updateBanner();
            }
        } catch (err) {
            console.error("Load failed:", err);
        } finally {
            isLoading = false;
        }
    }

    function updateStudentCounts(length, total, page, perPage) {
        if (!resultsIndicator) return;
        if (total === 0) {
            resultsIndicator.innerHTML = `Showing <span class="font-medium text-gray-800">0</span> of <span class="font-medium text-gray-800">0</span> students`;
        } else {
            const startItem = (page - 1) * perPage + 1;
            const endItem = (page - 1) * perPage + length;
            resultsIndicator.innerHTML = `Showing <span class="font-medium text-gray-800">${startItem}-${endItem}</span> of <span class="font-medium text-gray-800">${total.toLocaleString()}</span> students`;
        }
    }

    function renderStats(stats) {
        statsContainer.innerHTML = "";
        const years = [1, 2, 3, 4];
        years.forEach(year => {
            const stat = stats.find(s => s.year_level == year) || { count: 0 };
            statsContainer.innerHTML += `
                <div class="bg-white border border-orange-200 p-4 rounded-xl shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-bold text-gray-500 uppercase">Year ${year}</p>
                            <h3 class="text-2xl font-black text-orange-600">${parseInt(stat.count).toLocaleString()}</h3>
                        </div>
                        <div class="w-10 h-10 bg-orange-50 rounded-full flex items-center justify-center">
                            <i class="ph ph-users text-xl text-orange-500"></i>
                        </div>
                    </div>
                </div>
            `;
        });
    }

    function renderTable() {
        tableBody.innerHTML = "";
        if (students.length === 0) {
            const statusLabel = currentFilters.status == 1 ? "active" : "inactive";
            tableBody.innerHTML = `<tr><td colspan="5" class="py-20 text-center text-gray-500">No ${statusLabel} students found for this selection.</td></tr>`;
            return;
        }

        students.forEach(s => {
            const isSelected = isGlobalSelect || selectedIds.has(String(s.student_id));
            const row = document.createElement("tr");
            row.className = `hover:bg-orange-50/30 transition-colors ${isSelected ? 'bg-orange-50' : ''}`;
            
            row.innerHTML = `
                <td class="px-6 py-4">
                    <input type="checkbox" class="student-checkbox w-4 h-4 accent-orange-600" data-id="${s.student_id}" ${isSelected ? 'checked' : ''} ${isGlobalSelect ? 'disabled' : ''}>
                </td>
                <td class="px-6 py-4">
                    <div class="flex flex-col">
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-gray-900">${s.first_name} ${s.last_name}</span>
                            ${s.is_active == 0 ? '<span class="text-[9px] bg-red-100 text-red-600 px-1.5 py-0.5 rounded font-black uppercase">Inactive</span>' : ''}
                        </div>
                        <span class="text-[11px] font-mono text-gray-500 uppercase tracking-wider">${s.student_number}</span>
                        <span class="text-[10px] text-orange-600 font-bold uppercase tracking-tighter">${s.campus_name || 'N/A'}</span>
                    </div>
                </td>
                <td class="px-6 py-4">
                    <div class="flex flex-col">
                        <span class="text-xs font-bold text-gray-700">${s.course_code}</span>
                        <span class="text-[10px] text-gray-500 truncate max-w-[200px]">${s.course_title}</span>
                    </div>
                </td>
                <td class="px-6 py-4 text-center">
                    <span class="px-3 py-1 rounded-full bg-gray-100 text-gray-700 font-bold text-[11px]">YEAR ${s.year_level}</span>
                </td>
                <td class="px-6 py-4 text-center">
                    <span class="px-3 py-1 rounded-full bg-green-100 text-green-700 font-bold text-[11px]">YEAR ${parseInt(s.year_level) + 1}</span>
                </td>
            `;
            
            if (!isGlobalSelect) {
                row.onclick = (e) => {
                    if (e.target.type !== 'checkbox' && !e.target.closest('.student-checkbox')) {
                        const cb = row.querySelector('.student-checkbox');
                        cb.checked = !cb.checked;
                        toggleSelection(String(s.student_id), cb.checked);
                        row.classList.toggle('bg-orange-50', cb.checked);
                    }
                };

                const checkbox = row.querySelector('.student-checkbox');
                checkbox.onclick = (e) => {
                    e.stopPropagation();
                };
                checkbox.onchange = (e) => {
                    toggleSelection(String(s.student_id), e.target.checked);
                    row.classList.toggle('bg-orange-50', e.target.checked);
                };
            }

            tableBody.appendChild(row);
        });
        
        updateSelectAllState();
    }

    function toggleSelection(id, isSelected) {
        if (isSelected) selectedIds.add(id);
        else selectedIds.delete(id);
        updateActionButtons();
        updateSelectAllState();
        updateBanner();
    }

    function updateBanner() {
        const pageSelectedCount = Array.from(document.querySelectorAll('.student-checkbox:checked')).length;
        const totalPossibleOnPage = students.length;

        if (isGlobalSelect) {
            selectionBanner.classList.remove("hidden");
            selectEveryBtn.parentElement.classList.add("hidden");
            globalSelectedMsg.classList.remove("hidden");
        } else if (pageSelectedCount > 0 && pageSelectedCount === totalPossibleOnPage && totalMatchingCount > totalPossibleOnPage) {
            selectionBanner.classList.remove("hidden");
            selectEveryBtn.parentElement.classList.remove("hidden");
            globalSelectedMsg.classList.add("hidden");
            selectionBanner.querySelector('.page-count').textContent = totalPossibleOnPage;
            selectionBanner.querySelector('.total-matching').textContent = totalMatchingCount.toLocaleString();
        } else {
            selectionBanner.classList.add("hidden");
        }
    }

    function updateActionButtons() {
        const count = isGlobalSelect ? totalMatchingCount : selectedIds.size;
        document.querySelectorAll(".selected-count").forEach(el => el.textContent = count.toLocaleString());
        
        const hasSelection = count > 0;
        const isActiveTab = currentFilters.status == 1;

        if (isActiveTab) {
            promoteBtn.classList.toggle("hidden", !hasSelection);
            promoteBtn.classList.toggle("inline-flex", hasSelection);
            deactivateBtn.classList.toggle("hidden", !hasSelection);
            deactivateBtn.classList.toggle("inline-flex", hasSelection);
            activateBtn.classList.add("hidden");
            activateBtn.classList.remove("inline-flex");
        } else {
            promoteBtn.classList.add("hidden");
            promoteBtn.classList.remove("inline-flex");
            deactivateBtn.classList.add("hidden");
            deactivateBtn.classList.remove("inline-flex");
            activateBtn.classList.toggle("hidden", !hasSelection);
            activateBtn.classList.toggle("inline-flex", hasSelection);
        }
    }

    function renderPagination(totalPages, page) {
        if (!paginationControls || !paginationList) return;
        if (totalPages <= 1) {
            paginationControls.classList.add("hidden");
            return;
        }

        paginationControls.classList.remove("hidden");
        paginationList.innerHTML = '';

        const createPageLink = (type, text, pageNum, isDisabled = false, isActive = false) => {
            const li = document.createElement("li");
            const a = document.createElement("a");
            a.href = "#";
            a.setAttribute("data-page", String(pageNum));
            let baseClasses = `flex items-center justify-center min-w-[32px] h-9 text-sm font-medium transition-all duration-200`;
            if (type === "prev" || type === "next") {
                a.innerHTML = text;
                baseClasses += ` text-gray-700 hover:text-orange-600 px-3`;
                if (isDisabled) baseClasses += ` opacity-50 cursor-not-allowed pointer-events-none`;
            } else if (type === "ellipsis") {
                a.textContent = text;
                baseClasses += ` text-gray-400 cursor-default px-2`;
            } else {
                a.textContent = text;
                if (isActive) {
                    baseClasses += ` text-white bg-orange-600 rounded-full shadow-sm px-3`;
                } else {
                    baseClasses += ` text-gray-700 hover:text-orange-600 hover:bg-orange-100 rounded-full px-3`;
                }
            }
            a.className = baseClasses;
            li.appendChild(a);
            paginationList.appendChild(li);
        };

        createPageLink("prev", `<i class="flex ph ph-caret-left text-lg"></i> Previous`, page - 1, page === 1);
        const window = 1;
        let pagesToShow = new Set([1, totalPages, page]);
        for (let i = 1; i <= window; i++) {
            if (page - i > 0) pagesToShow.add(page - i);
            if (page + i <= totalPages) pagesToShow.add(page + i);
        }
        const sortedPages = [...pagesToShow].sort((a, b) => a - b);
        let lastPage = 0;
        for (const p of sortedPages) {
            if (p > lastPage + 1) createPageLink("ellipsis", "…", "...", true);
            createPageLink("number", p, p, false, p === page);
            lastPage = p;
        }
        createPageLink("next", `Next <i class="ph ph-caret-right text-lg"></i>`, page + 1, page === totalPages);

        paginationList.onclick = (e) => {
            e.preventDefault();
            const target = e.target.closest('a[data-page]');
            if (!target) return;
            const pageStr = target.dataset.page;
            if (pageStr === '...') return;
            const pageNum = parseInt(pageStr, 10);
            if (!isNaN(pageNum) && pageNum !== currentPage) {
                fetchStudents(pageNum);
            }
        };
    }

    function updateSelectAllState() {
        if (students.length === 0) {
            selectAllCheck.checked = false;
            return;
        }
        const allOnPageSelected = students.every(s => selectedIds.has(String(s.student_id)));
        selectAllCheck.checked = isGlobalSelect || allOnPageSelected;
    }

    selectEveryBtn.onclick = () => {
        isGlobalSelect = true;
        selectedIds.clear();
        renderTable();
        updateBanner();
        updateActionButtons();
    };

    clearGlobalBtn.onclick = () => {
        isGlobalSelect = false;
        selectedIds.clear();
        selectAllCheck.checked = false;
        renderTable();
        updateBanner();
        updateActionButtons();
    };

    promoteBtn.onclick = async () => {
        const count = isGlobalSelect ? totalMatchingCount : selectedIds.size;
        const isConfirmed = await Swal.fire({
            title: 'Bulk Promote Students?',
            text: `You are about to increase the year level of ${count.toLocaleString()} students by +1. This will be recorded in the audit trail.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, Promote All',
            confirmButtonColor: '#ea580c',
            cancelButtonColor: '#9ca3af'
        });

        if (!isConfirmed.isConfirmed) return;

        try {
            const res = await fetch(`api/campus_admin/studentPromotion/promote`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    is_all: isGlobalSelect,
                    student_ids: Array.from(selectedIds),
                    filters: currentFilters
                })
            });
            const data = await res.json();
            if (data.success) {
                Swal.fire({ title: 'Success!', text: data.message, icon: 'success', confirmButtonColor: '#ea580c' });
                isGlobalSelect = false;
                selectedIds.clear();
                fetchStudents(1);
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        } catch (err) {
            Swal.fire('Error', 'Failed to process promotion.', 'error');
        }
    };

    deactivateBtn.onclick = async () => {
        const count = isGlobalSelect ? totalMatchingCount : selectedIds.size;
        const isConfirmed = await Swal.fire({
            title: 'Bulk Deactivate Students?',
            text: `Mark ${count.toLocaleString()} students as Inactive (Graduated/Terminated)? They will no longer be able to borrow items.`,
            icon: 'error',
            showCancelButton: true,
            confirmButtonText: 'Yes, Deactivate All',
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#9ca3af'
        });

        if (!isConfirmed.isConfirmed) return;

        try {
            const res = await fetch(`api/campus_admin/studentPromotion/deactivate`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    is_all: isGlobalSelect,
                    student_ids: Array.from(selectedIds),
                    filters: currentFilters
                })
            });
            const data = await res.json();
            if (data.success) {
                Swal.fire({ title: 'Deactivated', text: data.message, icon: 'success', confirmButtonColor: '#ea580c' });
                isGlobalSelect = false;
                selectedIds.clear();
                fetchStudents(1);
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        } catch (err) {
            Swal.fire('Error', 'Failed to deactivate students.', 'error');
        }
    };

    activateBtn.onclick = async () => {
        const count = isGlobalSelect ? totalMatchingCount : selectedIds.size;
        const isConfirmed = await Swal.fire({
            title: 'Bulk Activate Students?',
            text: `Restore access for ${count.toLocaleString()} students? They will be able to borrow items again.`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, Activate All',
            confirmButtonColor: '#16a34a',
            cancelButtonColor: '#9ca3af'
        });

        if (!isConfirmed.isConfirmed) return;

        try {
            const res = await fetch(`api/campus_admin/studentPromotion/activate`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    is_all: isGlobalSelect,
                    student_ids: Array.from(selectedIds),
                    filters: currentFilters
                })
            });
            const data = await res.json();
            if (data.success) {
                Swal.fire({ title: 'Activated', text: data.message, icon: 'success', confirmButtonColor: '#16a34a' });
                isGlobalSelect = false;
                selectedIds.clear();
                fetchStudents(1);
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        } catch (err) {
            Swal.fire('Error', 'Failed to activate students.', 'error');
        }
    };

    selectAllCheck.onchange = (e) => {
        const isChecked = e.target.checked;
        isGlobalSelect = false; // Reset global select when toggling the page checkbox
        students.forEach(s => {
            const id = String(s.student_id);
            if (isChecked) selectedIds.add(id);
            else selectedIds.delete(id);
        });
        renderTable();
        updateActionButtons();
        updateBanner();
    };

    let debounceTimer;
    document.getElementById("promoSearchInput").addEventListener("input", (e) => {
        currentFilters.search = e.target.value;
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            isGlobalSelect = false;
            selectedIds.clear();
            fetchStudents(1);
        }, 500);
    });

    document.getElementById("yearFilter").onchange = (e) => {
        currentFilters.year_level = e.target.value;
        isGlobalSelect = false;
        selectedIds.clear();
        fetchStudents(1);
    };

    document.getElementById("statusFilter").onchange = (e) => {
        currentFilters.status = e.target.value;
        isGlobalSelect = false;
        selectedIds.clear();
        fetchStudents(1);
    };

    document.getElementById("campusFilter").onchange = (e) => {
        currentFilters.campus_id = e.target.value;
        isGlobalSelect = false;
        selectedIds.clear();
        fetchStudents(1);
    };

    async function loadCourseOptions() {
        try {
            const res = await fetch(`api/campus_admin/userManagement/getAllCourses`);
            const data = await res.json();
            if (data.success) {
                const select = document.getElementById("courseFilter");
                data.courses.forEach(c => {
                    const option = document.createElement("option");
                    option.value = c.course_id;
                    option.textContent = c.course_code;
                    select.appendChild(option);
                });
            }
        } catch (err) {}
    }

    document.getElementById("courseFilter").onchange = (e) => {
        currentFilters.course_id = e.target.value;
        isGlobalSelect = false;
        selectedIds.clear();
        fetchStudents(1);
    };

    loadCourseOptions();
    fetchStudents(1);
});
