document.addEventListener('DOMContentLoaded', function () {
    const returnModal = document.getElementById('return-modal');
    const closeButton = document.getElementById('modal-close-button');
    const cancelButton = document.getElementById('modal-cancel-button');
    const modalReturnButton = document.getElementById('modal-return-button');
    const modalExtendButton = document.getElementById('modal-extend-button');
    const availableBookModal = document.getElementById('available-book-modal');
    const availableModalCloseButton = document.getElementById('available-modal-close-button');
    const availableModalCloseAction = document.getElementById('available-modal-close-action');
    const accessionInput = document.getElementById('accession-input');
    const scanButton = document.getElementById('scan-button');
    const qrCodeValueInput = document.getElementById('qrCodeValue');
    const recentReturnsFeed = document.getElementById('recent-returns-feed');

    const closeReturnModal = () => { if (returnModal) returnModal.classList.add('hidden'); };
    const closeAvailableModal = () => { if (availableBookModal) availableBookModal.classList.add('hidden'); };

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

    function showErrorToast(title, body = "An error occurred.") {
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

    async function fetchRecentReturns() {
        try {
            const response = await fetch('api/admin/returning/getRecent?limit=10');
            const result = await response.json();
            if (result.success) {
                renderRecentReturns(result.list);
            }
        } catch (error) {
            console.error('Error:', error);
        }
    }

    function renderRecentReturns(list) {
        if (!recentReturnsFeed) return;
        if (!list || list.length === 0) {
            recentReturnsFeed.innerHTML = `<tr><td colspan="5" class="py-10 text-center text-gray-400 italic">No returns processed yet.</td></tr>`;
            return;
        }

        recentReturnsFeed.innerHTML = list.map(item => `
            <tr class="hover:bg-gray-50 transition-colors">
                <td class="px-4 py-4">
                    <div class="flex flex-col">
                        <span class="font-bold text-gray-800 text-base leading-tight">${item.first_name} ${item.last_name}</span>
                        <span class="text-[14px] text-orange-600 font-mono font-black uppercase tracking-tight mt-0.5">${item.identifier || 'N/A'}</span>
                    </div>
                </td>
                <td class="px-4 py-4 text-center text-sm text-gray-700 font-bold">${item.year_section || 'N/A'}</td>
                <td class="px-4 py-4 text-center text-sm text-gray-600 font-mono font-black">${item.accession_number || 'N/A'}</td>
                <td class="px-4 py-4 text-gray-800 text-sm font-medium truncate max-w-[200px]" title="${item.item_title}">${item.item_title}</td>
                <td class="px-4 py-4 text-right text-orange-700 text-base font-black font-mono">${formatTime(item.returned_at)}</td>
            </tr>
        `).join('');
    }

    function formatTime(timestamp) {
        const date = new Date(timestamp);
        return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }

    async function handleBookCheck(accessionNumber) {
        if (!accessionNumber) return;
        const formData = new FormData();
        formData.append('accession_number', accessionNumber);

        try {
            const response = await fetch('api/admin/returning/checkBook', { method: 'POST', body: formData });
            const result = await response.json();
            if (result.success) {
                if (result.data.status === 'borrowed') openReturnModal(result.data.details);
                else if (result.data.status === 'available') openAvailableModal(result.data.details);
            }
        } catch (error) { console.error('Error:', error); }
        accessionInput.value = '';
        qrCodeValueInput.value = '';
        qrCodeValueInput.focus();
    }

    function openReturnModal(data) {
        document.getElementById('modal-book-title').textContent = data.title;
        document.getElementById('modal-book-author').textContent = data.author || '';
        document.getElementById('modal-book-accessionnumber').textContent = data.accession_number || data.title;
        document.getElementById('modal-book-callnumber').textContent = data.call_number || 'N/A';
        document.getElementById('modal-borrower-name').textContent = data.borrower_name;
        document.getElementById('modal-student-id').textContent = data.id_number;
        document.getElementById('modal-borrower-course').textContent = data.course_or_department;
        document.getElementById('modal-borrower-year-section').textContent = data.student_year_section;
        document.getElementById('modal-due-date').textContent = data.due_date;
        
        const statusEl = document.getElementById('modal-book-status');
        if (statusEl) {
            const status = data.availability.toUpperCase();
            statusEl.textContent = status;
            statusEl.className = status === 'OVERDUE' 
                ? 'bg-red-200 text-red-800 text-xs font-semibold px-3 py-1 rounded-full'
                : 'bg-orange-200 text-orange-800 text-xs font-semibold px-3 py-1 rounded-full';
        }

        modalReturnButton.dataset.borrowingId = data.borrowing_id;
        if (modalExtendButton) modalExtendButton.dataset.borrowingId = data.borrowing_id;
        returnModal.classList.remove('hidden');
    }

    function openAvailableModal(data) {
        document.getElementById('available-modal-title').textContent = data.title;
        document.getElementById('available-modal-author').textContent = data.author || '';
        document.getElementById('available-modal-accession').textContent = data.accession_number || 'N/A';
        document.getElementById('available-modal-call-number').textContent = data.call_number || 'N/A';
        document.getElementById('available-modal-isbn').textContent = data.book_isbn || 'N/A';
        document.getElementById('available-modal-publisher').textContent = data.book_publisher || 'N/A';
        document.getElementById('available-modal-year').textContent = data.year || 'N/A';
        document.getElementById('available-modal-edition').textContent = data.book_edition || 'N/A';
        document.getElementById('available-modal-subject').textContent = data.subject || 'N/A';
        document.getElementById('available-modal-description').textContent = data.description || 'No description available.';

        const img = document.getElementById('available-modal-img');
        const placeholder = document.getElementById('available-modal-img-placeholder');
        if (data.book_image && img) {
            img.src = 'public/uploads/books-img/' + data.book_image;
            img.classList.remove('hidden');
            placeholder.classList.add('hidden');
        } else if (img) {
            img.classList.add('hidden');
            placeholder.classList.remove('hidden');
        }

        availableBookModal.classList.remove('hidden');
    }

    if (modalReturnButton) {
        modalReturnButton.addEventListener('click', async () => {
            const id = modalReturnButton.dataset.borrowingId;
            const formData = new FormData();
            formData.append('borrowing_id', id);
            try {
                const response = await fetch('api/admin/returning/markReturned', { method: 'POST', body: formData });
                const result = await response.json();
                if (result.success) {
                    closeReturnModal();
                    showSuccessToast('Item Returned', result.message);
                    renderRecentReturns(result.recent);
                } else {
                    showErrorToast('Return Failed', result.message);
                }
            } catch (error) { showErrorToast('Error', 'Could not process return.'); }
        });
    }

    if (modalExtendButton) {
        modalExtendButton.addEventListener('click', async () => {
            const id = modalExtendButton.dataset.borrowingId;
            const { value: days } = await Swal.fire({
                title: 'Extend Due Date',
                input: 'number',
                inputLabel: 'Days to extend',
                inputValue: 7,
                showCancelButton: true,
                confirmButtonText: 'Extend',
                customClass: {
                    popup: "!rounded-xl !shadow-md !p-6 !bg-gradient-to-b !from-[#fffdfb] !to-[#fff6ef] !border-2 !border-orange-400",
                    confirmButton: "!bg-orange-600 !text-white !px-5 !py-2.5 !rounded-lg",
                    cancelButton: "!bg-gray-200 !text-gray-800 !px-5 !py-2.5 !rounded-lg"
                }
            });
            if (days) {
                const formData = new FormData();
                formData.append('borrowing_id', id);
                formData.append('days', days);
                const response = await fetch('api/admin/returning/extend', { method: 'POST', body: formData });
                const result = await response.json();
                if (result.success) {
                    document.getElementById('modal-due-date').textContent = result.new_due_date;
                    showSuccessToast('Due Date Extended', `New due date: ${result.new_due_date}`);
                } else {
                    showErrorToast('Extension Failed', result.message);
                }
            }
        });
    }

    if (scanButton) scanButton.addEventListener('click', () => handleBookCheck(accessionInput.value.trim()));
    if (accessionInput) accessionInput.addEventListener('keydown', e => { if (e.key === 'Enter') handleBookCheck(accessionInput.value.trim()); });
    
    if (qrCodeValueInput) {
        qrCodeValueInput.addEventListener('input', () => {
            const val = qrCodeValueInput.value.trim();
            if (val) {
                handleBookCheck(val);
                qrCodeValueInput.value = '';
            }
        });
    }

    closeButton?.addEventListener('click', closeReturnModal);
    cancelButton?.addEventListener('click', closeReturnModal);
    availableModalCloseButton?.addEventListener('click', closeAvailableModal);
    availableModalCloseAction?.addEventListener('click', closeAvailableModal);

    fetchRecentReturns();
});
