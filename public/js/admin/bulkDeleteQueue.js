document.addEventListener("DOMContentLoaded", () => {
    const queueTableBody = document.getElementById("queueTableBody");
    const refreshQueueBtn = document.getElementById("refreshQueueBtn");
    
    // Modal Elements
    const detailsModal = document.getElementById("requestDetailsModal");
    const detailsModalContent = document.getElementById("requestDetailsModalContent");
    const closeRequestDetails = document.getElementById("closeRequestDetails");
    
    // Detail Elements
    const detailRequestId = document.getElementById("detailRequestId");
    const detailRequesterInfo = document.getElementById("detailRequesterInfo");
    const detailReason = document.getElementById("detailReason");
    const detailItemCount = document.getElementById("detailItemCount");
    const detailItemsTableBody = document.getElementById("detailItemsTableBody");
    
    // Action Buttons
    const approveBtn = document.getElementById("approveBtn");
    const rejectBtn = document.getElementById("rejectBtn");

    let currentRequestId = null;

    const fetchQueue = async () => {
        queueTableBody.innerHTML = `
            <tr>
                <td colspan="6" class="py-12 text-center text-gray-400">
                    <i class="ph ph-circle-notched animate-spin text-3xl mb-2"></i>
                    <p class="text-sm">Fetching pending deletion requests...</p>
                </td>
            </tr>
        `;

        try {
            const res = await fetch(`${BASE_URL_JS}/api/bulk-delete/pending`);
            const data = await res.json();

            if (data.success) {
                renderQueue(data.requests);
            } else {
                throw new Error(data.message);
            }
        } catch (error) {
            queueTableBody.innerHTML = `
                <tr>
                    <td colspan="6" class="py-12 text-center text-red-500">
                        <i class="ph ph-warning-circle text-3xl mb-2"></i>
                        <p class="text-sm font-bold">Failed to load queue</p>
                        <p class="text-xs opacity-70">${error.message}</p>
                    </td>
                </tr>
            `;
        }
    };

    const renderQueue = (requests) => {
        if (!requests || requests.length === 0) {
            queueTableBody.innerHTML = `
                <tr>
                    <td colspan="6" class="py-16 text-center text-gray-400">
                        <div class="flex flex-col items-center justify-center opacity-40">
                            <i class="ph ph-stack text-5xl mb-3"></i>
                            <p class="text-sm font-bold">No pending requests found.</p>
                            <p class="text-xs font-medium">The approval queue is currently empty.</p>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }

        queueTableBody.innerHTML = requests.map(req => `
            <tr class="hover:bg-gray-50/50 transition-colors group">
                <td class="py-4 px-6 font-mono font-bold text-orange-600">#${req.request_id}</td>
                <td class="py-4 px-6">
                    <span class="bg-gray-100 text-gray-700 px-2 py-1 rounded text-[10px] font-black uppercase tracking-wider">
                        ${req.campus_name}
                    </span>
                </td>
                <td class="py-4 px-6">
                    <div class="flex flex-col">
                        <span class="font-bold text-gray-800 text-sm">${req.first_name} ${req.last_name}</span>
                        <span class="text-[10px] text-gray-400 font-bold uppercase tracking-widest leading-none mt-0.5">${req.requester_role}</span>
                    </div>
                </td>
                <td class="py-4 px-6 text-center">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-orange-50 text-orange-700 rounded-full text-xs font-black">
                        <i class="ph ph-users-three"></i>
                        ${req.total_items} Users
                    </span>
                </td>
                <td class="py-4 px-6 text-gray-500 text-xs">
                    <div class="flex flex-col">
                        <span class="font-bold text-gray-700">${new Date(req.requested_at).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' })}</span>
                        <span class="opacity-60 font-medium">${new Date(req.requested_at).toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' })}</span>
                    </div>
                </td>
                <td class="py-4 px-6 text-right">
                    <button onclick="viewRequestDetails(${req.request_id})" class="inline-flex items-center gap-2 px-4 py-2 text-orange-600 hover:bg-orange-600 hover:text-white border border-orange-200 rounded-xl font-bold text-xs transition-all shadow-sm">
                        <i class="ph ph-magnifying-glass-plus text-base"></i>
                        Review Batch
                    </button>
                </td>
            </tr>
        `).join('');
    };

    window.viewRequestDetails = async (id) => {
        currentRequestId = id;
        try {
            const res = await fetch(`${BASE_URL_JS}/api/bulk-delete/get/${id}`);
            const data = await res.json();

            if (data.success) {
                const req = data.request;
                detailRequestId.textContent = req.request_id;
                detailRequesterInfo.textContent = `BY ${req.first_name} ${req.last_name} • ${req.campus_name}`;
                detailReason.textContent = req.reason || "The requester did not provide a specific reason for this deletion.";
                detailItemCount.textContent = req.total_items;
                
                detailItemsTableBody.innerHTML = req.items.map(item => `
                    <tr class="hover:bg-gray-50/50 transition-colors text-[11px]">
                        <td class="py-2.5 px-4 font-mono text-orange-600 font-bold whitespace-nowrap">
                            ${item.identifier || 'N/A'}
                        </td>
                        <td class="py-2.5 px-4 text-gray-800 font-bold truncate max-w-[180px]">
                            ${item.item_name || 'System User'}
                        </td>
                        <td class="py-2.5 px-4 text-center">
                            <span class="bg-blue-50 text-blue-700 px-2 py-0.5 rounded text-[10px] font-black uppercase whitespace-nowrap">
                                ${item.target_campus || 'N/A'}
                            </span>
                        </td>
                        <td class="py-2.5 px-4 text-center">
                            ${item.target_deleted_at 
                                ? `<span class="bg-red-50 text-red-600 px-2 py-0.5 rounded text-[10px] font-black uppercase flex items-center justify-center gap-1">
                                    <i class="ph ph-user-minus"></i> Deactivated
                                   </span>`
                                : `<span class="bg-green-50 text-green-600 px-2 py-0.5 rounded text-[10px] font-black uppercase flex items-center justify-center gap-1">
                                    <i class="ph ph-user-check"></i> Active
                                   </span>`
                            }
                        </td>
                    </tr>
                `).join('');

                openModal();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Fetch Failed',
                    text: data.message,
                    confirmButtonColor: '#ea580c',
                    customClass: { popup: 'rounded-3xl border-none' }
                });
            }
        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'Network Error',
                text: 'Failed to connect to the server. Please try again.',
                confirmButtonColor: '#ea580c',
                customClass: { popup: 'rounded-3xl border-none' }
            });
        }
    };

    const openModal = () => {
        detailsModal.classList.remove("hidden");
        document.body.style.overflow = 'hidden';
        setTimeout(() => {
            detailsModal.classList.add("opacity-100");
            detailsModalContent.classList.remove("scale-95");
            detailsModalContent.classList.add("scale-100");
        }, 10);
    };

    const closeModal = () => {
        detailsModal.classList.remove("opacity-100");
        detailsModalContent.classList.remove("scale-100");
        detailsModalContent.classList.add("scale-95");
        document.body.style.overflow = '';
        setTimeout(() => {
            detailsModal.classList.add("hidden");
        }, 300);
    };

    closeRequestDetails.addEventListener("click", closeModal);
    
    // Approval
    approveBtn.addEventListener("click", async () => {
        const result = await Swal.fire({
            title: "Confirm Batch Deactivation?",
            text: "This will immediately disable all listed user accounts. They will lose access to library services instantly.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#16a34a",
            cancelButtonColor: "#64748b",
            confirmButtonText: "Yes, Approve & Execute",
            cancelButtonText: "Cancel",
            customClass: {
                popup: 'rounded-3xl border-none',
                confirmButton: 'rounded-xl px-6 py-3 font-bold',
                cancelButton: 'rounded-xl px-6 py-3 font-bold'
            }
        });

        if (result.isConfirmed) {
            try {
                approveBtn.disabled = true;
                const originalHtml = approveBtn.innerHTML;
                approveBtn.innerHTML = `<i class="ph ph-spinner animate-spin"></i> Processing...`;

                const res = await fetch(`${BASE_URL_JS}/api/bulk-delete/approve`, {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ request_id: currentRequestId })
                });

                const data = await res.json();
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: data.message,
                        confirmButtonColor: '#16a34a',
                        customClass: { popup: 'rounded-3xl border-none' }
                    });
                    closeModal();
                    fetchQueue();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Execution Failed',
                        text: data.message,
                        confirmButtonColor: '#ef4444',
                        customClass: { popup: 'rounded-3xl border-none' }
                    });
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Server Error',
                    text: 'A critical error occurred while processing the request.',
                    confirmButtonColor: '#ef4444'
                });
            } finally {
                approveBtn.disabled = false;
                approveBtn.innerHTML = `<i class="ph ph-check-circle text-lg"></i> Approve & Process Batch`;
            }
        }
    });

    // Rejection
    rejectBtn.addEventListener("click", async () => {
        const result = await Swal.fire({
            title: "Reject Request?",
            text: "The requester will be notified that this deletion batch was not approved.",
            icon: "question",
            showCancelButton: true,
            confirmButtonColor: "#ef4444",
            cancelButtonColor: "#64748b",
            confirmButtonText: "Yes, Reject Batch",
            cancelButtonText: "Cancel",
            customClass: {
                popup: 'rounded-3xl border-none',
                confirmButton: 'rounded-xl px-6 py-3 font-bold',
                cancelButton: 'rounded-xl px-6 py-3 font-bold'
            }
        });

        if (result.isConfirmed) {
            try {
                rejectBtn.disabled = true;
                rejectBtn.innerHTML = `<i class="ph ph-spinner animate-spin"></i> Rejecting...`;

                const res = await fetch(`${BASE_URL_JS}/api/bulk-delete/reject`, {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ request_id: currentRequestId })
                });

                const data = await res.json();
                if (data.success) {
                    Swal.fire({
                        icon: 'info',
                        title: 'Batch Rejected',
                        text: data.message,
                        confirmButtonColor: '#64748b',
                        customClass: { popup: 'rounded-3xl border-none' }
                    });
                    closeModal();
                    fetchQueue();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Action Failed',
                        text: data.message,
                        confirmButtonColor: '#ef4444',
                        customClass: { popup: 'rounded-3xl border-none' }
                    });
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Server Error',
                    text: 'Could not communicate with the server.',
                    confirmButtonColor: '#ef4444'
                });
            } finally {
                rejectBtn.disabled = false;
                rejectBtn.innerHTML = `<i class="ph ph-x-circle text-lg"></i> Reject Request`;
            }
        }
    });

    refreshQueueBtn.addEventListener("click", fetchQueue);

    // Initial load
    fetchQueue();
});
