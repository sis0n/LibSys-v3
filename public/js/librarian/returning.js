document.addEventListener('DOMContentLoaded', function () {
    const returnModal = document.getElementById('return-modal');
    const closeButton = document.getElementById('modal-close-button');
    const cancelButton = document.getElementById('modal-cancel-button');
    const modalReturnButton = document.getElementById('modal-return-button');
    const availableBookModal = document.getElementById('available-book-modal');
    const availableModalCloseButton = document.getElementById('available-modal-close-button');
    const availableModalCloseAction = document.getElementById('available-modal-close-action');
    const accessionInput = document.getElementById('accession-input');
    const scanButton = document.getElementById('scan-button');
    const qrCodeValueInput = document.getElementById('qrCodeValue');
    const recentReturnsFeed = document.getElementById('recent-returns-feed');

    const closeReturnModal = () => { if (returnModal) returnModal.classList.add('hidden'); };
    const closeAvailableModal = () => { if (availableBookModal) availableBookModal.classList.add('hidden'); };

    async function fetchRecentReturns() {
        try {
            const response = await fetch('api/librarian/returning/getRecent?limit=10');
            const result = await response.json();
            if (result.success) {
                renderRecentReturns(result.list);
            }
        } catch (error) {
            console.error('Error fetching recent returns:', error);
        }
    }

    function renderRecentReturns(list) {
        if (!recentReturnsFeed) return;
        if (!list || list.length === 0) {
            recentReturnsFeed.innerHTML = `<tr><td colspan="3" class="px-6 py-8 text-center text-gray-400 italic">No returns processed yet.</td></tr>`;
            return;
        }

        recentReturnsFeed.innerHTML = list.map(item => `
            <tr class="hover:bg-gray-50 transition-colors">
                <td class="px-6 py-3">
                    <div class="flex flex-col">
                        <span class="font-bold text-gray-800 leading-tight">${item.first_name} ${item.last_name}</span>
                        <span class="text-[10px] text-orange-600 font-mono font-bold uppercase tracking-tighter">${item.identifier || 'N/A'}</span>
                    </div>
                </td>
                <td class="px-6 py-3 text-gray-600 truncate max-w-[250px]" title="${item.item_title}">${item.item_title}</td>
                <td class="px-6 py-3 text-right text-gray-400 text-xs font-mono">${formatTime(item.returned_at)}</td>
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
            const response = await fetch('api/librarian/returning/checkBook', { method: 'POST', body: formData });
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
        document.getElementById('modal-due-date').textContent = data.due_date;
        modalReturnButton.dataset.borrowingId = data.borrowing_id;
        returnModal.classList.remove('hidden');
    }

    function openAvailableModal(data) {
        document.getElementById('available-modal-title').textContent = data.title;
        document.getElementById('available-modal-author').textContent = data.author || '';
        availableBookModal.classList.remove('hidden');
    }

    if (modalReturnButton) {
        modalReturnButton.addEventListener('click', async () => {
            const id = modalReturnButton.dataset.borrowingId;
            const formData = new FormData();
            formData.append('borrowing_id', id);
            try {
                const response = await fetch('api/librarian/returning/markReturned', { method: 'POST', body: formData });
                const result = await response.json();
                if (result.success) {
                    closeReturnModal();
                    renderRecentReturns(result.recent);
                }
            } catch (error) { console.error('Error:', error); }
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
