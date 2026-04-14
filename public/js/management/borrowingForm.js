/**
 * Unified Borrowing Form JS
 * Consolidated logic for manual borrowing across roles.
 */

// --- CORE CONFIRMATION FUNCTION ---
async function showCustomConfirmationModal(title, text, confirmText = "Confirm") {
    if (typeof Swal == "undefined") return confirm(title);
    const result = await Swal.fire({
        background: "transparent",
        buttonsStyling: false,
        width: "450px",
        html: `
            <div class="flex flex-col text-center">
                <div class="flex justify-center mb-3">
                    <div class="flex items-center justify-center w-16 h-16 rounded-full bg-orange-100 text-orange-600">     
                        <i class="ph ph-warning-circle text-3xl"></i>
                    </div>
                </div>
                <h3 class="text-xl font-semibold text-gray-800">${title}</h3>
                <p class="text-[14px] text-gray-700 mt-1">${text}</p>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: confirmText,
        cancelButtonText: "Cancel",
        customClass: {
            popup: "!rounded-xl !shadow-lg !p-6 !bg-white !border-2 !border-orange-500 !border-solid",
            confirmButton: "!bg-orange-600 !text-white !px-5 !py-2.5 !rounded-lg hover:!bg-orange-700 !mx-2 !font-semibold !text-base",
            cancelButton: "!bg-gray-200 !text-gray-800 !px-5 !py-2.5 !rounded-lg hover:!bg-gray-300 !mx-2 !font-semibold !text-base",
            actions: "!mt-4",
        },
    });
    return result.isConfirmed;
}

const showBorrowingToast = (isSuccess, title, text, duration = 3000) => {
    const iconClass = isSuccess ? "ph-check-circle" : "ph-x-circle";
    const contentColor = isSuccess ? "text-green-600" : "text-red-600";
    const bgColor = isSuccess ? "bg-green-100" : "bg-red-100";
    const borderColor = isSuccess ? "#22c55e" : "#dc2626";
    const inlineStyle = `border: 2px solid ${borderColor} !important; box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);`;

    Swal.fire({
        toast: true,
        position: "bottom-end",
        showConfirmButton: false,
        timer: duration,
        width: "360px",
        background: "white",
        backdrop: `transparent`,
        customClass: { popup: `!rounded-xl !p-4 backdrop-blur-sm` },
        html: `
            <div class="flex flex-col text-left">
                <div class="flex items-center gap-3 mb-2">
                    <div class="flex items-center justify-center w-10 h-10 rounded-full ${bgColor} ${contentColor}">        
                        <i class="ph ${iconClass} text-lg"></i>
                    </div>
                    <div>
                        <h3 class="text-[15px] font-semibold ${contentColor}">${title}</h3>
                        <p class="text-[13px] text-gray-700 mt-0.5">${text}</p>
                    </div>
                </div>
            </div>
        `,
        didOpen: (toast) => {
            toast.style.cssText = inlineStyle + " " + toast.style.cssText;
        },
    });
};

const showFinalBorrowingModal = (isSuccess, title, message) => {
    if (typeof Swal == "undefined") return alert(`${title}: ${message}`);

    const duration = 3000;
    let timerInterval;

    const theme = isSuccess
        ? {
            bg: "bg-green-50",
            border: "border-green-300",
            text: "text-green-700",
            iconBg: "bg-green-100",
            iconColor: "text-green-600",
            iconClass: "ph-check-circle",
            progressBarColor: "bg-green-500",
        }
        : {
            bg: "bg-red-50",
            border: "border-red-300",
            text: "text-red-700",
            iconBg: "bg-red-100",
            iconColor: "text-red-600",
            iconClass: "ph-x-circle",
            progressBarColor: "bg-red-500",
        };

    Swal.fire({
        showConfirmButton: false,
        showCancelButton: false,
        buttonsStyling: false,
        width: "450px",
        backdrop: `rgba(0,0,0,0.3) backdrop-filter: blur(6px)`,
        timer: duration,
        didOpen: () => {
            const progressBar = Swal.getHtmlContainer().querySelector("#progress-bar");
            let width = 100;
            timerInterval = setInterval(() => {
                width -= 100 / (duration / 100);
                if (progressBar) progressBar.style.width = width + "%";
            }, 100);
        },
        willClose: () => clearInterval(timerInterval),
        html: `
            <div class="w-full ${theme.bg} border-2 ${theme.border} rounded-2xl p-8 shadow-xl text-center">
                <div class="flex items-center justify-center w-16 h-16 rounded-full ${theme.iconBg} mx-auto mb-4">
                    <i class="ph ${theme.iconClass} ${theme.iconColor} text-3xl"></i>
                </div>
                <h3 class="text-2xl font-bold ${theme.text}">${title}</h3>
                <p class="text-base ${theme.text} mt-3 mb-4">${message}</p>
                <div class="w-full bg-gray-200 h-2 rounded mt-4 overflow-hidden">
                    <div id="progress-bar" class="${theme.progressBarColor} h-2 w-full transition-all duration-100 ease-linear"></div>
                </div>
            </div>
        `,
        customClass: {
            popup: "!block !bg-transparent !shadow-none !p-0 !border-0 !w-auto !min-w-0 !max-w-none",
        },
    });
};

const showLoadingModal = (message = "Processing request...", subMessage = "Please wait.") => {
    if (typeof Swal == "undefined") return;
    Swal.fire({
        background: "transparent",
        html: `
            <div class="flex flex-col items-center justify-center gap-3">
                <div class="animate-spin rounded-full h-12 w-12 border-4 border-orange-200 border-t-orange-600"></div>
                <p class="text-gray-700 text-[15px] font-semibold">${message}</p>
                <span class="text-[13px] text-gray-500">${subMessage}</span>
            </div>
        `,
        allowOutsideClick: false,
        showConfirmButton: false,
        customClass: {
            popup: "!w-64 !rounded-xl !shadow-md !border-2 !border-orange-400 !p-7 !bg-gradient-to-b !from-[#fffdfb] !to-[#fff6ef] shadow-[0_0_9px_#ffb34770]"
        },
    });
};

document.addEventListener('DOMContentLoaded', () => {

    const setupDropdown = (btnId, menuId, valueId, inputId, itemClass, callback) => {
        const dropdownBtn = document.getElementById(btnId);
        const dropdownMenu = document.getElementById(menuId);
        const dropdownValue = document.getElementById(valueId);
        const hiddenInput = document.getElementById(inputId);
        if (!dropdownBtn || !dropdownMenu || !dropdownValue || !hiddenInput) return;

        dropdownBtn.addEventListener('click', (e) => {
            e.preventDefault(); e.stopPropagation();
            document.querySelectorAll('.absolute.z-10, .absolute.z-20').forEach(menu => {
                if (menu.id !== menuId) menu.classList.add('hidden');
            });
            dropdownMenu.classList.toggle('hidden');
        });

        dropdownMenu.addEventListener('click', (e) => {
            const target = e.target.closest(`.${itemClass}`);
            if (target) {
                const val = target.dataset.value;
                const name = target.textContent.trim();
                dropdownValue.textContent = name;
                hiddenInput.value = val;
                dropdownMenu.classList.add('hidden');
                if (callback) callback(val);
            }
        });
    };

    const itemIcon = document.getElementById('item_icon');
    const itemNameWrapper = document.getElementById('item_name_wrapper');
    const accessionWrapper = document.getElementById('accession_number_wrapper');
    const bookTitleWrapper = document.getElementById('book_title_wrapper');

    const handleItemTypeChange = (type) => {
        if (!itemIcon) return;
        if (type === 'Book') {
            itemIcon.className = 'ph ph-book-open text-3xl text-emerald-600';
            if (itemNameWrapper) itemNameWrapper.style.display = 'none';
            if (accessionWrapper) accessionWrapper.style.display = 'block';
            if (bookTitleWrapper) bookTitleWrapper.style.display = 'block';
        } else {
            itemIcon.className = 'ph ph-desktop text-3xl text-emerald-600';
            if (itemNameWrapper) itemNameWrapper.style.display = 'block';
            if (accessionWrapper) accessionWrapper.style.display = 'none';
            if (bookTitleWrapper) bookTitleWrapper.style.display = 'none';
        }
    };

    setupDropdown('itemTypeDropdownBtn', 'itemTypeDropdownMenu', 'itemTypeDropdownValue', 'item_type', 'item-type-item', handleItemTypeChange);
    setupDropdown('roleDropdownBtn', 'roleDropdownMenu', 'roleDropdownValue', 'role', 'role-item');
    setupDropdown('collateralDropdownBtn', 'collateralDropdownMenu', 'collateralDropdownValue', 'collateral_id_hidden', 'collateral-item');
    setupDropdown('equipmentDropdownBtn', 'equipmentDropdownMenu', 'equipmentDropdownValue', 'equipment_id_hidden', 'equipment-item');

    const fetchData = async (endpoint, listId, idKey, nameKey, itemClass) => {
        const list = document.getElementById(listId);
        if (!list) return;

        try {
            const res = await fetch(`${BORROWING_API_BASE}/${endpoint}`);
            const data = await res.json();
            list.innerHTML = "";
            const items = data.list || data; // Fallback if data is already the list
            if (Array.isArray(items)) {
                items.forEach(item => {
                    const li = document.createElement('li');
                    li.innerHTML = `<button type="button" class="${itemClass} w-full text-left px-4 py-2 text-sm hover:bg-amber-50" data-value="${item[idKey]}">${item[nameKey]}</button>`;
                    list.appendChild(li);
                });
            }
        } catch (err) { console.error("Fetch failed:", err); }
    };

    fetchData('getCollaterals', 'collateral-list', 'collateral_id', 'name', 'collateral-item');
    fetchData('getEquipments', 'equipment-list', 'equipment_id', 'equipment_name', 'equipment-item');

    document.getElementById('clear-btn').addEventListener('click', () => {
        const form = document.getElementById('main-borrow-form');
        form.reset();
        document.getElementById('roleDropdownValue').textContent = 'Select Role';
        document.getElementById('itemTypeDropdownValue').textContent = 'Equipment';
        document.getElementById('collateralDropdownValue').textContent = 'Select Collateral';
        document.getElementById('equipmentDropdownValue').textContent = 'Select Equipment';
        document.getElementById('role').value = '';
        document.getElementById('item_type').value = 'Equipment';
        document.getElementById('collateral_id_hidden').value = '';
        document.getElementById('equipment_id_hidden').value = '';
        handleItemTypeChange('Equipment');
        showBorrowingToast(true, 'Form Cleared', 'Borrower and Item fields have been reset.');
    });

    document.getElementById('check-btn').addEventListener('click', async () => {
        const userId = document.getElementById('input_user_id').value.trim();
        if (!userId) return showBorrowingToast(false, 'Input Required', 'Please enter a **User ID**.');
        showLoadingModal('Checking User...', 'Verifying User ID.');
        try {
            const res = await fetch(`${BORROWING_API_BASE}/checkUser`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ input_user_id: userId })
            });
            const data = await res.json();
            Swal.close();
            if (data.exists) {
                if (await showCustomConfirmationModal('User ID Exists', 'Fill the form with existing data?', 'Yes, Fill Fields')) {
                    document.querySelector('input[name="first_name"]').value = data.data.first_name;
                    document.querySelector('input[name="middle_name"]').value = data.data.middle_name || '';
                    document.querySelector('input[name="last_name"]').value = data.data.last_name;
                    document.querySelector('input[name="suffix"]').value = data.data.suffix || '';
                    document.querySelector('input[name="email"]').value = data.data.email || '';
                    document.querySelector('input[name="contact"]').value = data.data.contact || '';
                    const displayRole = data.data.role.charAt(0).toUpperCase() + data.data.role.slice(1);
                    document.getElementById('roleDropdownValue').textContent = displayRole;
                    document.getElementById('role').value = displayRole;
                }
            } else showBorrowingToast(false, 'Not Found', 'User ID not found. Use Guest Mode.');
        } catch { Swal.close(); showBorrowingToast(false, 'Error', 'Unexpected error occurred.'); }
    });

    document.getElementById('main-borrow-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);

        if (!document.getElementById('collateral_id_hidden').value) return showBorrowingToast(false, 'Invalid Collateral', 'Mangyaring pumili ng collateral mula sa listahan.');
        if (formData.get('item_type') === 'Equipment' && !document.getElementById('equipment_id_hidden').value) return showBorrowingToast(false, 'Invalid Equipment', 'Mangyaring pumili ng kagamitan mula sa listahan.');

        const confirmed = await showCustomConfirmationModal(
            "Confirm Transaction?",
            "Are you sure you want to process this borrowing request?",
            "Yes, Process It!"
        );

        if (confirmed) {
            showLoadingModal('Submitting...', 'Processing transaction.');
            if (!formData.get('role')) formData.set('role', '');
            if (!formData.get('equipment_type')) formData.set('equipment_type', formData.get('item_type'));
            try {
                const res = await fetch(`${BORROWING_API_BASE}/create`, { method: 'POST', body: formData });
                const data = await res.json();
                Swal.close();
                if (data.success) {
                    showFinalBorrowingModal(true, 'Success!', 'Item borrowed successfully.');
                    form.reset();
                    // Reset dropdowns
                    document.getElementById('roleDropdownValue').textContent = 'Select Role';
                    document.getElementById('itemTypeDropdownValue').textContent = 'Equipment';
                    document.getElementById('collateralDropdownValue').textContent = 'Select Collateral';
                    document.getElementById('equipmentDropdownValue').textContent = 'Select Equipment';
                    handleItemTypeChange('Equipment');
                } else {
                    showFinalBorrowingModal(false, 'Failed', data.message || 'Check fields.');
                }
            } catch { Swal.close(); showBorrowingToast(false, 'Error', 'Submission failed.'); }
        }
    });

    document.addEventListener('click', (e) => {
        document.querySelectorAll('.absolute.z-10, .absolute.z-20').forEach(menu => {
            if (!menu.contains(e.target)) menu.classList.add('hidden');
        });
    });
});