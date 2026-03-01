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
        <div class="flex-1 bg-[var(--color-card)] rounded-[var(--radius-lg)] shadow-md border border-[var(--color-border)] p-6 flex flex-col justify-between text-center">
            <div>
                <h3 class="text-[var(--font-size-lg)] font-semibold mb-1">Official QR Ticket</h3>
                <p id="ticket-instruction" class="text-[var(--font-size-sm)] text-[var(--color-gray-600)] mb-6">Present to the librarian</p>

                <div class="w-full p-4 border border-gray-300 rounded-lg bg-white flex justify-center items-center relative min-h-[250px]">
                    <div id="ticket-message-container" class="flex items-center justify-center p-4">
                        <?php if (!empty($isExpired) && $isExpired): ?>
                            <p id="ticket-message" class="text-red-500 font-semibold text-lg flex items-center justify-center gap-2">
                                <i class="ph ph-x-circle text-2xl"></i> QR Code Ticket Expired
                            </p>
                        <?php elseif (!empty($isBorrowed) && $isBorrowed): ?>
                            <p id="ticket-message" class="text-green-600 font-semibold text-lg flex items-center justify-center gap-2">
                                <i class="ph ph-check-circle text-2xl"></i> Active Transaction
                            </p>
                        <?php elseif (empty($qrPath) && empty($isBorrowed) && empty($isExpired)): ?>
                            <p id="ticket-message" class="text-gray-500 font-semibold text-lg flex items-center justify-center gap-2">
                                <i class="ph ph-info text-2xl"></i> No active ticket.
                            </p>
                        <?php else: ?>
                            <p id="ticket-message" class="hidden"></p>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($qrPath)): ?>
                        <img id="qr-image" src="<?= $qrPath ?>" alt="QR Code" class="w-56 h-56 object-contain" />
                    <?php else: ?>
                        <img id="qr-image" src="" alt="QR Code" class="w-56 h-56 object-contain hidden" />
                    <?php endif; ?>
                </div>
            </div>

            <div class="mt-6">
                <div class="text-[var(--font-size-sm)] text-[var(--color-gray-700)] border-t border-[var(--color-border)] pt-4">
                    <p class="font-medium" id="ticket_code">
                        Ticket Code: <span class="text-[var(--color-primary)] font-bold"><?= $transaction_code ?? 'N/A' ?></span>
                    </p>
                    <p id="generated_date" class="text-[var(--font-size-xs)] text-[var(--color-gray-500)] <?= (empty($generated_at) || $isBorrowed || $isExpired) ? 'hidden' : '' ?>">
                        Generated: <?= !empty($generated_at) ? date("h:i:s A", strtotime($generated_at)) : "" ?>
                    </p>
                    <p id="due_date_borrowed" class="text-[var(--font-size-xs)] text-green-600 font-bold <?= (empty($isBorrowed) || empty($due_date)) ? 'hidden' : '' ?>">
                        Due Date: <?= !empty($due_date) ? date("F d, Y h:i A", strtotime($due_date)) : "" ?>
                    </p>
                </div>
                <a id="download-button" href="<?= $qrPath ?? '#' ?>" download="<?= ($transaction_code ?? 'qrcode') ?>.svg"
                    class="mt-4 flex items-center justify-center gap-2 px-4 py-2 rounded-[var(--radius-md)] bg-orange-500 text-white font-medium shadow hover:bg-orange-600 transition <?= (empty($qrPath)) ? 'hidden' : '' ?>">
                    <i class="ph ph-download-simple text-xl"></i> Download
                </a>
            </div>
        </div>

        <!-- Details Card -->
        <div class="flex-1 bg-[var(--color-card)] rounded-[var(--radius-lg)] shadow-md border border-[var(--color-border)] p-6 flex flex-col">
            <h3 class="text-md font-medium mb-1">Faculty Info</h3>
            <p class="text-sm text-amber-700 mb-5">Encoded ticket data</p>
            <dl class="space-y-3 text-sm flex-1">
                <div class="flex justify-between items-center"><dt class="text-amber-700 font-medium">Faculty ID:</dt><dd id="detailsStudentNumber" class="text-right"><?= htmlspecialchars($faculty["faculty_id"] ?? "N/A") ?></dd></div>
                <div class="flex justify-between items-center"><dt class="text-amber-700 font-medium">Name:</dt><dd id="detailsStudentName" class="text-right"><?= htmlspecialchars($faculty["name"] ?? "Faculty Name") ?></dd></div>
                <div class="flex justify-between items-center"><dt class="text-amber-700 font-medium">College/Dept:</dt><dd id="detailsStudentCourse" class="text-right"><?= htmlspecialchars($faculty["college"] ?? "N/A") ?></dd></div>
                <div class="flex justify-between items-center"><dt class="text-amber-700 font-medium">Items:</dt><dd id="detailsBookCount" class="text-right"><?= count($items) ?> Item(s)</dd></div>
            </dl>
        </div>
    </div>

    <!-- Checked Out Items -->
    <div id="checkedOutSection" class="space-y-6 mt-6 <?= empty($items) ? 'hidden' : '' ?>">
        <?php if (!empty($items)): ?>
            <div class="border-t border-x border-green-300 bg-green-50 rounded-xl overflow-hidden">
                <div class="p-4 bg-green-100/50 border-b border-green-200"><h4 class="font-bold text-green-800 flex items-center gap-2"><i class="ph ph-book text-lg"></i> Books List</h4></div>
                <?php foreach ($items as $index => $item): ?>
                    <div class="bg-white p-4 flex gap-3 border-b border-green-300 last:border-0">
                        <div class="flex items-center"><i class="ph ph-book-open text-3xl text-green-500"></i></div>
                        <div class="flex-1"><p class="font-bold"><?= htmlspecialchars($item["title"]) ?></p><p class="text-xs text-gray-500">by <?= htmlspecialchars($item["author"]) ?></p></div>
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
    const dueDateBorrowedP = document.getElementById('due_date_borrowed');
    const ticketInstruction = document.getElementById('ticket-instruction');
    const checkedOutSection = document.getElementById('checkedOutSection');
    
    const STORAGE_URL = "<?= STORAGE_URL ?>";
    const BASE_URL = "<?= BASE_URL ?>";
    let statusInterval;
    let isChecking = false;
    let hadActiveTicket = <?= ($transaction_code) ? 'true' : 'false' ?>;

    function displayMessage(text, type = 'info') {
        ticketMessageContainer.innerHTML = '';
        const p = document.createElement('p');
        p.className = `font-semibold text-lg flex items-center justify-center gap-2`;
        let iconClass = (type === 'success') ? 'ph ph-check-circle text-green-600' : 'ph ph-info text-gray-500';
        p.innerHTML = `<i class="${iconClass} text-2xl"></i> ${text}`;
        ticketMessageContainer.appendChild(p);
        if (qrImage) qrImage.classList.add('hidden');
        if (downloadButton) downloadButton.classList.add('hidden');
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
                if (qrImage) {
                    qrImage.src = `${STORAGE_URL}/uploads/qrcodes/${data.transaction_code}.svg?t=${Date.now()}`;
                    qrImage.classList.remove('hidden');
                }
                if (ticketCodeSpan) ticketCodeSpan.textContent = data.transaction_code;
            } else if (data.status === 'none') {
                if (hadActiveTicket) {
                    displayMessage('Borrowed Successfully!', 'success');
                    setTimeout(() => window.location.reload(), 5000);
                } else {
                    displayMessage('No active ticket.', 'info');
                    if (checkedOutSection) checkedOutSection.classList.add('hidden');
                }
            }
        } catch (err) {
            console.error('Check failed:', err);
        } finally {
            isChecking = false;
        }
    }

    statusInterval = setInterval(checkTicketStatus, 5000);
    checkTicketStatus();
});
</script>
</main>