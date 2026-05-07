<div class="mb-3">
    <h2 class="text-2xl font-bold mb-4">My Borrowing History</h2>
    <p class="text-gray-700">Complete record of your library borrowing activity with detailed information.</p>
</div>

<div id="statsContainer" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

    <div
        class="relative bg-[var(--color-card)] border border-[var(--color-border)] rounded-lg shadow-md p-4 border-l-4 border-l-[var(--color-primary)]">
        <p class="text-sm text-gray-600">Total Borrowed</p>
        <h2 id="statTotal" class="text-3xl font-bold text-[var(--color-primary)]">0</h2>
        <p class="text-xs text-gray-500">All time</p>
        <i class="ph ph-books absolute top-3 right-3 text-[var(--color-primary)] text-xl"></i>
    </div>

    <div
        class="relative bg-[var(--color-card)] border border-[var(--color-border)] rounded-lg shadow-md p-4 border-l-4 border-l-[var(--color-green-600)]">
        <p class="text-sm text-gray-600">Currently Borrowed</p>
        <h2 id="statCurrent" class="text-3xl font-bold text-[var(--color-green-600)]">0</h2>
        <p class="text-xs text-gray-500">Active books</p>
        <i class="ph ph-eye absolute top-3 right-3 text-[var(--color-green-600)] text-xl"></i>
    </div>

    <div
        class="relative bg-[var(--color-card)] border border-[var(--color-border)] rounded-lg shadow-md p-4 border-l-4 border-l-[var(--color-destructive)]">
        <p class="text-sm text-gray-600">Overdue</p>
        <h2 id="statOverdue" class="text-3xl font-bold text-[var(--color-destructive)]">0</h2>
        <p class="text-xs text-gray-500">Need attention</p>
        <i class="ph ph-warning-circle absolute top-3 right-3 text-[var(--color-destructive)] text-xl"></i>
    </div>

    <div
        class="relative bg-[var(--color-card)] border border-[var(--color-border)] rounded-lg shadow-md p-4 border-l-4 border-l-[var(--color-accent)]">
        <p class="text-sm text-gray-600">Returned</p>
        <h2 id="statReturned" class="text-3xl font-bold text-[var(--color-accent)]">0</h2>
        <p class="text-xs text-gray-500">Completed</p>
        <i class="ph ph-check-circle absolute top-3 right-3 text-[var(--color-accent)] text-xl"></i>
    </div>

</div>


<div class="bg-[var(--color-card)] border border-[var(--color-border)] rounded-lg shadow-md p-6">
    <h3 class="text-lg font-semibold mb-1 flex items-center gap-2 text-[var(--color-foreground)]">
        <i class="ph ph-calendar text-[var(--color-primary)]"></i>
        Borrowing Records
    </h3>
    <p class="text-sm text-gray-600 mb-6">
        Complete history of your book borrowings with detailed information
    </p>

    <div id="recordsContainer" class="space-y-6">
        <div class="text-center py-10 text-gray-500" id="loadingIndicator">Loading history...</div>
    </div>

    <!-- Pagination Container -->
    <div id="paginationContainer" class="pagination-wrapper text-center mt-8"></div>
</div>

<script>
    const CURRENT_STUDENT_ID = <?= $_SESSION['user_data']['student_id'] ?? 0 ?>;
</script>
<script src="<?= BASE_URL ?>/js/user/borrowingHistory.js" defer></script>