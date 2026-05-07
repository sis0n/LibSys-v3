document.addEventListener('DOMContentLoaded', () => {
    if (typeof allAttendanceLogs === 'undefined') {
        console.error("myAttendance.js Error: Global variable 'allAttendanceLogs' not found.");
        const errorDiv = document.getElementById('attendance-error');
        if (errorDiv) {
            errorDiv.textContent = "Could not initialize attendance data.";
            errorDiv.classList.remove('hidden');
        }
        return;
    }

  const dateInput = document.getElementById('attendanceDate');
  const methodSelect = document.getElementById('attendanceMethod');
  const tableBody = document.getElementById('attendanceListContainer');
  const loadingRow = document.getElementById('tableLoadingRow');
  const noRecordsRow = document.getElementById('tableNoRecordsRow');
  const errorDiv = document.getElementById('attendance-error');

    function formatDate(dateString) {
        try {
            const localDate = new Date(dateString.replace(/-/g, '/'));
            if (isNaN(localDate)) return "Invalid Date";
            return localDate.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' });
        } catch (e) {
            console.error("Error formatting date:", dateString, e);
            return "Err Date";
        }
    }

    function formatDay(dateString) {
        try {
            const localDate = new Date(dateString.replace(/-/g, '/'));
            if (isNaN(localDate)) return "";
            return localDate.toLocaleDateString('en-US', { weekday: 'long' });
        } catch (e) {
            console.error("Error formatting day:", dateString, e);
            return "";
        }
    }

    function formatTime(dateString) {
        try {
            const localDate = new Date(dateString.replace(/-/g, '/'));
            if (isNaN(localDate)) return "";
            return localDate.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
        } catch (e) {
            console.error("Error formatting time:", dateString, e);
            return "";
        }
    }

    // GINAWA NATING SYNCHRONOUS ULIT ANG FUNCTION AT INALIS ANG SWEETALERT LOGIC
    function renderTable() {
        if (!tableBody || !loadingRow || !noRecordsRow || !dateInput || !methodSelect) {
            console.error("myAttendance.js Error: Required elements not found.");
            return;
        }

        const selectedDate = dateInput.value;
        const selectedMethod = methodSelect.value;

    tableBody.querySelectorAll(".attendance-card").forEach(card => card.remove());
    loadingRow.classList.add('hidden');
    noRecordsRow.classList.add('hidden');
    if (errorDiv) errorDiv.classList.add('hidden');

        if (!/^\d{4}-\d{2}-\d{2}$/.test(selectedDate)) {
            console.error("Invalid date format selected:", selectedDate);
            if (noRecordsRow) noRecordsRow.classList.remove('hidden');
            if (errorDiv) {
                errorDiv.textContent = "Invalid date selected.";
                errorDiv.classList.remove('hidden');
            }
            return;
        }

        let filteredLogs = allAttendanceLogs.filter(log => {
            return typeof log.timestamp === 'string' && log.timestamp.startsWith(selectedDate);
        });

        if (selectedMethod !== 'all') {
            filteredLogs = filteredLogs.filter(log => {
                return typeof log.method === 'string' && log.method.toLowerCase() === selectedMethod.toLowerCase();
            });
        }


        if (filteredLogs.length === 0) {
            noRecordsRow.classList.remove('hidden');
            const noRecordsCell = noRecordsRow.querySelector('td');
            if (noRecordsCell) noRecordsCell.innerHTML = `
                               <i class="ph ph-clipboard text-4xl block mb-2"></i>
                               No attendance records found for the selected criteria.
                             `;
            return;
        }

    const fragment = document.createDocumentFragment();
    filteredLogs.forEach(log => {
      const card = document.createElement("div");
      card.className = "attendance-card grid grid-cols-2 md:grid-cols-4 gap-x-4 gap-y-2 p-4 border-b border-orange-100 last:border-b-0 md:border-none md:p-3 md:px-4 md:items-center hover:bg-orange-50/50 transition-colors";

      card.innerHTML = `
            <!-- Mobile Label (hidden on md screens and up) -->
            <div class="text-xs text-gray-500 md:hidden">Date</div>
            <!-- Value -->
            <div class="font-medium text-gray-800 col-span-1 md:col-span-1">${formatDate(log.timestamp)}</div>

            <div class="text-xs text-gray-500 md:hidden">Day</div>
            <div class="text-gray-600 col-span-1 md:col-span-1">${formatDay(log.timestamp)}</div>

            <div class="text-xs text-gray-500 md:hidden">Check-in Time</div>
            <div class="font-medium text-gray-800 col-span-1 md:col-span-1">${formatTime(log.timestamp)}</div>

            <div class="text-xs text-gray-500 md:hidden">Method</div>
            <div class="text-gray-600 capitalize col-span-1 md:col-span-1">${log.method || 'N/A'}</div>
        `;
      fragment.appendChild(card);
    });
    tableBody.appendChild(fragment);
  }

    if (errorDiv && errorDiv.textContent.trim() && !errorDiv.classList.contains('hidden')) {
        if (loadingRow) loadingRow.classList.add('hidden');
    } else if (dateInput && methodSelect) {

        // Pag-alis ng loading sa initial render (first load)
        // Inalis ang `loadingRow.classList.remove('hidden');` sa labas ng renderTable
        
        renderTable(); 

        // Tiyakin na walang loading modal sa filters
        dateInput.addEventListener('change', renderTable);
        methodSelect.addEventListener('change', renderTable);
    } else {
        console.error("myAttendance.js Error: Date input or Method select element not found.");
        if (loadingRow) loadingRow.classList.add('hidden');
        if (errorDiv) {
            errorDiv.textContent = "Could not find the date or method selector.";
            errorDiv.classList.remove('hidden');
        }
    }
});