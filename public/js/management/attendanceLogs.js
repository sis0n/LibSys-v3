// --- SweetAlert Helper Functions (Para ma-maintain ang design consistency) ---

function showSuccessToast(title, body = "") {
    if (typeof Swal == "undefined") return alert(title);
    Swal.fire({
        toast: true,
        position: "bottom-end",
        showConfirmButton: false,
        timer: 3000,
        width: "360px",
        background: "transparent",
        html: `<div class="flex flex-col text-left"><div class="flex items-center gap-3 mb-2"><div class="flex items-center justify-center w-10 h-10 rounded-full bg-green-100 text-green-600"><i class="ph ph-check-circle text-lg"></i></div><div><h3 class="text-[15px] font-semibold text-green-600">${title}</h3><p class="text-[13px] text-gray-700 mt-0.5">${body}</p></div></div></div>`,
        customClass: {
            popup: "!rounded-xl !shadow-md !border-2 !border-green-400 !p-4 !bg-gradient-to-b !from-[#fffdfb] !to-[#f0fff5] shadow-[0_0_8px_#22c55e70]",
        },
    });
}

function showErrorToast(title, body = "An error occurred during processing.") {
    if (typeof Swal == "undefined") return alert(title);
    Swal.fire({
        toast: true,
        position: "bottom-end",
        showConfirmButton: false,
        timer: 4000,
        width: "360px",
        background: "transparent",
        html: `<div class="flex flex-col text-left"><div class="flex items-center gap-3 mb-2"><div class="flex items-center justify-center w-10 h-10 rounded-full bg-red-100 text-red-600"><i class="ph ph-x-circle text-lg"></i></div><div><h3 class="text-[15px] font-semibold text-red-600">${title}</h3><p class="text-[13px] text-gray-700 mt-0.5">${body}</p></div></div></div>`,
        customClass: {
            popup: "!rounded-xl !shadow-md !border-2 !border-red-400 !p-4 !bg-gradient-to-b !from-[#fffdfb] !to-[#fff6ef] shadow-[0_0_8px_#ff6b6b70]",
        },
    });
}

// 🟠 LOADING MODAL (ORANGE THEME)
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
            // ORANGE THEME DESIGN
            popup: "!rounded-xl !shadow-md !border-2 !border-orange-400 !p-6 !bg-gradient-to-b !from-[#fffdfb] !to-[#fff6ef] shadow-[0_0_8px_#ffb34770]",
        },
    });
}

// 🟠 CONFIRMATION MODAL (ORANGE THEME)
async function showConfirmationModal(title, text, confirmText = "Confirm", icon = "ph-warning-circle") {
    if (typeof Swal == "undefined") return confirm(title);
    const result = await Swal.fire({
        background: "transparent",
        html: `
            <div class="flex flex-col text-center">
                <div class="flex justify-center mb-3">
                    <div class="flex items-center justify-center w-14 h-14 rounded-full bg-orange-100 text-orange-600">
                        <i class="ph ${icon} text-2xl"></i>
                    </div>
                </div>
                <h3 class="text-[17px] font-semibold text-orange-700">${title}</h3>
                <p class="text-[14px] text-gray-700 mt-1">${text}</p>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: confirmText,
        cancelButtonText: "Cancel",
        customClass: {
            popup:
                "!rounded-xl !shadow-md !p-6 !bg-gradient-to-b !from-[#fffdfb] !to-[#fff6ef] !border-2 !border-orange-400 shadow-[0_0_8px_#ffb34770]",
            confirmButton:
                "!bg-orange-600 !text-white !px-5 !py-2.5 !rounded-lg hover:!bg-orange-700",
            cancelButton:
                "!bg-gray-200 !text-gray-800 !px-5 !py-2.5 !rounded-lg hover:!bg-gray-300",
        },
    });
    return result.isConfirmed;
}
// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------


function initializeAttendanceLogs() {
    // --- DOM ELEMENTS ---
    const buttons = document.querySelectorAll('.period-btn');
    const label = document.getElementById('visitor-label');
    const count = document.getElementById('visitor-count');
    const searchInput = document.getElementById("attendanceSearchInput");
    const dateInput = document.getElementById("datePickerInput");
    const tableBody = document.getElementById("attendanceTableBody");
    const noRecordsRow = document.getElementById("noRecordsRow");
    const checkinsModal = document.getElementById("viewCheckinsModal");
    const closeCheckinsBtn1 = document.getElementById("closeCheckinsModal");
    const closeCheckinsBtn2 = document.getElementById("closeCheckinsModalBtn");
    const checkinsModalTitle = document.getElementById("checkinsModalTitle");
    const checkinsModalSubtitle = document.getElementById("checkinsModalSubtitle");
    const checkinsList = document.getElementById("checkinsList");

    // Pagination Elements
    const paginationContainer = document.getElementById('pagination-container');
    const paginationNumbers = document.getElementById('pagination-numbers');
    const prevPageBtn = document.getElementById('prev-page');
    const nextPageBtn = document.getElementById('next-page');

    // --- VALIDATION ---
    if (!searchInput || !dateInput || !tableBody || !noRecordsRow || !checkinsModal || !paginationContainer) {
        console.error("AttendanceLogs Error: Critical elements are missing.");
        if (tableBody) {
            tableBody.innerHTML = `<tr><td colspan="5" class="text-center text-red-500 py-10">Page components failed to load. Please refresh.</td></tr>`;
        }
        return;
    }

    // --- STATE ---
    let currentSearch = "";
    let currentDate = "";
    let currentGroupedLogs = []; // Holds the result of groupLogs()
    let searchTimeout; // For debounce

    // Pagination State
    let currentPage = 1;
    const rowsPerPage = 10;

    // --- HELPERS ---
    const timezone = "Asia/Manila";
    function getPhDate(date = new Date()) {
        try {
            return new Date(date.toLocaleString("en-US", { timeZone: timezone }));
        } catch (e) {
            return new Date();
        }
    }

    function formatDate(date) {
        const yyyy = date.getFullYear();
        const mm = String(date.getMonth() + 1).padStart(2, '0');
        const dd = String(date.getDate()).padStart(2, '0');
        return `${yyyy}-${mm}-${dd}`;
    }
    
    function formatTo12Hour(dateStr, timeStr) {
        try {
            const dateTime = new Date(`${dateStr}T${timeStr}`);
            return dateTime.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true });
        } catch (e) {
            return timeStr;
        }
    }

    // --- DATA PROCESSING ---
    function groupLogs(logs) {
        const studentMap = {};
        logs.forEach(log => {
            const key = `${log.studentNumber}-${log.date}`;
            const checkInTime = formatTo12Hour(log.date, log.time);
            if (!studentMap[key]) {
                studentMap[key] = {
                    studentName: log.studentName,
                    studentNumber: log.studentNumber,
                    date: log.date,
                    firstCheckIn: checkInTime,
                    totalCheckIns: 1,
                    allCheckIns: [checkInTime]
                };
            } else {
                studentMap[key].totalCheckIns++;
                studentMap[key].allCheckIns.push(checkInTime);
            }
        });
        return Object.values(studentMap).sort((a, b) => new Date(b.date + ' ' + b.firstCheckIn) - new Date(a.date + ' ' + a.firstCheckIn));
    }

    // --- RENDERING ---
    function renderTable() {
        tableBody.innerHTML = '';
        noRecordsRow.classList.add('hidden');

        if (currentGroupedLogs.length === 0) {
            tableBody.appendChild(noRecordsRow);
            noRecordsRow.classList.remove('hidden');
            paginationContainer.classList.add('hidden');
            return;
        }

        paginationContainer.classList.remove('hidden');
        const start = (currentPage - 1) * rowsPerPage;
        const end = start + rowsPerPage;
        const paginatedData = currentGroupedLogs.slice(start, end);

        const fragment = document.createDocumentFragment();
        paginatedData.forEach(log => {
            const row = document.createElement('tr');
            row.className = 'bg-white';
            const studentName = log.studentName ? log.studentName.replace(/</g, "&lt;") : 'N/A';
            const studentNumber = log.studentNumber ? log.studentNumber.replace(/</g, "&lt;") : 'N/A';
            const date = log.date ? log.date.replace(/</g, "&lt;") : 'N/A';
            const firstCheckIn = log.firstCheckIn ? log.firstCheckIn.replace(/</g, "&lt;") : 'N/A';

            row.innerHTML = `
                <td class="px-4 py-3"><p class="font-medium text-gray-800">${studentName}</p><p class="text-gray-500 text-xs">${studentNumber}</p></td>
                <td class="px-4 py-3 text-gray-700">${date}</td>
                <td class="px-4 py-3 text-gray-700">${firstCheckIn}</td>
                <td class="px-4 py-3 text-gray-700"><span class="bg-orange-100 text-orange-700 text-xs font-medium px-2 py-0.5 rounded-full">${log.totalCheckIns || 0}</span></td>
                <td class="px-4 py-3">
                    <button class="viewCheckinsBtn flex items-center gap-1 border border-orange-200 text-gray-600 px-2 py-1.5 rounded-md text-xs font-medium hover:bg-orange-50 transition" 
                        data-student-name="${studentName.replace(/"/g, "&quot;")}" 
                        data-date="${date.replace(/"/g, "&quot;")}"
                        data-checkins='${JSON.stringify(log.allCheckIns || [])}'>
                        <i class="ph ph-eye text-base"></i><span>View All</span>
                    </button>
                </td>`;
            fragment.appendChild(row);
        });
        tableBody.appendChild(fragment);
    }

    function renderPagination() {
        paginationNumbers.innerHTML = '';
        const pageCount = Math.ceil(currentGroupedLogs.length / rowsPerPage);

        if (pageCount <= 1) {
            paginationContainer.classList.add('hidden');
            return;
        }
        paginationContainer.classList.remove('hidden');

        prevPageBtn.classList.toggle('text-gray-400', currentPage === 1);
        prevPageBtn.classList.toggle('pointer-events-none', currentPage === 1);
        nextPageBtn.classList.toggle('text-gray-400', currentPage === pageCount);
        nextPageBtn.classList.toggle('pointer-events-none', currentPage === pageCount);

        for (let i = 1; i <= pageCount; i++) {
            const pageNumber = document.createElement('a');
            pageNumber.href = '#';
            pageNumber.textContent = i;
            pageNumber.classList.add('px-4', 'py-1.5', 'rounded-full', 'transition', 'duration-200');
            if (i === currentPage) {
                pageNumber.classList.add('bg-orange-500', 'text-white', 'shadow-sm');
            } else {
                pageNumber.classList.add('text-gray-700', 'hover:text-orange-600', 'hover:bg-orange-50');
            }

            pageNumber.addEventListener('click', e => {
                e.preventDefault();
                currentPage = i;
                renderTable();
                renderPagination();
            });
            paginationNumbers.appendChild(pageNumber);
        }
    }

    // --- DATA FETCHING (GINAWANG ASYNC) ---
    async function fetchLogs(showLoading = true) {
        // 1. Start timer for minimum delay
        const startTime = Date.now(); 
        
        // 2. 🟠 SHOW LOADING MODAL (ONLY IF showLoading is true)
        if (showLoading && typeof showLoadingModal !== 'undefined') {
            showLoadingModal("Loading Attendance Logs...", "Fetching and processing visitor data.");
        }
        
        // 3. Clear table while loading
        tableBody.innerHTML = '';
        noRecordsRow.classList.add('hidden');
        paginationContainer.classList.add('hidden');

        const todayStr = formatDate(getPhDate());
        const yesterdayStr = formatDate(new Date(getPhDate().setDate(getPhDate().getDate() - 1)));

        let periodToSend = 'All dates';
        let dateToFilter = currentDate;

        if (currentDate === todayStr) {
            periodToSend = 'Today';
            dateToFilter = null;
        } else if (currentDate === yesterdayStr) {
            periodToSend = 'Yesterday';
            dateToFilter = null;
        }

        let url = `${BASE_URL_JS}/api/attendance/logs/ajax?period=${periodToSend}&search=${currentSearch}`;

        try {
            const res = await fetch(url);
            console.log("Fetch response status:", res.status);
            if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
            const data = await res.json();
            console.log("Fetched logs:", data);

            let logsToProcess = [];
            if (Array.isArray(data)) {
                logsToProcess = data;
            } else if (data && data.success === false) {
                console.error("API Error:", data.message);
                if (typeof showErrorToast !== 'undefined') showErrorToast("Data Load Failed", data.message || "Could not fetch attendance data.");
                throw new Error(data.message || "API returned success:false");
            } else if (data && Array.isArray(data.data)) {
                logsToProcess = data.data;
            }
            
            // 4. Implement Minimum Delay and Close Modal
            if (showLoading) {
                 const elapsed = Date.now() - startTime;
                 const minDelay = 500; // 500ms minimum loading time
                 if (elapsed < minDelay) await new Promise(r => setTimeout(r, minDelay - elapsed));
            }
           
            // 5. Close Loading Modal (SUCCESS)
            if (showLoading && typeof Swal !== 'undefined') Swal.close();
            
            let filteredData = logsToProcess;
            if (dateToFilter) {
                filteredData = logsToProcess.filter(log => log.date === dateToFilter);
            }
            currentGroupedLogs = groupLogs(filteredData);
            currentPage = 1;
            renderTable();
            renderPagination();
            
        } catch (err) {
            console.error("Failed to fetch logs:", err);
            
            // 6. Close Loading Modal and show error (ERROR)
            if (showLoading && typeof Swal !== 'undefined') {
                Swal.close();
                // Optional: Show error toast if available
                if (typeof showErrorToast !== 'undefined') showErrorToast("Data Load Failed", "Could not fetch attendance data.");
            }
            
            currentGroupedLogs = [];
            renderTable();
            renderPagination();
            // Show permanent error message in the table
            noRecordsRow.querySelector('td').textContent = 'Error loading data. Please try again.';
            noRecordsRow.classList.remove('hidden'); 
            tableBody.innerHTML = ''; 
        }
    }

    // --- EVENT LISTENERS ---
    // Visitor card (unchanged)
    if (buttons.length > 0 && label && count) {
        buttons.forEach(btn => {
            btn.addEventListener('click', () => {
                const period = btn.dataset.period;
                const visitorCount = btn.dataset.count;
                label.textContent = `This ${period}`;
                count.textContent = visitorCount;
                buttons.forEach(b => b.classList.remove('bg-[var(--color-popover)]', 'font-medium'));
                btn.classList.add('bg-[var(--color-popover)]', 'font-medium');
            });
        });
    }

    // Walang debounce sa search input para mabilis ang response
    searchInput.addEventListener("input", () => { 
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            currentSearch = searchInput.value.trim(); 
            // WALANG LOADING MODAL SA SEARCH
            fetchLogs(false); 
        }, 300); // Gumamit ng 300ms debounce para hindi masyadong madalas mag-fetch
    }); 
    
    // WALANG LOADING MODAL SA DATE PICKER
    dateInput.addEventListener("change", () => { 
        currentDate = dateInput.value; 
        fetchLogs(false); 
    }); 

    // Pagination buttons
    if (prevPageBtn) {
        prevPageBtn.addEventListener('click', e => {
            e.preventDefault();
            if (currentPage > 1) {
                currentPage--;
                renderTable();
                renderPagination();
            }
        });
    }

    if (nextPageBtn) {
        nextPageBtn.addEventListener('click', e => {
            e.preventDefault();
            const pageCount = Math.ceil(currentGroupedLogs.length / rowsPerPage);
            if (currentPage < pageCount) {
                currentPage++;
                renderTable();
                renderPagination();
            }
        });
    }

    // Modal (unchanged)
    function closeCheckinsModal() {
        if (checkinsModal) {
            checkinsModal.classList.add("hidden");
            document.body.classList.remove("overflow-hidden");
        }
    }
    [closeCheckinsBtn1, closeCheckinsBtn2].forEach(btn => btn?.addEventListener("click", closeCheckinsModal));
    checkinsModal?.addEventListener("click", e => { if (e.target === checkinsModal) closeCheckinsModal(); });

    tableBody.addEventListener("click", (e) => {
        const viewBtn = e.target.closest(".viewCheckinsBtn");
        if (viewBtn) {
            try {
                const studentName = viewBtn.dataset.studentName;
                const date = viewBtn.dataset.date;
                const checkins = JSON.parse(viewBtn.dataset.checkins);
                checkinsModalTitle.textContent = `Check-ins for: ${studentName}`;
                checkinsModalSubtitle.textContent = `Date: ${date}`;
                checkinsList.innerHTML = '';
                if (checkins && checkins.length > 0) {
                    checkins.forEach((time, index) => {
                        checkinsList.innerHTML += `<div class="flex justify-between items-center bg-orange-50 border border-orange-200 px-3 py-2 rounded-lg"><p class="font-medium text-gray-800 text-sm">Check-in #${index + 1}</p><span class="text-sm font-semibold text-orange-700">${time}</span></div>`;
                    });
                } else {
                    checkinsList.innerHTML = `<p class="text-gray-500 text-sm text-center">No individual check-in times recorded.</p>`;
                }
                checkinsModal.classList.remove("hidden");
                document.body.classList.add("overflow-hidden");
            } catch (parseError) {
                console.error("Error opening modal, could not parse check-in data:", parseError);
                alert("Error: Could not display check-in details.");
            }
        }
    });

    // --- INITIALIZATION ---
    currentDate = formatDate(getPhDate());
    dateInput.value = currentDate;
    // Initial load (default: may loading modal)
    fetchLogs(); 
}
