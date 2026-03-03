<?php
$items = $items ?? [];
$isBorrowed = $isBorrowed ?? false;
$isExpired = $isExpired ?? false;
$transaction_code = $transaction_code ?? null;

// Initial QR Path logic
$qrPath = null;
if ($transaction_code && !$isBorrowed && !$isExpired) {
    $qrPath = STORAGE_URL . "/uploads/qrcodes/" . $transaction_code . ".svg";
}
?>
<main class="min-h-screen">
    <h2 class="text-2xl font-bold mb-4">Faculty QR Borrowing Ticket</h2>
    <p class="text-gray-700">Your QR code for official book borrowing.</p>

    <div class="container mx-auto px-4 py-6 flex flex-col md:flex-row gap-6 justify-center items-stretch">
        <!-- QR Ticket Card -->
        <div
            class="flex-1 bg-[var(--color-card)] rounded-[var(--radius-lg)] shadow-md border border-[var(--color-border)] p-6 flex flex-col justify-between text-center">
            <div>
                <h3 class="text-[var(--font-size-lg)] font-semibold mb-1">Your QR Ticket</h3>
                <p id="ticket-instruction" class="text-[var(--font-size-sm)] text-[var(--color-gray-600)] mb-6">
                    <?= $isBorrowed ? 'This ticket is already active.' : 'Present this to the librarian' ?>
                </p>

                <div
                    class="w-full p-4 border border-gray-300 rounded-lg bg-white flex justify-center items-center relative min-h-[250px]">

                    <div id="ticket-message-container" class="flex items-center justify-center p-4">
                        <?php if (!empty($isExpired) && $isExpired): ?>
                            <p id="ticket-message" class="text-red-500 font-semibold text-lg flex items-center justify-center gap-2">
                                <i class="ph ph-x-circle text-2xl"></i> QR Code Ticket Expired
                            </p>
                        <?php elseif (!empty($isBorrowed) && $isBorrowed): ?>
                            <p id="ticket-message" class="text-green-600 font-semibold text-lg flex items-center justify-center gap-2">
                                <i class="ph ph-check-circle text-2xl"></i> Active Borrowing Transaction
                            </p>
                        <?php elseif (empty($qrPath) && empty($isBorrowed) && empty($isExpired)): ?>
                            <p id="ticket-message" class="text-gray-500 font-semibold text-lg flex items-center justify-center gap-2">
                                <i class="ph ph-info text-2xl"></i> No active borrowing ticket.
                            </p>
                        <?php else: ?>
                            <p id="ticket-message" class="hidden"></p>
                        <?php endif; ?>
                    </div>

                    <img id="qr-image" src="<?= $qrPath ?? '' ?>" alt="QR Code" class="w-56 h-56 object-contain <?= empty($qrPath) ? 'hidden' : '' ?>" />
                </div>
            </div>

            <div class="mt-6">
                <div class="text-[var(--font-size-sm)] text-[var(--color-gray-700)] border-t border-[var(--color-border)] pt-4">
                    <p class="font-medium" id="ticket_code">
                        Ticket Code: <span class="text-[var(--color-primary)] font-bold"><?= $transaction_code ?? 'N/A' ?></span>
                    </p>
                    <p id="generated_date" class="text-[var(--font-size-xs)] text-[var(--color-gray-500)] <?= (empty($generated_at) || $isBorrowed || $isExpired) ? 'hidden' : '' ?>">
                        Generated Time: <?= !empty($generated_at) ? date("h:i:s A", strtotime($generated_at)) : "N/A" ?>
                    </p>
                    <p id="due_date" class="text-[var(--font-size-xs)] text-red-500 font-medium <?= (empty($expires_at) || $isBorrowed || $isExpired) ? 'hidden' : '' ?>">
                        Expiration: <?= !empty($expires_at) ? 'Expires at: ' . date("h:i:s A", strtotime($expires_at)) : 'N/A' ?>
                    </p>
                </div>

                <a id="download-button" href="<?= $qrPath ?? '#' ?>" download="<?= ($transaction_code ?? 'qrcode') ?>.svg"
                    class="mt-4 flex items-center justify-center gap-2 px-4 py-2 rounded-[var(--radius-md)] bg-orange-500 text-white font-medium shadow hover:bg-orange-600 transition <?= empty($qrPath) ? 'hidden' : '' ?>">
                    <i class="ph ph-download-simple text-xl"></i> Download
                </a>
            </div>
        </div>

        <!-- Ticket Details Card -->
        <div class="flex-1 bg-[var(--color-card)] rounded-[var(--radius-lg)] shadow-md border border-[var(--color-border)] p-6 flex flex-col">
            <h3 class="text-md font-medium mb-1">Ticket Details</h3>
            <p class="text-sm text-amber-700 mb-5">Information encoded in your QR ticket</p>

            <dl class="space-y-3 text-sm flex-1">
                <div class="flex justify-between items-center">
                    <dt class="text-amber-700 font-medium">Faculty ID:</dt>
                    <dd id="detailsStudentNumber" class="text-right"><?= htmlspecialchars($faculty["faculty_id"] ?? "N/A") ?></dd>
                </div>
                <div class="flex justify-between items-center">
                    <dt class="text-amber-700 font-medium">Name:</dt>
                    <dd id="detailsStudentName" class="text-right"><?= htmlspecialchars($faculty["name"] ?? "Faculty Name") ?></dd>
                </div>
                <div class="flex justify-between items-center">
                    <dt class="text-amber-700 font-medium">College/Dept:</dt>
                    <dd id="detailsStudentCourse" class="text-right"><?= htmlspecialchars($faculty["college"] ?? "N/A") ?></dd>
                </div>
                <div class="flex justify-between items-center">
                    <dt class="text-amber-700 font-medium">Items:</dt>
                    <dd id="detailsBookCount" class="text-right"><?= !empty($items) ? count($items) : 0 ?> Item(s)</dd>
                </div>
            </dl>

            <div class="mt-6 p-4 rounded-[var(--radius-md)] bg-[var(--color-blue-50,#eff6ff)] border border-[var(--color-blue-200,#bfdbfe)]">
                <h4 class="font-medium text-md mb-2">Notice:</h4>
                <ol class="list-decimal list-inside space-y-1 text-sm text-amber-700">
                    <li>Show this QR code to the librarian</li>
                    <li>Always bring your ID when returning items</li>
                    <li>Return items on time to avoid fines</li>
                </ol>
            </div>
        </div>
    </div>

    <!-- Items List Section -->
    <div id="checkedOutSection" class="space-y-6 mt-6 <?= empty($items) ? 'hidden' : '' ?>">
        <?php if (!empty($items)): ?>
            <div class="p-4 border border-amber-200 bg-amber-50 rounded-lg flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <i class="ph ph-qr-code text-2xl text-amber-600"></i>
                    <div><h3 class="font-medium text-amber-900">Checked Out Items</h3><p class="text-sm text-amber-700">Included in this QR ticket</p></div>
                </div>
                <div class="text-right"><p class="text-2xl font-bold text-amber-700"><?= count($items) ?></p><p class="text-xs text-amber-600">Total</p></div>
            </div>
            <div class="border-t border-x border-green-300 bg-green-50 rounded-xl overflow-hidden">
                <div class="p-4 flex items-center justify-between border-b border-green-200 bg-green-100/50"><h4 class="font-medium text-green-700 flex items-center gap-2"><i class="ph ph-book text-lg"></i> Books List (<?= count($items) ?>)</h4></div>
                <?php foreach ($items as $index => $item): ?>
                    <div class="bg-white p-4 flex gap-3 border-b border-green-300 last:border-0">
                        <div class="flex items-center"><i class="ph ph-book-open text-3xl text-green-500"></i></div>
                        <div class="flex-1 min-w-0">
                            <p class="font-medium"><?= htmlspecialchars($item["title"]) ?></p>
                            <p class="text-sm text-gray-600">by <?= htmlspecialchars($item["author"]) ?></p>
                            <div class="flex flex-wrap gap-2 mt-2 text-[10px]">
                                <span class="px-2 py-0.5 bg-gray-100 rounded">ACC: <?= htmlspecialchars($item["accession_number"] ?? "N/A") ?></span>
                                <span class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded">CALL: <?= htmlspecialchars($item["call_number"] ?? "N/A") ?></span>
                            </div>
                        </div>
                        <span class="w-6 h-6 flex items-center justify-center rounded-full bg-green-600 text-white text-[10px] font-bold"><?= $index + 1 ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
document.addEventListener('DOMContentLoaded', function() {
    const qrImage = document.getElementById('qr-image');
    const ticketMessageContainer = document.getElementById('ticket-message-container');
    const downloadButton = document.getElementById('download-button');
    const ticketCodeSpan = document.querySelector('#ticket_code span');
    const generatedDateP = document.getElementById('generated_date');
    const dueDateP = document.getElementById('due_date');
    const ticketInstruction = document.getElementById('ticket-instruction');
    
    const detailsElems = {
        number: document.getElementById('detailsStudentNumber'),
        name: document.getElementById('detailsStudentName'),
        course: document.getElementById('detailsStudentCourse'),
        count: document.getElementById('detailsBookCount')
    };
    const checkedOutSection = document.getElementById('checkedOutSection');

    const STORAGE_URL = "<?= STORAGE_URL ?>";
    const BASE_URL = "<?= BASE_URL ?>";

    let statusInterval;
    let isChecking = false;
    let hadActiveTicket = <?= ($transaction_code) ? 'true' : 'false' ?>;

    function displayMessage(text, type = 'info') {
        ticketMessageContainer.innerHTML = '';
        const p = document.createElement('p');
        p.id = 'ticket-message';
        p.className = `font-semibold text-lg flex items-center justify-center gap-2`;
        
        let iconClass = 'ph ph-info';
        if (type === 'expired' || type === 'error') {
            p.classList.add('text-red-500');
            iconClass = 'ph ph-x-circle';
        } else if (type === 'success' || type === 'borrowed') {
            p.classList.add('text-green-600');
            iconClass = 'ph ph-check-circle';
        } else {
            p.classList.add('text-gray-500');
        }
        
        p.innerHTML = `<i class="${iconClass} text-2xl"></i> ${text}`;
        ticketMessageContainer.appendChild(p);
        
        if (qrImage) qrImage.classList.add('hidden');
        if (downloadButton) downloadButton.classList.add('hidden');
        if (ticketInstruction) ticketInstruction.textContent = 'Your ticket status';
    }

    function showQR(ticket) {
        ticketMessageContainer.innerHTML = '';
        if (qrImage) {
            qrImage.src = `${STORAGE_URL}/uploads/qrcodes/${ticket.transaction_code}.svg?t=${Date.now()}`;
            qrImage.classList.remove('hidden');
        }
        if (downloadButton) {
            downloadButton.href = `${STORAGE_URL}/uploads/qrcodes/${ticket.transaction_code}.svg`;
            downloadButton.classList.remove('hidden');
        }
        if (ticketInstruction) ticketInstruction.textContent = 'Present this to the librarian';
        if (ticketCodeSpan) ticketCodeSpan.textContent = ticket.transaction_code || 'N/A';
        
        updateDetails(ticket);
    }

    function updateDetails(data) {
        if (data.student) {
            detailsElems.number.textContent = data.student.student_number || 'N/A';
            detailsElems.name.textContent = data.student.name || 'N/A';
            detailsElems.course.textContent = data.student.course || 'N/A';
        }
        detailsElems.count.textContent = data.books ? `${data.books.length} Item(s)` : '0 Item(s)';

        if (data.books && data.books.length > 0) {
            renderItemsList(data.books);
            checkedOutSection.classList.remove('hidden');
        } else {
            checkedOutSection.classList.add('hidden');
        }
    }

    function renderItemsList(books) {
        let html = `
            <div class="p-4 border border-amber-200 bg-amber-50 rounded-lg flex items-center justify-between shadow-sm">
                <div class="flex items-center gap-3">
                    <i class="ph ph-qr-code text-2xl text-amber-600"></i>
                    <div><h3 class="font-medium text-amber-900">Checked Out Items</h3><p class="text-sm text-amber-700">Included in this QR ticket</p></div>
                </div>
                <div class="text-right"><p class="text-2xl font-bold text-amber-700">${books.length}</p><p class="text-xs text-amber-600">Total</p></div>
            </div>
            <div class="border-t border-x border-green-300 bg-green-50 rounded-xl overflow-hidden shadow-sm">
                <div class="p-4 flex items-center justify-between border-b border-green-200 bg-green-100/50">
                    <h4 class="font-bold text-green-800 flex items-center gap-2"><i class="ph ph-book text-lg"></i> Books List</h4>
                </div>
                ${books.map((book, index) => `
                    <div class="bg-white p-4 flex gap-3 border-b border-green-300 transition-colors last:border-0">
                        <div class="flex items-center"><i class="ph ph-book-open text-3xl text-green-500"></i></div>
                        <div class="flex-1 min-w-0">
                            <p class="font-bold text-gray-900">${book.title}</p>
                            <p class="text-xs text-gray-500">by ${book.author}</p>
                            <div class="flex flex-wrap gap-2 mt-2 text-[10px]">
                                <span class="px-2 py-0.5 bg-gray-100 rounded">ACC: ${book.accession_number ?? 'N/A'}</span>
                                <span class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded">CALL: ${book.call_number ?? 'N/A'}</span>
                            </div>
                        </div>
                        <span class="w-6 h-6 flex items-center justify-center rounded-full bg-green-600 text-white text-[10px] font-bold">${index + 1}</span>
                    </div>
                `).join('')}
            </div>`;
        checkedOutSection.innerHTML = html;
    }

    async function checkTicketStatus() {
        if (isChecking) return;
        isChecking = true;

        try {
            const res = await fetch(`${BASE_URL}/api/faculty/qrBorrowingTicket/checkStatus`);
            const data = await res.json();
            if (!data.success) return;

            if (data.status === 'pending') {
                hadActiveTicket = true;
                showQR(data);
                if (generatedDateP) {
                    generatedDateP.textContent = `Generated Time: ${new Date(data.generated_at).toLocaleTimeString([], { hour: 'numeric', minute: '2-digit', hour12: true })}`;
                    generatedDateP.classList.remove('hidden');
                }
                if (dueDateP && data.expires_at) {
                    dueDateP.textContent = `Expiration: Expires at ${new Date(data.expires_at).toLocaleTimeString([], { hour: 'numeric', minute: '2-digit', hour12: true })}`;
                    dueDateP.classList.remove('hidden');
                }
            } else if (data.status === 'none') {
                if (hadActiveTicket) {
                    displayMessage('Borrowed Successfully!', 'success');
                    setTimeout(() => {
                        window.location.reload();
                    }, 5000);
                } else {
                    displayMessage('No active borrowing ticket.', 'info');
                    checkedOutSection.classList.add('hidden');
                }
            }
        } catch (err) {
            console.error('Ticket Pulse Error:', err);
        } finally {
            isChecking = false;
        }
    }

    statusInterval = setInterval(checkTicketStatus, 5000);
    checkTicketStatus();
});
</script>
</main>
