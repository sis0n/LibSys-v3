<body class="min-h-screen p-6">

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold mb-4 sm:mb-0">My Cart</h2>
            <p class="text-gray-700">Review and checkout your selected items.</p>
        </div>
        <span id="cart-count" class="mt-3 sm:mt-0 self-end sm:self-auto px-[var(--spacing-3)] py-[var(--spacing-1)] rounded-md border 
           text-[var(--font-size-sm)] text-[var(--color-foreground)] border-[var(--color-border)] bg-white 
           shadow-sm flex items-center gap-[var(--spacing-1)]">
            <i class="ph ph-shopping-cart text-sm"></i> 0 total items
        </span>
    </div>

    <div id="empty-state"
        class="mt-2 border rounded-[var(--radius-lg)] border-[var(--color-border)] bg-white shadow-sm flex flex-col items-center justify-center py-9">
        <i class="ph ph-shopping-cart text-6xl text-amber-700 mb-3"></i>
        <p class="text-[var(--color-foreground)] font-medium text-[var(--font-size-base)]">Your cart is empty</p>
        <p class="text-amber-700 text-[var(--font-size-sm)] p-3">
            Add books or equipment from the catalog to get started.
        </p>
    </div>

    <div id="cart-items" class="hidden">
        <div class="mt-4 border rounded-[var(--radius-lg)] border-[var(--color-border)] bg-white shadow-sm p-4">
            <div class="flex items-center justify-between mb-2">
                <h3 class="font-semibold text-[var(--color-foreground)]">Checkout Summary</h3>

                <label class="flex items-center gap-2 cursor-pointer text-sm text-[var(--color-muted-foreground)]">
                    <input type="checkbox" id="select-all" class="w-4 h-4 accent-orange-600 rounded cursor-pointer" />
                    Select All
                </label>
            </div>

            <p id="summary-text" class="text-[var(--color-muted-foreground)] text-[var(--font-size-sm)]">
                0 book(s) and 0 equipment item(s) selected for borrowing
            </p>

            <div class="mt-4 flex items-center gap-2">
                <button id="checkout-btn"
                    class="flex-1 bg-orange-600 text-white font-semibold rounded-[var(--radius-lg)] py-2 hover:bg-orange-500 transition">
                    Check Out
                </button>
                <button id="clear-cart-btn" class="px-4 py-2 border rounded-[var(--radius-lg)] border-[var(--color-border)] 
                           text-[var(--color-orange-700)] font-medium hover:bg-[var(--color-orange-100)] transition">
                    Clear Cart
                </button>
            </div>
        </div>
    </div>
    <div id="selected-items-section" class="hidden">
        <h3 class="mt-6 font-semibold text-[var(--color-foreground)]">Selected Items</h3>
        <div id="selected-items"></div>
    </div>

    <script src="<?= BASE_URL ?>/js/user/myCart.js" defer></script>

</body>